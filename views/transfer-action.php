<?php
/**
 * Officer action handler. POST-only. Runs inside its own transaction-safe flow.
 */
require_login();
require_role('registration_officer', 'land_office_officer', 'administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php?page=dashboard');
}

verify_csrf();

$caseId = (int)($_GET['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$notes  = trim((string)($_POST['notes'] ?? ''));

if ($caseId <= 0) {
    redirect('index.php?page=dashboard&notice=not_found');
}
if ($notes === '') {
    redirect("index.php?page=transfer&id={$caseId}&notice=notes_required");
}

$user = current_user();
$role = $user['role'];
$pdo  = db();

$stmt = $pdo->prepare("SELECT * FROM transfer_cases WHERE id = :id");
$stmt->execute([':id' => $caseId]);
$case = $stmt->fetch();
if (!$case) {
    redirect('index.php?page=dashboard&notice=not_found');
}

// Independent re-check of the action matrix
$allowed = allowed_actions($role, $case['status']);
if (!in_array($action, $allowed, true)) {
    redirect("index.php?page=transfer&id={$caseId}&notice=unauthorized");
}

$newStatus = action_new_status($action, $case['status']);
if ($newStatus === null) {
    redirect("index.php?page=transfer&id={$caseId}&notice=invalid_input");
}

// ============ update_record (the actual ownership transfer) ============
if ($action === 'update_record') {
    try {
        $pdo->beginTransaction();

        // 1. Lock seller ownership
        $stmt = $pdo->prepare("SELECT * FROM ownerships WHERE parcel_id = :p AND user_id = :u FOR UPDATE");
        $stmt->execute([':p' => $case['parcel_id'], ':u' => $case['from_user_id']]);
        $seller = $stmt->fetch();
        if (!$seller) {
            throw new RuntimeException('Seller ownership missing');
        }

        $sNum = (int)$seller['share_numerator'];
        $sDen = (int)$seller['share_denominator'];
        $tNum = (int)$case['transfer_share_numerator'];
        $tDen = (int)$case['transfer_share_denominator'];

        // 2. Compute new seller share
        $newSNum = $sNum * $tDen - $tNum * $sDen;
        $newSDen = $sDen * $tDen;
        if ($newSNum < 0) {
            throw new RuntimeException('Share exceeds');
        }

        // 3. Update or delete seller
        if ($newSNum === 0) {
            $del = $pdo->prepare("DELETE FROM ownerships WHERE id = :id");
            $del->execute([':id' => $seller['id']]);
        } else {
            [$nn, $nd] = simplify_fraction($newSNum, $newSDen);
            $upd = $pdo->prepare("UPDATE ownerships SET share_numerator = :n, share_denominator = :d WHERE id = :id");
            $upd->execute([':n' => $nn, ':d' => $nd, ':id' => $seller['id']]);
        }

        // 4. Lock buyer ownership (if any)
        $stmt = $pdo->prepare("SELECT * FROM ownerships WHERE parcel_id = :p AND user_id = :u FOR UPDATE");
        $stmt->execute([':p' => $case['parcel_id'], ':u' => $case['to_user_id']]);
        $buyer = $stmt->fetch();

        if ($buyer) {
            $bNum = (int)$buyer['share_numerator'];
            $bDen = (int)$buyer['share_denominator'];
            $combNum = $bNum * $tDen + $tNum * $bDen;
            $combDen = $bDen * $tDen;
            [$nn, $nd] = simplify_fraction($combNum, $combDen);
            $upd = $pdo->prepare("UPDATE ownerships SET share_numerator = :n, share_denominator = :d, share_type = 'joint' WHERE id = :id");
            $upd->execute([':n' => $nn, ':d' => $nd, ':id' => $buyer['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO ownerships (parcel_id, user_id, share_type, share_numerator, share_denominator) VALUES (:p, :u, 'joint', :n, :d)");
            $ins->execute([
                ':p' => $case['parcel_id'],
                ':u' => $case['to_user_id'],
                ':n' => $tNum,
                ':d' => $tDen,
            ]);
        }

        // 5. Update case status
        $upd = $pdo->prepare("UPDATE transfer_cases SET status = 'record_updated' WHERE id = :id");
        $upd->execute([':id' => $caseId]);

        // 6. Insert event
        $ins = $pdo->prepare("INSERT INTO transfer_events (transfer_case_id, event_type, actor_user_id, notes) VALUES (:c, 'record_updated', :a, :n)");
        $ins->execute([':c' => $caseId, ':a' => $user['id'], ':n' => $notes]);

        // 7. Audit
        $ins = $pdo->prepare("INSERT INTO audit_logs (actor_user_id, action, table_name, record_id, old_values, new_values) VALUES (:a, :ac, 'transfer_cases', :r, :o, :nw)");
        $ins->execute([
            ':a'  => $user['id'],
            ':ac' => 'update_record',
            ':r'  => $caseId,
            ':o'  => json_encode(['status' => $case['status']]),
            ':nw' => json_encode(['status' => 'record_updated', 'notes' => $notes]),
        ]);

        $pdo->commit();
        redirect("index.php?page=transfer&id={$caseId}&notice=record_updated");
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        redirect("index.php?page=transfer&id={$caseId}&notice=transaction_failed");
    }
}

// ============ Non-update_record actions ============
try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare("UPDATE transfer_cases SET status = :s WHERE id = :id");
    $upd->execute([':s' => $newStatus, ':id' => $caseId]);

    $eventType = status_event_type($newStatus);
    $ins = $pdo->prepare("INSERT INTO transfer_events (transfer_case_id, event_type, actor_user_id, notes) VALUES (:c, :e, :a, :n)");
    $ins->execute([':c' => $caseId, ':e' => $eventType, ':a' => $user['id'], ':n' => $notes]);

    $ins = $pdo->prepare("INSERT INTO audit_logs (actor_user_id, action, table_name, record_id, old_values, new_values) VALUES (:a, :ac, 'transfer_cases', :r, :o, :nw)");
    $ins->execute([
        ':a'  => $user['id'],
        ':ac' => $action,
        ':r'  => $caseId,
        ':o'  => json_encode(['status' => $case['status']]),
        ':nw' => json_encode(['status' => $newStatus, 'notes' => $notes]),
    ]);

    $pdo->commit();
    redirect("index.php?page=transfer&id={$caseId}&notice=action_completed");
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    redirect("index.php?page=transfer&id={$caseId}&notice=transaction_failed");
}