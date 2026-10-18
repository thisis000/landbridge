<?php
require_role('citizen');
$user = current_user();
$uid  = $user['id'];
$pdo  = db();

$pageTitle = t('transfer_new.title');

$errors = [];
$parcelId   = (int)($_POST['parcel_id'] ?? 0);
$buyerId    = (int)($_POST['buyer_id'] ?? 0);
$num        = (int)($_POST['num'] ?? 1);
$den        = (int)($_POST['den'] ?? 1);
$notes      = trim((string)($_POST['notes'] ?? ''));

// My parcels
$stmt = $pdo->prepare(
    "SELECT p.*, o.share_type, o.share_numerator, o.share_denominator
     FROM land_parcels p JOIN ownerships o ON o.parcel_id = p.id
     WHERE o.user_id = :u ORDER BY p.parcel_code"
);
$stmt->execute([':u' => $uid]);
$myParcels = $stmt->fetchAll();

// Eligible buyers
$stmt = $pdo->prepare("SELECT id, name, land_person_id FROM users WHERE role = 'citizen' AND is_active = 1 AND id <> :u ORDER BY name");
$stmt->execute([':u' => $uid]);
$buyers = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Validate parcel ownership
    $owned = null;
    foreach ($myParcels as $mp) {
        if ((int)$mp['id'] === $parcelId) { $owned = $mp; break; }
    }
    if (!$owned) {
        $errors[] = t('flash.not_owner');
    }

    $buyerOk = false;
    foreach ($buyers as $b) {
        if ((int)$b['id'] === $buyerId) { $buyerOk = true; break; }
    }
    if (!$buyerOk) {
        $errors[] = t('flash.invalid_input');
    }

    if ($num <= 0 || $den <= 0 || $num > $den) {
        $errors[] = t('flash.invalid_input');
    }

    if ($owned && $num > 0 && $den > 0) {
        // transfer fraction must not exceed seller's current share
        // n/d <= sn/sd  <=>  n*sd <= sn*d
        $sn = (int)$owned['share_numerator'];
        $sd = (int)$owned['share_denominator'];
        if ($num * $sd > $sn * $den) {
            $errors[] = t('flash.share_exceeds');
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $case = generate_case_number($pdo);
            [$rn, $rd] = simplify_fraction($num, $den);

            $ins = $pdo->prepare(
                "INSERT INTO transfer_cases
                 (case_number, parcel_id, from_user_id, to_user_id,
                  transfer_share_numerator, transfer_share_denominator, status)
                 VALUES (:c, :p, :f, :t, :n, :d, 'submitted')"
            );
            $ins->execute([
                ':c' => $case, ':p' => $parcelId, ':f' => $uid, ':t' => $buyerId,
                ':n' => $rn, ':d' => $rd,
            ]);
            $newId = (int)$pdo->lastInsertId();

            $ins = $pdo->prepare("INSERT INTO transfer_events (transfer_case_id, event_type, actor_user_id, notes) VALUES (:c, 'submitted', :a, :n)");
            $ins->execute([':c' => $newId, ':a' => $uid, ':n' => $notes !== '' ? $notes : null]);

            $ins = $pdo->prepare("INSERT INTO audit_logs (actor_user_id, action, table_name, record_id, old_values, new_values) VALUES (:a, 'transfer_created', 'transfer_cases', :r, NULL, :nw)");
            $ins->execute([':a' => $uid, ':r' => $newId, ':nw' => json_encode(['status' => 'submitted', 'case' => $case])]);

            $pdo->commit();
            redirect('index.php?page=transfer&id=' . $newId . '&notice=transfer_created');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = t('flash.transaction_failed');
        }
    }
}
?>
<main class="container py-4">
    <div class="mb-3">
        <a href="index.php?page=transfers" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i><?= h(t('nav.transfers')) ?></a>
    </div>
    <h1 class="h3 fw-bold mb-1"><?= h(t('transfer_new.title')) ?></h1>
    <p class="text-muted small mb-4"><?= h(t('transfer_new.subtitle')) ?></p>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger py-2"><?= h($err) ?></div>
    <?php endforeach; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="lb-card-lg">
                <?php if (!$myParcels): ?>
                    <p class="text-muted mb-0"><?= h(t('transfer_new.no_parcels')) ?></p>
                <?php else: ?>
                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label"><?= h(t('transfer_new.parcel')) ?></label>
                            <select name="parcel_id" id="parcel-select" class="form-select" required>
                                <option value="">—</option>
                                <?php foreach ($myParcels as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"
                                            data-code="<?= h($p['parcel_code']) ?>"
                                            data-mouza="<?= h($p['mouza']) ?>"
                                            data-dag="<?= h($p['dag_number']) ?>"
                                            data-khatian="<?= h($p['khatian_number']) ?>"
                                            data-area="<?= h(number_format((float)$p['area_decimal'], 3)) ?> <?= h(t('unit.' . $p['area_unit'])) ?>"
                                            data-share="<?= h(share_fraction((int)$p['share_numerator'], (int)$p['share_denominator'])) ?>"
                                            data-district="<?= h($p['district'] ?? '—') ?>"
                                            <?= $parcelId === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= h($p['parcel_code']) ?> — <?= h($p['mouza']) ?> / <?= h($p['dag_number']) ?> (<?= h(share_fraction((int)$p['share_numerator'], (int)$p['share_denominator'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= h(t('transfer_new.buyer')) ?></label>
                            <select name="buyer_id" class="form-select" required>
                                <option value="">—</option>
                                <?php foreach ($buyers as $b): ?>
                                    <option value="<?= (int)$b['id'] ?>" <?= $buyerId === (int)$b['id'] ? 'selected' : '' ?>>
                                        <?= h($b['name']) ?> (<?= h($b['land_person_id']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label"><?= h(t('transfer_new.numerator')) ?></label>
                                <input type="number" name="num" class="form-control" min="1" value="<?= h((string)$num) ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label"><?= h(t('transfer_new.denominator')) ?></label>
                                <input type="number" name="den" class="form-control" min="1" value="<?= h((string)$den) ?>" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label"><?= h(t('transfer_new.notes')) ?> <span class="text-muted small"><?= h(t('common.optional')) ?></span></label>
                            <textarea name="notes" class="form-control" rows="3"><?= h($notes) ?></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit"><?= h(t('transfer_new.submit')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="lb-card-lg">
                <h2 class="h6 fw-bold mb-3"><?= h(t('transfer_new.preview_title')) ?></h2>
                <div id="parcel-preview" class="d-none small">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted"><?= h(t('parcels.col.code')) ?></dt><dd class="col-7 fw-semibold" data-field="code"></dd>
                        <dt class="col-5 text-muted"><?= h(t('parcels.col.mouza')) ?></dt><dd class="col-7" data-field="mouza"></dd>
                        <dt class="col-5 text-muted"><?= h(t('parcels.col.district')) ?></dt><dd class="col-7" data-field="district"></dd>
                        <dt class="col-5 text-muted"><?= h(t('parcels.col.dag')) ?></dt><dd class="col-7" data-field="dag"></dd>
                        <dt class="col-5 text-muted"><?= h(t('parcels.col.khatian')) ?></dt><dd class="col-7" data-field="khatian"></dd>
                        <dt class="col-5 text-muted"><?= h(t('parcels.col.area')) ?></dt><dd class="col-7" data-field="area"></dd>
                        <dt class="col-5 text-muted"><?= h(t('parcels.col.your_share')) ?></dt><dd class="col-7" data-field="share"></dd>
                    </dl>
                </div>
                <p id="parcel-preview-empty" class="text-muted small mb-0"><?= h(t('transfer_new.preview_empty')) ?></p>
            </div>
        </div>
    </div>
</main>

<script>
    (function () {
        var sel = document.getElementById('parcel-select');
        if (!sel) return;
        var preview = document.getElementById('parcel-preview');
        var empty   = document.getElementById('parcel-preview-empty');

        function update() {
            var opt = sel.options[sel.selectedIndex];
            if (!opt || !opt.value) {
                preview.classList.add('d-none');
                empty.classList.remove('d-none');
                return;
            }
            preview.classList.remove('d-none');
            empty.classList.add('d-none');
            ['code', 'mouza', 'district', 'dag', 'khatian', 'area', 'share'].forEach(function (k) {
                var el = preview.querySelector('[data-field="' + k + '"]');
                if (el) el.textContent = opt.dataset[k] || '';
            });
        }
        sel.addEventListener('change', update);
        update();
    })();
</script>