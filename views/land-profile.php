<?php
require_role('citizen');
$user = current_user();
$uid  = $user['id'];
$pdo  = db();

$pageTitle = t('land_profile.title');

$stmt = $pdo->prepare(
    "SELECT p.*, o.share_type, o.share_numerator, o.share_denominator
     FROM land_parcels p JOIN ownerships o ON o.parcel_id = p.id
     WHERE o.user_id = :u ORDER BY p.parcel_code ASC"
);
$stmt->execute([':u' => $uid]);
$parcels = $stmt->fetchAll();

$totalArea = 0.0;
foreach ($parcels as $p) {
    $totalArea += adjusted_area((float)$p['area_decimal'], (int)$p['share_numerator'], (int)$p['share_denominator']);
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM ownership_correction_requests WHERE user_id = :u AND status = 'pending'");
$stmt->execute([':u' => $uid]);
$pendingCorr = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM transfer_cases
     WHERE (from_user_id = :u1 OR to_user_id = :u2)
       AND status IN ('submitted','identity_verified','registration_review','land_office_review','needs_correction','approved')"
);
$stmt->execute([':u1' => $uid, ':u2' => $uid]);
$activeTransfers = (int)$stmt->fetchColumn();
?>
<main class="container py-4">
    <h1 class="h3 fw-bold mb-4"><?= h(t('land_profile.title')) ?></h1>

    <div class="row g-3 mb-4">
        <?php
        $cards = [
            [t('land_profile.stat.total_parcels'), (string)count($parcels), 'bi-folder2-open', 'success'],
            [t('land_profile.stat.total_area'), number_format($totalArea, 3) . ' ' . t('unit.decimal'), 'bi-rulers', 'info'],
            [t('land_profile.stat.pending_corr'), (string)$pendingCorr, 'bi-pencil-square', 'warning'],
            [t('land_profile.stat.active_transfers'), (string)$activeTransfers, 'bi-arrow-left-right', 'primary'],
        ];
        foreach ($cards as [$label, $value, $icon, $color]): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="lb-stat-card lb-stat-<?= h($color) ?>">
                    <div class="lb-stat-icon"><i class="bi <?= h($icon) ?>"></i></div>
                    <div class="lb-stat-value"><?= h($value) ?></div>
                    <div class="lb-stat-label"><?= h($label) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$parcels): ?>
        <div class="alert alert-info"><?= h(t('land_profile.alert.no_parcels')) ?></div>
    <?php endif; ?>
    <?php if ($pendingCorr > 0): ?>
        <div class="alert alert-warning"><?= h(t('land_profile.alert.pending_corr')) ?></div>
    <?php endif; ?>
    <?php if ($activeTransfers > 0): ?>
        <div class="alert alert-info"><?= h(t('land_profile.alert.active_transfers')) ?></div>
    <?php endif; ?>

    <div class="lb-card-lg">
        <?php if (!$parcels): ?>
            <p class="text-muted mb-0"><?= h(t('parcels.empty')) ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th><?= h(t('parcels.col.code')) ?></th>
                        <th><?= h(t('parcels.col.mouza')) ?></th>
                        <th><?= h(t('parcels.col.dag')) ?></th>
                        <th><?= h(t('parcels.col.area')) ?></th>
                        <th><?= h(t('parcels.col.your_share')) ?></th>
                        <th><?= h(t('parcels.col.adjusted_area')) ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($parcels as $p):
                        $adj = adjusted_area((float)$p['area_decimal'], (int)$p['share_numerator'], (int)$p['share_denominator']);
                    ?>
                        <tr>
                            <td class="fw-semibold"><?= h($p['parcel_code']) ?></td>
                            <td><?= h($p['mouza']) ?></td>
                            <td><?= h($p['dag_number']) ?></td>
                            <td><?= h(area_display((float)$p['area_decimal'], $p['area_unit'])) ?></td>
                            <td>
                                <?= share_badge($p['share_type']) ?>
                                <span class="small text-muted ms-1"><?= h(share_fraction((int)$p['share_numerator'], (int)$p['share_denominator'])) ?></span>
                            </td>
                            <td><?= h(number_format($adj, 3)) ?> <?= h(t('unit.decimal')) ?></td>
                            <td class="text-end"><a class="btn btn-ghost btn-sm" href="index.php?page=parcel&id=<?= (int)$p['id'] ?>"><i class="bi bi-eye"></i> <?= h(t('common.view')) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="lb-card-lg mt-4">
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-success" href="index.php?page=parcels"><i class="bi bi-folder2-open me-1"></i><?= h(t('nav.parcels')) ?></a>
            <a class="btn btn-outline-success" href="index.php?page=corrections"><i class="bi bi-pencil-square me-1"></i><?= h(t('nav.corrections')) ?></a>
            <a class="btn btn-outline-success" href="index.php?page=transfers"><i class="bi bi-arrow-left-right me-1"></i><?= h(t('nav.transfers')) ?></a>
            <a class="btn btn-outline-success" href="index.php?page=profile"><i class="bi bi-person me-1"></i><?= h(t('nav.profile')) ?></a>
        </div>
    </div>
</main>