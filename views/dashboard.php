<?php
require_login();
$user = current_user();
$role = $user['role'];
$pdo  = db();

$pageTitle = t('nav.dashboard');

$data = [];

if ($role === 'citizen') {
    $uid = $user['id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ownerships WHERE user_id = :u");
    $stmt->execute([':u' => $uid]);
    $data['parcel_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transfer_cases WHERE from_user_id = :u1 OR to_user_id = :u2");
    $stmt->execute([':u1' => $uid, ':u2' => $uid]);
    $data['transfer_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM transfer_cases
         WHERE (from_user_id = :u1 OR to_user_id = :u2)
           AND status IN ('submitted','identity_verified','registration_review','land_office_review','needs_correction')"
    );
    $stmt->execute([':u1' => $uid, ':u2' => $uid]);
    $data['pending_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM transfer_cases
         WHERE (from_user_id = :u1 OR to_user_id = :u2)
           AND status IN ('approved','record_updated')"
    );
    $stmt->execute([':u1' => $uid, ':u2' => $uid]);
    $data['approved_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT p.*, o.share_type, o.share_numerator, o.share_denominator
         FROM land_parcels p
         JOIN ownerships o ON o.parcel_id = p.id
         WHERE o.user_id = :u
         ORDER BY p.created_at DESC LIMIT 5"
    );
    $stmt->execute([':u' => $uid]);
    $data['parcels'] = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT tc.*, p.mouza, p.dag_number
         FROM transfer_cases tc
         JOIN land_parcels p ON p.id = tc.parcel_id
         WHERE tc.from_user_id = :u1 OR tc.to_user_id = :u2
         ORDER BY tc.submitted_at DESC LIMIT 5"
    );
    $stmt->execute([':u1' => $uid, ':u2' => $uid]);
    $data['transfers'] = $stmt->fetchAll();
}

if ($role === 'registration_officer') {
    $queueStatuses = ['submitted', 'identity_verified', 'registration_review', 'needs_correction'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transfer_cases WHERE status IN (:s1,:s2,:s3,:s4)");
    $stmt->execute([':s1' => 'submitted', ':s2' => 'identity_verified', ':s3' => 'registration_review', ':s4' => 'needs_correction']);
    $data['queue_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM (
            SELECT from_user_id AS uid FROM transfer_cases WHERE status IN ('submitted','identity_verified','registration_review','needs_correction')
            UNION
            SELECT to_user_id AS uid FROM transfer_cases WHERE status IN ('submitted','identity_verified','registration_review','needs_correction')
        ) t"
    );
    $stmt->execute();
    $data['parties_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transfer_cases WHERE status = 'submitted'");
    $stmt->execute();
    $data['pending_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT tc.*, p.mouza, p.dag_number,
                fu.name AS from_name, fu.land_person_id AS from_lpid,
                tu.name AS to_name,   tu.land_person_id AS to_lpid
         FROM transfer_cases tc
         JOIN land_parcels p ON p.id = tc.parcel_id
         JOIN users fu ON fu.id = tc.from_user_id
         JOIN users tu ON tu.id = tc.to_user_id
         WHERE tc.status IN ('submitted','identity_verified','registration_review','needs_correction')
         ORDER BY tc.submitted_at ASC
         LIMIT 15"
    );
    $stmt->execute();
    $data['queue'] = $stmt->fetchAll();
}

if ($role === 'land_office_officer') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transfer_cases WHERE status IN ('registration_review','land_office_review','needs_correction')");
    $stmt->execute();
    $data['queue_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query(
        "SELECT COUNT(DISTINCT p.mouza)
         FROM transfer_cases tc JOIN land_parcels p ON p.id = tc.parcel_id
         WHERE tc.status IN ('registration_review','land_office_review','needs_correction')"
    );
    $data['mouza_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transfer_cases WHERE status = 'registration_review'");
    $stmt->execute();
    $data['pending_count'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT tc.*, p.mouza, p.dag_number,
                fu.name AS from_name, fu.land_person_id AS from_lpid,
                tu.name AS to_name,   tu.land_person_id AS to_lpid
         FROM transfer_cases tc
         JOIN land_parcels p ON p.id = tc.parcel_id
         JOIN users fu ON fu.id = tc.from_user_id
         JOIN users tu ON tu.id = tc.to_user_id
         WHERE tc.status IN ('registration_review','land_office_review','needs_correction')
         ORDER BY tc.submitted_at ASC
         LIMIT 15"
    );
    $stmt->execute();
    $data['queue'] = $stmt->fetchAll();
}

if ($role === 'administrator') {
    $data['users_count']    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $data['parcels_count']  = (int)$pdo->query("SELECT COUNT(*) FROM land_parcels")->fetchColumn();
    $data['transfers_count']= (int)$pdo->query("SELECT COUNT(*) FROM transfer_cases")->fetchColumn();
    $data['audit_count']    = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

    $data['recent_users'] = $pdo->query(
        "SELECT id, land_person_id, name, email, role, is_active, created_at
         FROM users ORDER BY created_at DESC LIMIT 8"
    )->fetchAll();

    $data['recent_audit'] = $pdo->query(
        "SELECT a.*, u.name AS actor_name
         FROM audit_logs a
         LEFT JOIN users u ON u.id = a.actor_user_id
         ORDER BY a.created_at DESC LIMIT 10"
    )->fetchAll();
}
?>
<main class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1"><?= h(t('dashboard.welcome', ['name' => $user['name']])) ?></h1>
        <p class="text-muted mb-0">
            <?php
            if ($role === 'citizen') echo h(t('dashboard.citizen.sub'));
            elseif ($role === 'registration_officer') echo h(t('dashboard.reg.sub'));
            elseif ($role === 'land_office_officer') echo h(t('dashboard.lo.sub'));
            else echo h(t('dashboard.admin.sub'));
            ?>
        </p>
    </div>

    <?php if ($role === 'citizen'): ?>
        <div class="row g-3 mb-4">
            <?php
            $cards = [
                [t('dashboard.stat.parcels'), $data['parcel_count'], 'bi-folder2-open', 'success'],
                [t('dashboard.stat.transfers'), $data['transfer_count'], 'bi-arrow-left-right', 'info'],
                [t('dashboard.stat.pending'), $data['pending_count'], 'bi-hourglass-split', 'warning'],
                [t('dashboard.stat.approved'), $data['approved_count'], 'bi-check2-circle', 'primary'],
            ];
            foreach ($cards as [$label, $value, $icon, $color]): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="lb-stat-card lb-stat-<?= h($color) ?>">
                        <div class="lb-stat-icon"><i class="bi <?= h($icon) ?>"></i></div>
                        <div class="lb-stat-value"><?= (int)$value ?></div>
                        <div class="lb-stat-label"><?= h($label) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="lb-card-lg h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0"><?= h(t('dashboard.my_parcels')) ?></h2>
                        <a class="small" href="index.php?page=parcels"><?= h(t('common.view_all')) ?> →</a>
                    </div>
                    <?php if (!$data['parcels']): ?>
                        <p class="text-muted small mb-0"><?= h(t('parcels.empty')) ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr>
                                    <th><?= h(t('parcels.col.code')) ?></th>
                                    <th><?= h(t('parcels.col.mouza')) ?>/<?= h(t('parcels.col.dag')) ?></th>
                                    <th><?= h(t('parcels.col.area')) ?></th>
                                    <th><?= h(t('parcels.col.your_share')) ?></th>
                                    <th></th>
                                </tr></thead>
                                <tbody>
                                <?php foreach ($data['parcels'] as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= h($p['parcel_code']) ?></td>
                                        <td><?= h($p['mouza']) ?> / <?= h($p['dag_number']) ?></td>
                                        <td><?= h(area_display((float)$p['area_decimal'], $p['area_unit'])) ?></td>
                                        <td>
                                            <?= share_badge($p['share_type']) ?>
                                            <span class="small text-muted ms-1"><?= h(share_fraction((int)$p['share_numerator'], (int)$p['share_denominator'])) ?></span>
                                        </td>
                                        <td class="text-end"><a class="btn btn-ghost btn-sm" href="index.php?page=parcel&id=<?= (int)$p['id'] ?>"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="lb-card-lg h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0"><?= h(t('dashboard.my_transfers')) ?></h2>
                        <a class="small" href="index.php?page=transfers"><?= h(t('common.view_all')) ?> →</a>
                    </div>
                    <?php if (!$data['transfers']): ?>
                        <p class="text-muted small mb-0"><?= h(t('transfers.empty')) ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr>
                                    <th><?= h(t('transfers.col.case')) ?></th>
                                    <th><?= h(t('transfers.col.parcel')) ?></th>
                                    <th><?= h(t('transfers.col.status')) ?></th>
                                    <th></th>
                                </tr></thead>
                                <tbody>
                                <?php foreach ($data['transfers'] as $tr): ?>
                                    <tr>
                                        <td class="fw-semibold font-monospace small"><?= h($tr['case_number']) ?></td>
                                        <td><?= h($tr['mouza']) ?> / <?= h($tr['dag_number']) ?></td>
                                        <td><?= status_badge($tr['status']) ?></td>
                                        <td class="text-end"><a class="btn btn-ghost btn-sm" href="index.php?page=transfer&id=<?= (int)$tr['id'] ?>"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="lb-card-lg">
            <h2 class="h6 fw-bold mb-3"><?= h(t('dashboard.quick_actions')) ?></h2>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-success" href="index.php?page=land-profile"><i class="bi bi-person-vcard me-1"></i><?= h(t('nav.land_profile')) ?></a>
                <a class="btn btn-outline-success" href="index.php?page=parcels"><i class="bi bi-folder2-open me-1"></i><?= h(t('nav.parcels')) ?></a>
                <a class="btn btn-primary" href="index.php?page=transfer-new"><i class="bi bi-plus-circle me-1"></i><?= h(t('nav.new_transfer')) ?></a>
                <a class="btn btn-outline-success" href="index.php?page=corrections"><i class="bi bi-pencil-square me-1"></i><?= h(t('nav.corrections')) ?></a>
            </div>
        </div>

    <?php elseif ($role === 'registration_officer' || $role === 'land_office_officer'): ?>
        <div class="row g-3 mb-4">
            <?php
            $cards = [
                [t('dashboard.stat.queue'), $data['queue_count'], 'bi-inboxes', 'success'],
                [$role === 'registration_officer' ? t('dashboard.stat.parties') : t('dashboard.stat.mouzas'),
                 $role === 'registration_officer' ? $data['parties_count'] : $data['mouza_count'],
                 'bi-people', 'info'],
                [t('dashboard.stat.pending'), $data['pending_count'], 'bi-hourglass-split', 'warning'],
            ];
            foreach ($cards as [$label, $value, $icon, $color]): ?>
                <div class="col-md-4">
                    <div class="lb-stat-card lb-stat-<?= h($color) ?>">
                        <div class="lb-stat-icon"><i class="bi <?= h($icon) ?>"></i></div>
                        <div class="lb-stat-value"><?= (int)$value ?></div>
                        <div class="lb-stat-label"><?= h($label) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="lb-card-lg">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-bold mb-0"><?= h(t('dashboard.queue_title')) ?></h2>
                <a class="small" href="index.php?page=<?= $role === 'registration_officer' ? 'reg-queue' : 'lo-queue' ?>"><?= h(t('dashboard.go_queue')) ?> →</a>
            </div>
            <?php if (!$data['queue']): ?>
                <p class="text-muted small mb-0"><?= h(t('queue.empty')) ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr>
                            <th><?= h(t('queue.col.case')) ?></th>
                            <th><?= h(t('queue.col.parcel')) ?></th>
                            <th><?= h(t('queue.col.from_to')) ?></th>
                            <th><?= h(t('queue.col.share')) ?></th>
                            <th><?= h(t('queue.col.status')) ?></th>
                            <th></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($data['queue'] as $c): ?>
                            <tr>
                                <td class="fw-semibold font-monospace small"><?= h($c['case_number']) ?></td>
                                <td><?= h($c['mouza']) ?> / <?= h($c['dag_number']) ?></td>
                                <td class="small"><?= h($c['from_name']) ?> <span class="text-muted">(<?= h($c['from_lpid']) ?>)</span> → <?= h($c['to_name']) ?> <span class="text-muted">(<?= h($c['to_lpid']) ?>)</span></td>
                                <td><?= h(share_fraction((int)$c['transfer_share_numerator'], (int)$c['transfer_share_denominator'])) ?></td>
                                <td><?= status_badge($c['status']) ?></td>
                                <td class="text-end"><a class="btn btn-ghost btn-sm" href="index.php?page=transfer&id=<?= (int)$c['id'] ?>"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($role === 'administrator'): ?>
        <div class="row g-3 mb-4">
            <?php
            $cards = [
                [t('dashboard.stat.users'), $data['users_count'], 'bi-people', 'success'],
                [t('dashboard.stat.parcels'), $data['parcels_count'], 'bi-folder2-open', 'info'],
                [t('dashboard.stat.transfers'), $data['transfers_count'], 'bi-arrow-left-right', 'warning'],
                [t('dashboard.stat.audit_logs'), $data['audit_count'], 'bi-shield-check', 'primary'],
            ];
            foreach ($cards as [$label, $value, $icon, $color]): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="lb-stat-card lb-stat-<?= h($color) ?>">
                        <div class="lb-stat-icon"><i class="bi <?= h($icon) ?>"></i></div>
                        <div class="lb-stat-value"><?= (int)$value ?></div>
                        <div class="lb-stat-label"><?= h($label) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="lb-card-lg h-100">
                    <h2 class="h6 fw-bold mb-3"><?= h(t('dashboard.recent_users')) ?></h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr>
                                <th>LPID</th>
                                <th><?= h(t('form.name')) ?></th>
                                <th><?= h(t('profile.role')) ?></th>
                                <th><?= h(t('common.status')) ?></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($data['recent_users'] as $u): ?>
                                <tr>
                                    <td class="font-monospace small"><?= h($u['land_person_id']) ?></td>
                                    <td class="small"><div class="fw-semibold"><?= h($u['name']) ?></div><div class="text-muted"><?= h($u['email']) ?></div></td>
                                    <td><?= role_badge($u['role']) ?></td>
                                    <td><?= (int)$u['is_active'] ? '<span class="badge bg-success-subtle text-success-emphasis">' . h(t('common.yes')) . '</span>' : '<span class="badge bg-danger-subtle text-danger-emphasis">' . h(t('common.no')) . '</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="lb-card-lg h-100">
                    <h2 class="h6 fw-bold mb-3"><?= h(t('dashboard.recent_audit')) ?></h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr>
                                <th><?= h(t('common.date')) ?></th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Target</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($data['recent_audit'] as $a): ?>
                                <tr>
                                    <td class="small text-muted"><?= h(date('Y-m-d H:i', strtotime($a['created_at']))) ?></td>
                                    <td class="small"><?= h($a['actor_name'] ?? '—') ?></td>
                                    <td class="small"><span class="badge bg-secondary"><?= h($a['action']) ?></span></td>
                                    <td class="small text-muted"><?= h($a['table_name']) ?>#<?= (int)$a['record_id'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>