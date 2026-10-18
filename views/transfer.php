<?php
require_login();
$user = current_user();
$role = $user['role'];
$uid  = $user['id'];
$pdo  = db();

$caseId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT tc.*, p.parcel_code, p.mouza, p.dag_number,
            fu.name AS from_name, fu.land_person_id AS from_lpid, fu.email AS from_email,
            tu.name AS to_name,   tu.land_person_id AS to_lpid,   tu.email AS to_email
     FROM transfer_cases tc
     JOIN land_parcels p ON p.id = tc.parcel_id
     JOIN users fu ON fu.id = tc.from_user_id
     JOIN users tu ON tu.id = tc.to_user_id
     WHERE tc.id = :id"
);
$stmt->execute([':id' => $caseId]);
$case = $stmt->fetch();

if (!$case) {
    redirect('index.php?page=transfers&notice=not_found');
}

// Authorization
$isParticipant = ((int)$case['from_user_id'] === $uid) || ((int)$case['to_user_id'] === $uid);
$isOfficer     = in_array($role, ['registration_officer', 'land_office_officer', 'administrator'], true);
if (!$isParticipant && !$isOfficer) {
    redirect('index.php?page=dashboard&notice=unauthorized');
}

$pageTitle = t('transfer.title', ['case' => $case['case_number']]);

// Timeline
$stmt = $pdo->prepare(
    "SELECT e.*, u.name AS actor_name, u.role AS actor_role
     FROM transfer_events e
     LEFT JOIN users u ON u.id = e.actor_user_id
     WHERE e.transfer_case_id = :c
     ORDER BY e.created_at ASC, e.id ASC"
);
$stmt->execute([':c' => $caseId]);
$timeline = $stmt->fetchAll();

$allowed = ($role === 'citizen') ? [] : allowed_actions($role, $case['status']);
?>
<main class="container py-4">
    <div class="mb-3">
        <a href="index.php?page=transfers" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i><?= h(t('nav.transfers')) ?></a>
    </div>
    <h1 class="h3 fw-bold mb-4"><?= h(t('transfer.title', ['case' => $case['case_number']])) ?></h1>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="lb-card-lg mb-4">
                <h2 class="h6 fw-bold mb-3"><?= h(t('transfer.section.info')) ?></h2>
                <div class="row g-3 small">
                    <div class="col-sm-6"><div class="text-muted small"><?= h(t('transfer.field.case')) ?></div><div class="fw-semibold font-monospace"><?= h($case['case_number']) ?></div></div>
                    <div class="col-sm-6"><div class="text-muted small"><?= h(t('transfer.field.status')) ?></div><div><?= status_badge($case['status']) ?></div></div>
                    <div class="col-sm-6"><div class="text-muted small"><?= h(t('transfer.field.parcel')) ?></div><div class="fw-semibold"><?= h($case['parcel_code']) ?> · <?= h($case['mouza']) ?> / <?= h($case['dag_number']) ?></div></div>
                    <div class="col-sm-6"><div class="text-muted small"><?= h(t('transfer.field.share')) ?></div><div class="fw-semibold"><?= h(share_fraction((int)$case['transfer_share_numerator'], (int)$case['transfer_share_denominator'])) ?></div></div>
                    <div class="col-sm-6"><div class="text-muted small"><?= h(t('transfer.field.from')) ?></div><div><?= h($case['from_name']) ?> <span class="text-muted">(<?= h($case['from_lpid']) ?>)</span></div><div class="small text-muted"><?= h($case['from_email']) ?></div></div>
                    <div class="col-sm-6"><div class="text-muted small"><?= h(t('transfer.field.to')) ?></div><div><?= h($case['to_name']) ?> <span class="text-muted">(<?= h($case['to_lpid']) ?>)</span></div><div class="small text-muted"><?= h($case['to_email']) ?></div></div>
                    <div class="col-12"><div class="text-muted small"><?= h(t('transfer.field.submitted')) ?></div><div><?= h(date('Y-m-d H:i', strtotime($case['submitted_at']))) ?></div></div>
                </div>
            </div>

            <?php if ($allowed): ?>
                <div class="lb-card-lg">
                    <h2 class="h6 fw-bold mb-3"><?= h(t('transfer.section.actions')) ?></h2>
                    <form method="post" action="index.php?page=transfer-action&id=<?= (int)$case['id'] ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <?php foreach ($allowed as $a): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="action" id="act-<?= h($a) ?>" value="<?= h($a) ?>" required>
                                    <label class="form-check-label" for="act-<?= h($a) ?>"><?= h(t('action.' . $a)) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= h(t('transfer.notes_label')) ?></label>
                            <textarea name="notes" class="form-control" rows="3" required></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit"><?= h(t('transfer.action_submit')) ?></button>
                    </form>
                </div>
            <?php elseif ($isOfficer): ?>
                <div class="alert alert-secondary"><?= h(t('transfer.no_actions')) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="lb-card-lg">
                <h2 class="h6 fw-bold mb-3"><?= h(t('transfer.section.timeline')) ?></h2>
                <?php if (!$timeline): ?>
                    <p class="text-muted small mb-0"><?= h(t('transfer.timeline_empty')) ?></p>
                <?php else: ?>
                    <ol class="lb-timeline list-unstyled mb-0">
                        <?php foreach ($timeline as $ev): ?>
                            <li class="lb-timeline-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <div><?= event_badge($ev['event_type']) ?></div>
                                    <div class="small text-muted"><?= h(date('Y-m-d H:i', strtotime($ev['created_at']))) ?></div>
                                </div>
                                <?php if ($ev['actor_name']): ?>
                                    <div class="small text-muted"><?= h(t('transfer.actor')) ?>: <?= h($ev['actor_name']) ?> (<?= h(t('role.' . $ev['actor_role'])) ?>)</div>
                                <?php endif; ?>
                                <?php if (!empty($ev['notes'])): ?>
                                    <div class="small mt-1">“<?= h($ev['notes']) ?>”</div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>