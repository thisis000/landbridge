<?php
require_role('citizen');
$user = current_user();
$uid  = $user['id'];
$pdo  = db();

$parcelId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM land_parcels WHERE id = :id");
$stmt->execute([':id' => $parcelId]);
$parcel = $stmt->fetch();

if (!$parcel) {
    redirect('index.php?page=parcels&notice=not_found');
}

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM ownerships WHERE parcel_id = :p AND user_id = :u");
$stmt->execute([':p' => $parcelId, ':u' => $uid]);
$myOwn = $stmt->fetch();
if (!$myOwn) {
    redirect('index.php?page=parcels&notice=not_owner');
}

$pageTitle = t('parcel.title', ['code' => $parcel['parcel_code']]);

// Co-owners
$stmt = $pdo->prepare(
    "SELECT o.*, u.name, u.land_person_id
     FROM ownerships o JOIN users u ON u.id = o.user_id
     WHERE o.parcel_id = :p ORDER BY o.id ASC"
);
$stmt->execute([':p' => $parcelId]);
$coOwners = $stmt->fetchAll();

// Documents
$stmt = $pdo->prepare("SELECT * FROM parcel_documents WHERE parcel_id = :p ORDER BY document_date DESC");
$stmt->execute([':p' => $parcelId]);
$documents = $stmt->fetchAll();

// Corrections for this parcel
$stmt = $pdo->prepare("SELECT * FROM ownership_correction_requests WHERE parcel_id = :p ORDER BY created_at DESC");
$stmt->execute([':p' => $parcelId]);
$corrections = $stmt->fetchAll();

// History
$stmt = $pdo->prepare(
    "SELECT e.*, tc.case_number, u.name AS actor_name, u.role AS actor_role
     FROM transfer_events e
     JOIN transfer_cases tc ON tc.id = e.transfer_case_id
     LEFT JOIN users u ON u.id = e.actor_user_id
     WHERE tc.parcel_id = :p
     ORDER BY e.created_at ASC, e.id ASC"
);
$stmt->execute([':p' => $parcelId]);
$history = $stmt->fetchAll();

$myAdj = adjusted_area((float)$parcel['area_decimal'], (int)$myOwn['share_numerator'], (int)$myOwn['share_denominator']);
?>
<main class="container py-4">
    <div class="mb-3">
        <a href="index.php?page=parcels" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i><?= h(t('nav.parcels')) ?></a>
    </div>
    <h1 class="h3 fw-bold mb-4"><?= h(t('parcel.title', ['code' => $parcel['parcel_code']])) ?></h1>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="lb-card-lg mb-4">
                <h2 class="h6 fw-bold mb-3"><?= h(t('parcel.section.recorded')) ?></h2>
                <div class="row g-3 small">
                    <?php
                    $fields = [
                        [t('parcels.col.code'), $parcel['parcel_code']],
                        [t('parcels.col.mouza'), $parcel['mouza']],
                        [t('parcels.col.district'), $parcel['district'] ?? '—'],
                        [t('parcel.field.division'), $parcel['division'] ?? '—'],
                        [t('parcels.col.dag'), $parcel['dag_number']],
                        [t('parcels.col.khatian'), $parcel['khatian_number']],
                        [t('parcel.field.area'), area_display((float)$parcel['area_decimal'], $parcel['area_unit'])],
                        [t('parcel.field.share_type'), $myOwn['share_type'] === 'sole' ? t('share.sole') : t('share.joint')],
                    ];
                    foreach ($fields as [$label, $value]): ?>
                        <div class="col-sm-6">
                            <div class="text-muted small"><?= h($label) ?></div>
                            <div class="fw-semibold"><?= h((string)$value) ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!empty($parcel['description'])): ?>
                        <div class="col-12">
                            <div class="text-muted small"><?= h(t('parcel.field.description')) ?></div>
                            <div><?= h($parcel['description']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lb-card-lg mb-4">
                <h2 class="h6 fw-bold mb-3"><?= h(t('parcel.section.ownership')) ?></h2>
                <div class="row g-3 small">
                    <div class="col-sm-6">
                        <div class="text-muted"><?= h(t('parcel.field.recorded_share')) ?></div>
                        <div class="fw-semibold fs-5"><?= h(share_fraction((int)$myOwn['share_numerator'], (int)$myOwn['share_denominator'])) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted"><?= h(t('parcel.field.adjusted_area')) ?></div>
                        <div class="fw-semibold fs-5"><?= h(number_format($myAdj, 3)) ?> <?= h(t('unit.decimal')) ?></div>
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i><?= h(t('parcel.calc_note')) ?></p>
            </div>

            <div class="lb-card-lg mb-4">
                <h2 class="h6 fw-bold mb-3"><?= h(t('parcel.section.co_owners')) ?></h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr>
                            <th><?= h(t('parcel.col.owner')) ?></th>
                            <th><?= h(t('parcel.col.lpid')) ?></th>
                            <th><?= h(t('parcel.col.share')) ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($coOwners as $o): ?>
                            <tr>
                                <td><?= h($o['name']) ?><?= (int)$o['user_id'] === $uid ? ' <span class="badge bg-success-subtle text-success-emphasis">' . h(t('common.yes')) . '</span>' : '' ?></td>
                                <td class="font-monospace small"><?= h($o['land_person_id']) ?></td>
                                <td><?= h(share_fraction((int)$o['share_numerator'], (int)$o['share_denominator'])) ?> <span class="text-muted small">(<?= h($o['share_type'] === 'sole' ? t('share.sole') : t('share.joint')) ?>)</span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lb-card-lg mb-4">
                <h2 class="h6 fw-bold mb-3"><?= h(t('parcel.section.documents')) ?></h2>
                <?php if (!$documents): ?>
                    <p class="text-muted small mb-0"><?= h(t('parcel.no_documents')) ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr>
                                <th><?= h(t('corrections.col.type')) ?></th>
                                <th>Ref</th>
                                <th><?= h(t('common.date')) ?></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($documents as $d): ?>
                                <tr>
                                    <td><?= doc_badge($d['document_type']) ?></td>
                                    <td class="font-monospace small"><?= h($d['reference_number']) ?></td>
                                    <td class="small"><?= h($d['document_date']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="lb-card-lg mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0"><?= h(t('parcel.section.corrections')) ?></h2>
                    <a class="btn btn-sm btn-outline-success" href="index.php?page=corrections&parcel=<?= (int)$parcel['id'] ?>"><i class="bi bi-plus-lg me-1"></i><?= h(t('parcel.submit_correction')) ?></a>
                </div>
                <?php if (!$corrections): ?>
                    <p class="text-muted small mb-0"><?= h(t('parcel.no_corrections')) ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr>
                                <th><?= h(t('corrections.col.ref')) ?></th>
                                <th><?= h(t('corrections.col.type')) ?></th>
                                <th><?= h(t('corrections.col.status')) ?></th>
                                <th><?= h(t('common.date')) ?></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($corrections as $cr): ?>
                                <tr>
                                    <td class="font-monospace small">CR-<?= str_pad((string)$cr['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= h(t('correction_type.' . $cr['request_type'])) ?></td>
                                    <td><?= status_badge($cr['status']) ?></td>
                                    <td class="small"><?= h(date('Y-m-d', strtotime($cr['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="lb-card-lg">
                <h2 class="h6 fw-bold mb-3"><?= h(t('parcel.section.history')) ?></h2>
                <?php if (!$history): ?>
                    <p class="text-muted small mb-0"><?= h(t('parcel.no_history')) ?></p>
                <?php else: ?>
                    <ol class="lb-timeline list-unstyled mb-0">
                        <?php foreach ($history as $h): ?>
                            <li class="lb-timeline-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <div><?= event_badge($h['event_type']) ?></div>
                                    <div class="small text-muted"><?= h(date('Y-m-d H:i', strtotime($h['created_at']))) ?></div>
                                </div>
                                <div class="small mt-2"><span class="text-muted"><?= h(t('transfer.field.case')) ?>:</span> <span class="font-monospace"><?= h($h['case_number']) ?></span></div>
                                <?php if ($h['actor_name']): ?>
                                    <div class="small text-muted"><?= h(t('transfer.actor')) ?>: <?= h($h['actor_name']) ?> (<?= h(t('role.' . $h['actor_role'])) ?>)</div>
                                <?php endif; ?>
                                <?php if (!empty($h['notes'])): ?>
                                    <div class="small mt-1">“<?= h($h['notes']) ?>”</div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>