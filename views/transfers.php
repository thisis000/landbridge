<?php
require_login();
$user = current_user();
$uid  = $user['id'];
$role = $user['role'];
$pdo  = db();

$pageTitle = t('transfers.title');

if ($role === 'citizen') {
    $stmt = $pdo->prepare(
        "SELECT tc.*, p.mouza, p.dag_number, p.parcel_code,
                fu.name AS from_name, tu.name AS to_name
         FROM transfer_cases tc
         JOIN land_parcels p ON p.id = tc.parcel_id
         JOIN users fu ON fu.id = tc.from_user_id
         JOIN users tu ON tu.id = tc.to_user_id
         WHERE tc.from_user_id = :u1 OR tc.to_user_id = :u2
         ORDER BY tc.submitted_at DESC"
    );
    $stmt->execute([':u1' => $uid, ':u2' => $uid]);
} else {
    $stmt = $pdo->query(
        "SELECT tc.*, p.mouza, p.dag_number, p.parcel_code,
                fu.name AS from_name, tu.name AS to_name
         FROM transfer_cases tc
         JOIN land_parcels p ON p.id = tc.parcel_id
         JOIN users fu ON fu.id = tc.from_user_id
         JOIN users tu ON tu.id = tc.to_user_id
         ORDER BY tc.submitted_at DESC"
    );
}
$rows = $stmt->fetchAll();
?>
<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1"><?= h(t('transfers.title')) ?></h1>
            <p class="text-muted mb-0 small"><?= h(t('transfers.subtitle')) ?></p>
        </div>
        <?php if ($role === 'citizen'): ?>
            <a class="btn btn-primary" href="index.php?page=transfer-new"><i class="bi bi-plus-circle me-1"></i><?= h(t('transfers.cta_new')) ?></a>
        <?php endif; ?>
    </div>

    <div class="lb-card-lg">
        <?php if (!$rows): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                <?= h(t('transfers.empty')) ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th><?= h(t('transfers.col.case')) ?></th>
                        <th><?= h(t('transfers.col.parcel')) ?></th>
                        <th><?= h(t('transfers.col.from_to')) ?></th>
                        <th><?= h(t('transfers.col.status')) ?></th>
                        <th><?= h(t('transfers.col.submitted')) ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="fw-semibold font-monospace small"><?= h($r['case_number']) ?></td>
                            <td><div class="fw-semibold"><?= h($r['mouza']) ?></div><div class="small text-muted"><?= h($r['parcel_code']) ?> · <?= h($r['dag_number']) ?></div></td>
                            <td class="small"><?= h($r['from_name']) ?> → <?= h($r['to_name']) ?></td>
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