<?php
require_role('registration_officer', 'administrator');
$user = current_user();
$uid  = $user['id'];
$role = $user['role'];
$pdo  = db();

$pageTitle = t('queue.title.reg');

$statusFilter = (string)($_GET['status'] ?? '');
$assignment   = (string)($_GET['assign'] ?? '');
$validStatuses = ['submitted', 'identity_verified', 'registration_review', 'needs_correction'];
if ($statusFilter !== '' && !in_array($statusFilter, $validStatuses, true)) $statusFilter = '';
if (!in_array($assignment, ['', 'mine', 'unassigned'], true)) $assignment = '';

$where = "tc.status IN ('submitted','identity_verified','registration_review','needs_correction')";
$params = [];

if ($statusFilter !== '') {
    $where .= " AND tc.status = :st";
    $params[':st'] = $statusFilter;
}

if ($assignment === 'mine') {
    $where .= " AND EXISTS (SELECT 1 FROM transfer_events e WHERE e.transfer_case_id = tc.id AND e.actor_user_id = :me)";
    $params[':me'] = $uid;
} elseif ($assignment === 'unassigned') {
    // No events from anyone other than the two participants
    $where .= " AND NOT EXISTS (
        SELECT 1 FROM transfer_events e
        WHERE e.transfer_case_id = tc.id
          AND e.actor_user_id IS NOT NULL
          AND e.actor_user_id <> tc.from_user_id
          AND e.actor_user_id <> tc.to_user_id
    )";
}

$sql = "SELECT tc.*, p.parcel_code, p.mouza, p.dag_number,
               fu.name AS from_name, fu.land_person_id AS from_lpid,
               tu.name AS to_name,   tu.land_person_id AS to_lpid
        FROM transfer_cases tc
        JOIN land_parcels p ON p.id = tc.parcel_id
        JOIN users fu ON fu.id = tc.from_user_id
        JOIN users tu ON tu.id = tc.to_user_id
        WHERE $where
        ORDER BY FIELD(tc.status, 'submitted','identity_verified','needs_correction','registration_review'),
                 tc.submitted_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<main class="container py-4">
    <h1 class="h3 fw-bold mb-4"><?= h(t('queue.title.reg')) ?></h1>

    <form method="get" class="lb-card-lg mb-4">
        <input type="hidden" name="page" value="reg-queue">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted"><?= h(t('queue.filter_status')) ?></label>
                <select name="status" class="form-select">
                    <option value=""><?= h(t('common.all')) ?></option>
                    <?php foreach ($validStatuses as $s): ?>
                        <option value="<?= h($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= h(t('status.' . $s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted"><?= h(t('queue.filter_assignment')) ?></label>
                <select name="assign" class="form-select">
                    <option value=""><?= h(t('queue.assignment.all')) ?></option>
                    <option value="mine"       <?= $assignment === 'mine'       ? 'selected' : '' ?>><?= h(t('queue.assignment.mine')) ?></option>
                    <option value="unassigned" <?= $assignment === 'unassigned' ? 'selected' : '' ?>><?= h(t('queue.assignment.unassigned')) ?></option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel"></i></button></div>
            <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="index.php?page=reg-queue"><?= h(t('common.clear_filters')) ?></a></div>
        </div>
    </form>

    <div class="lb-card-lg">
        <?php if (!$rows): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i><?= h(t('queue.empty')) ?></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th><?= h(t('queue.col.case')) ?></th>
                        <th><?= h(t('queue.col.parcel')) ?></th>
                        <th><?= h(t('queue.col.from_to')) ?></th>
                        <th><?= h(t('queue.col.share')) ?></th>
                        <th><?= h(t('queue.col.status')) ?></th>
                        <th><?= h(t('queue.col.submitted')) ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="fw-semibold font-monospace small"><?= h($r['case_number']) ?></td>
                            <td><div class="fw-semibold"><?= h($r['mouza']) ?></div><div class="small text-muted"><?= h($r['parcel_code']) ?> · <?= h($r['dag_number']) ?></div></td>
                            <td class="small"><?= h($r['from_name']) ?> <span class="text-muted">(<?= h($r['from_lpid']) ?>)</span> → <?= h($r['to_name']) ?> <span class="text-muted">(<?= h($r['to_lpid']) ?>)</span></td>
                            <td><?= h(share_fraction((int)$r['transfer_share_numerator'], (int)$r['transfer_share_denominator'])) ?></td>
                            <td><?= status_badge($r['status']) ?></td>
                            <td class="small text-muted"><?= h(date('Y-m-d', strtotime($r['submitted_at']))) ?></td>
                            <td class="text-end"><a class="btn btn-ghost btn-sm" href="index.php?page=transfer&id=<?= (int)$r['id'] ?>"><i class="bi bi-eye"></i> <?= h(t('common.view')) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>