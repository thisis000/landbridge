<?php
require_role('citizen');
$user = current_user();
$uid  = $user['id'];
$pdo  = db();

$pageTitle = t('parcels.title');

$search        = trim((string)($_GET['q'] ?? ''));
$filterShare   = (string)($_GET['share'] ?? '');
$filterDistrict= (string)($_GET['district'] ?? '');

// District dropdown options
$stmt = $pdo->prepare(
    "SELECT DISTINCT p.district FROM land_parcels p
     JOIN ownerships o ON o.parcel_id = p.id
     WHERE o.user_id = :u AND p.district IS NOT NULL AND p.district <> ''
     ORDER BY p.district"
);
$stmt->execute([':u' => $uid]);
$districts = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Base conditions
$where = "o.user_id = :u";
$params = [':u' => $uid];

if ($search !== '') {
    $where .= " AND (p.parcel_code LIKE :s1 OR p.mouza LIKE :s2 OR p.dag_number LIKE :s3 OR p.khatian_number LIKE :s4 OR p.district LIKE :s5 OR p.division LIKE :s6)";
    $like = '%' . $search . '%';
    $params[':s1'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
    $params[':s4'] = $like;
    $params[':s5'] = $like;
    $params[':s6'] = $like;
}
if ($filterShare === 'sole' || $filterShare === 'joint') {
    $where .= " AND o.share_type = :sh";
    $params[':sh'] = $filterShare;
}
if ($filterDistrict !== '') {
    $where .= " AND p.district = :d";
    $params[':d'] = $filterDistrict;
}

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM land_parcels p JOIN ownerships o ON o.parcel_id = p.id WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$perPage = 10;
$pageNum = max(1, (int)($_GET['p'] ?? 1));
$offset  = ($pageNum - 1) * $perPage;
$pages   = max(1, (int)ceil($total / $perPage));
if ($pageNum > $pages) {
    $pageNum = $pages;
    $offset  = ($pageNum - 1) * $perPage;
}

$sql = "SELECT p.*, o.share_type, o.share_numerator, o.share_denominator
        FROM land_parcels p
        JOIN ownerships o ON o.parcel_id = p.id
        WHERE $where
        ORDER BY p.parcel_code ASC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$anyFilter = ($search !== '' || $filterShare !== '' || $filterDistrict !== '');
?>
<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 fw-bold mb-0"><?= h(t('parcels.title')) ?></h1>
        <a class="btn btn-outline-success" href="index.php?page=land-profile"><i class="bi bi-person-vcard me-1"></i><?= h(t('nav.land_profile')) ?></a>
    </div>

    <form method="get" class="lb-card-lg mb-4">
        <input type="hidden" name="page" value="parcels">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted"><?= h(t('common.search')) ?></label>
                <input type="text" name="q" class="form-control" placeholder="<?= h(t('parcels.search_placeholder')) ?>" value="<?= h($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted"><?= h(t('parcels.filter_share')) ?></label>
                <select name="share" class="form-select">
                    <option value=""><?= h(t('common.all')) ?></option>
                    <option value="sole"  <?= $filterShare==='sole'?'selected':'' ?>><?= h(t('share.sole')) ?></option>
                    <option value="joint" <?= $filterShare==='joint'?'selected':'' ?>><?= h(t('share.joint')) ?></option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted"><?= h(t('parcels.filter_district')) ?></label>
                <select name="district" class="form-select">
                    <option value=""><?= h(t('common.all')) ?></option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?= h($d) ?>" <?= $filterDistrict===$d?'selected':'' ?>><?= h($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-funnel"></i></button>
                <?php if ($anyFilter): ?>
                    <a class="btn btn-outline-secondary" href="index.php?page=parcels" title="<?= h(t('common.clear_filters')) ?>"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="lb-card-lg">
        <?php if (!$rows): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                <?= h(t('parcels.empty')) ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th><?= h(t('parcels.col.code')) ?></th>
                        <th><?= h(t('parcels.col.mouza')) ?></th>
                        <th><?= h(t('parcels.col.district')) ?></th>
                        <th><?= h(t('parcels.col.dag')) ?></th>
                        <th><?= h(t('parcels.col.khatian')) ?></th>
                        <th><?= h(t('parcels.col.area')) ?></th>
                        <th><?= h(t('parcels.col.share_type')) ?></th>
                        <th><?= h(t('parcels.col.your_share')) ?></th>
                        <th><?= h(t('parcels.col.adjusted_area')) ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rows as $p):
                        $adj = adjusted_area((float)$p['area_decimal'], (int)$p['share_numerator'], (int)$p['share_denominator']);
                    ?>
                        <tr>
                            <td class="fw-semibold"><?= h($p['parcel_code']) ?></td>
                            <td><?= h($p['mouza']) ?></td>
                            <td><?= h($p['district'] ?? '—') ?></td>
                            <td><?= h($p['dag_number']) ?></td>
                            <td><?= h($p['khatian_number']) ?></td>
                            <td><?= h(area_display((float)$p['area_decimal'], $p['area_unit'])) ?></td>
                            <td><?= share_badge($p['share_type']) ?></td>
                            <td><?= h(share_fraction((int)$p['share_numerator'], (int)$p['share_denominator'])) ?></td>
                            <td><?= h(number_format($adj, 3)) ?> <?= h(t('unit.decimal')) ?></td>
                            <td class="text-end"><a class="btn btn-ghost btn-sm" href="index.php?page=parcel&id=<?= (int)$p['id'] ?>"><i class="bi bi-eye"></i> <?= h(t('common.view')) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($pageNum > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= h(url_with(['p' => $pageNum-1])) ?>"><?= h(t('common.prev')) ?></a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i===$pageNum?'active':'' ?>"><a class="page-link" href="<?= h(url_with(['p' => $i])) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <?php if ($pageNum < $pages): ?>
                            <li class="page-item"><a class="page-link" href="<?= h(url_with(['p' => $pageNum+1])) ?>"><?= h(t('common.next')) ?></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>