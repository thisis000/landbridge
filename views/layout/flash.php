<?php
$flashMap = [
    'logged_in'         => ['success', 'flash.logged_in'],
    'logged_out'        => ['success', 'flash.logged_out'],
    'registered'        => ['success', 'flash.registered'],
    'saved'             => ['success', 'flash.saved'],
    'transfer_created'  => ['success', 'flash.transfer_created'],
    'correction_created'=> ['success', 'flash.correction_created'],
    'action_completed'  => ['success', 'flash.action_completed'],
    'record_updated'    => ['success', 'flash.record_updated'],
    'unauthorized'      => ['warning', 'flash.unauthorized'],
    'invalid_csrf'      => ['danger',  'flash.invalid_csrf'],
    'not_found'         => ['danger',  'flash.not_found'],
    'not_owner'         => ['danger',  'flash.not_owner'],
    'invalid_input'     => ['danger',  'flash.invalid_input'],
    'share_exceeds'     => ['danger',  'flash.share_exceeds'],
    'transaction_failed'=> ['danger',  'flash.transaction_failed'],
    'bad_credentials'   => ['danger',  'flash.bad_credentials'],
    'notes_required'    => ['warning', 'flash.notes_required'],
    'parcel_required'   => ['warning', 'flash.parcel_required'],
    'email_taken'       => ['danger',  'flash.email_taken'],
];

$noticeKey = $_GET['notice'] ?? null;
if ($noticeKey && isset($flashMap[$noticeKey])) {
    [$type, $msgKey] = $flashMap[$noticeKey];
    ?>
    <div class="container mt-3 lb-flash">
        <div class="alert alert-<?= h($type) ?> alert-dismissible fade show" role="alert">
            <?= h(t($msgKey)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php
}