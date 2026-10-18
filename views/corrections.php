<?php
require_role('citizen');
$user = current_user();
$uid  = $user['id'];
$pdo  = db();

$pageTitle = t('corrections.title');

$errors = [];
$prefillParcel = (int)($_GET['parcel'] ?? 0);
$parcelId = $prefillParcel;
$requestType = 'name_correction';
$reason = '';

// My parcels
$stmt = $pdo->prepare(
    "SELECT p.id, p.parcel_code, p.mouza, p.dag_number
     FROM land_parcels p JOIN ownerships o ON o.parcel_id = p.id
     WHERE o.user_id = :u ORDER BY p.parcel_code"
);
$stmt->execute([':u' => $uid]);
$myParcels = $stmt->fetchAll();

$validTypes = ['name_correction', 'share_correction', 'ownership_removal', 'other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $parcelId    = (int)($_POST['parcel_id'] ?? 0);
    $requestType = (string)($_POST['request_type'] ?? '');
    $reason      = trim((string)($_POST['reason'] ?? ''));

    // ownership check
    $owned = false;
    foreach ($myParcels as $mp) {
        if ((int)$mp['id'] === $parcelId) { $owned = true; break; }
    }
    if (!$owned) {
        $errors[] = t('flash.not_owner');
    }
    if (!in_array($requestType, $validTypes, true)) {
        $errors[] = t('flash.invalid_input');
    }
    if ($reason === '') {
        $errors[] = t('flash.invalid_input');
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO ownership_correction_requests (user_id, parcel_id, request_type, reason, status) VALUES (:u, :p, :t, :r, 'pending')");
        $stmt->execute([':u' => $uid, ':p' => $parcelId, ':t' => $requestType, ':r' => $reason]);
        redirect('index.php?page=corrections&notice=correction_created');
    }
}

// Past requests
$stmt = $pdo->prepare(
    "SELECT cr.*, p.parcel_code, p.mouza, p.dag_number
     FROM ownership_correction_requests cr
     JOIN land_parcels p ON p.id = cr.parcel_id
     WHERE cr.user_id = :u ORDER BY cr.created_at DESC"
);
$stmt->execute([':u' => $uid]);
$requests = $stmt->fetchAll();
?>
<main class="container py-4">
    <h1 class="h3 fw-bold mb-1"><?= h(t('corrections.title')) ?></h1>
    <p class="text-muted small mb-4"><?= h(t('corrections.subtitle')) ?></p>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="lb-card-lg">
                <h2 class="h6 fw-bold mb-3"><?= h(t('corrections.submit_section')) ?></h2>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><?= h($err) ?></div>
                <?php endforeach; ?>

                <?php if (!$myParcels): ?>
                    <p class="text-muted small mb-0"><?= h(t('parcels.empty')) ?></p>
                <?php else: ?>
                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label"><?= h(t('corrections.parcel')) ?></label>
                            <select name="parcel_id" class="form-select" required>
                                <option value="">—</option>
                                <?php foreach ($myParcels as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>" <?= $parcelId === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= h($p['parcel_code']) ?> — <?= h($p['mouza']) ?> / <?= h($p['dag_number']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= h(t('corrections.type')) ?></label>
                            <select name="request_type" class="form-select" required>
                                <?php foreach ($validTypes as $t): ?>
                                    <option value="<?= h($t) ?>" <?= $requestType === $t ? 'selected' : '' ?>><?= h(t('correction_type.' . $t)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= h(t('corrections.reason')) ?></label>
                            <textarea name="reason" class="form-control" rows="4" required placeholder="<?= h(t('corrections.reason_placeholder')) ?>"><?= h($reason) ?></textarea>
                        </div>
                        <p class="small text-muted"><i class="bi bi-info-circle me-1"></i><?= h(t('corrections.disclaimer')) ?></p>
                        <button class="btn btn-primary" type="submit"><?= h(t('corrections.submit')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="lb-card-lg">
                <h2 class="h6 fw-bold mb-3"><?= h(t('corrections.list_section')) ?></h2>
                <?php if (!$requests): ?>
                    <p class="text-muted small mb-0"><?= h(t('corrections.empty')) ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr>
                                <th><?= h(t('corrections.col.ref')) ?></th>
                                <th><?= h(t('corrections.col.parcel')) ?></th>
                                <th><?= h(t('corrections.col.type')) ?></th>
                                <th><?= h(t('corrections.col.status')) ?></th>
                                <th><?= h(t('corrections.col.date')) ?></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td class="font-monospace small">CR-<?= str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td><div class="fw-semibold"><?= h($r['parcel_code']) ?></div><div class="small text-muted"><?= h($r['mouza']) ?> / <?= h($r['dag_number']) ?></div></td>
                                    <td class="small"><?= h(t('correction_type.' . $r['request_type'])) ?></td>
                                    <td><?= status_badge($r['status']) ?></td>
                                    <td class="small text-muted"><?= h(date('Y-m-d', strtotime($r['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>