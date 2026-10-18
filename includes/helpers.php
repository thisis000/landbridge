<?php
/**
 * Small utility helpers: escaping, redirects, share math, IDs, badges.
 */

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Build a URL relative to current script, merging current GET params. */
function url_with(array $params = [], ?string $path = null): string
{
    $base    = $path ?? $_SERVER['SCRIPT_NAME'];
    $current = $_GET;
    foreach ($params as $k => $v) {
        if ($v === null) {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    $qs = http_build_query($current);
    return $base . ($qs !== '' ? '?' . $qs : '');
}

/** Greatest common divisor (positive). */
function gcd(int $a, int $b): int
{
    $a = abs($a);
    $b = abs($b);
    while ($b !== 0) {
        [$a, $b] = [$b, $a % $b];
    }
    return $a === 0 ? 1 : $a;
}

/** Simplify [num, den] with GCD. */
function simplify_fraction(int $num, int $den): array
{
    if ($den === 0) {
        return [0, 1];
    }
    if ($num === 0) {
        return [0, 1];
    }
    $g = gcd($num, $den);
    return [intdiv($num, $g), intdiv($den, $g)];
}

/** Compute adjusted area in decimal units. */
function adjusted_area(float $areaDecimal, int $num, int $den): float
{
    if ($den <= 0) {
        return 0.0;
    }
    return $areaDecimal * $num / $den;
}

/** Format share as fraction string. */
function share_fraction(int $num, int $den): string
{
    return $num . '/' . $den;
}

/** Status badge HTML. */
function status_badge(string $status): string
{
    $map = [
        'submitted'           => 'info',
        'identity_verified'   => 'primary',
        'registration_review' => 'warning',
        'land_office_review'  => 'warning',
        'approved'            => 'success',
        'record_updated'      => 'success',
        'rejected'            => 'danger',
        'needs_correction'    => 'warning',
        'pending'             => 'warning',
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge rounded-pill bg-' . $color . '">' . h(t('status.' . $status)) . '</span>';
}

function role_badge(string $role): string
{
    $map = [
        'citizen'              => 'secondary',
        'registration_officer' => 'info',
        'land_office_officer'  => 'primary',
        'administrator'        => 'dark',
    ];
    $color = $map[$role] ?? 'secondary';
    return '<span class="badge rounded-pill bg-' . $color . '">' . h(t('role.' . $role)) . '</span>';
}

function share_badge(string $shareType): string
{
    $color = $shareType === 'sole' ? 'success' : 'info';
    return '<span class="badge rounded-pill bg-' . $color . '">' . h(t('share.' . $shareType)) . '</span>';
}

function doc_badge(string $type): string
{
    $map = [
        'mutation_khatian'   => 'success',
        'record_of_rights'   => 'info',
        'survey_map'         => 'warning',
        'deed'               => 'primary',
        'other'              => 'secondary',
    ];
    $color = $map[$type] ?? 'secondary';
    return '<span class="badge rounded-pill bg-' . $color . '">' . h(t('doc.' . $type)) . '</span>';
}

function event_badge(string $type): string
{
    $map = [
        'submitted'              => 'info',
        'identity_verified'      => 'primary',
        'registration_reviewed'  => 'warning',
        'land_office_reviewed'   => 'warning',
        'approved'               => 'success',
        'record_updated'         => 'success',
        'rejected'               => 'danger',
        'correction_requested'   => 'warning',
    ];
    $color = $map[$type] ?? 'secondary';
    return '<span class="badge rounded-pill bg-' . $color . '">' . h(t('event.' . $type)) . '</span>';
}

function area_display(float $value, string $unit): string
{
    return number_format($value, 3) . ' ' . t('unit.' . $unit);
}

/**
 * Officer action matrix. Returns allowed actions for role × status.
 */
function allowed_actions(string $role, string $status): array
{
    static $matrix = [
        'registration_officer' => [
            'submitted'           => ['verify_identity', 'request_correction', 'reject'],
            'identity_verified'   => ['approve_registration', 'request_correction', 'reject'],
            'registration_review' => ['request_correction', 'reject'],
            'needs_correction'    => ['approve_registration', 'reject'],
        ],
        'land_office_officer' => [
            'registration_review' => ['approve_land_office', 'request_correction', 'reject'],
            'land_office_review'  => ['approve_land_office', 'request_correction', 'reject'],
            'needs_correction'    => ['approve_land_office', 'request_correction', 'reject'],
        ],
        'administrator' => [
            'submitted'           => ['verify_identity', 'approve_registration', 'request_correction', 'reject'],
            'identity_verified'   => ['approve_registration', 'request_correction', 'reject'],
            'registration_review' => ['approve_registration', 'approve_land_office', 'request_correction', 'reject'],
            'land_office_review'  => ['approve_land_office', 'request_correction', 'reject'],
            'needs_correction'    => ['approve_registration', 'approve_land_office', 'request_correction', 'reject'],
            'approved'            => ['update_record'],
        ],
    ];
    return $matrix[$role][$status] ?? [];
}

/** Compute the status an officer action moves the case to. */
function action_new_status(string $action, string $current): ?string
{
    switch ($action) {
        case 'verify_identity':
            return $current === 'submitted' ? 'identity_verified' : null;
        case 'approve_registration':
            return in_array($current, ['identity_verified', 'submitted', 'needs_correction'], true)
                ? 'registration_review'
                : null;
        case 'approve_land_office':
            if ($current === 'registration_review') return 'land_office_review';
            if ($current === 'land_office_review')  return 'approved';
            if ($current === 'needs_correction')    return 'land_office_review';
            return null;
        case 'request_correction':
            return in_array($current, ['submitted', 'identity_verified', 'registration_review', 'land_office_review', 'needs_correction'], true)
                ? 'needs_correction'
                : null;
        case 'reject':
            return in_array($current, ['submitted', 'identity_verified', 'registration_review', 'land_office_review', 'needs_correction'], true)
                ? 'rejected'
                : null;
        case 'update_record':
            return $current === 'approved' ? 'record_updated' : null;
    }
    return null;
}

/** Map a case status to the corresponding transfer_events.event_type. */
function status_event_type(string $newStatus): string
{
    $map = [
        'submitted'           => 'submitted',
        'identity_verified'   => 'identity_verified',
        'registration_review' => 'registration_reviewed',
        'land_office_review'  => 'land_office_reviewed',
        'approved'            => 'approved',
        'record_updated'      => 'record_updated',
        'rejected'            => 'rejected',
        'needs_correction'    => 'correction_requested',
    ];
    return $map[$newStatus] ?? 'submitted';
}

/** Generate a fresh TC-YYYY-NNNN case number (retries on collision). */
function generate_case_number(PDO $pdo): string
{
    $year   = date('Y');
    $prefix = 'TC-' . $year . '-';
    for ($attempt = 0; $attempt < 25; $attempt++) {
        $stmt = $pdo->prepare("SELECT case_number FROM transfer_cases WHERE case_number LIKE :p ORDER BY case_number DESC LIMIT 1");
        $stmt->execute([':p' => $prefix . '%']);
        $last = $stmt->fetchColumn();
        $next = 1;
        if ($last) {
            $next = (int)substr((string)$last, strlen($prefix)) + 1;
        }
        $candidate = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
        $check = $pdo->prepare("SELECT 1 FROM transfer_cases WHERE case_number = :c");
        $check->execute([':c' => $candidate]);
        if (!$check->fetchColumn()) {
            return $candidate;
        }
    }
    throw new RuntimeException('Could not allocate a case number');
}

/** Generate a unique LP-XXXXX land person ID. */
function generate_lpid(PDO $pdo): string
{
    for ($i = 0; $i < 50; $i++) {
        $n    = random_int(10000, 99999);
        $lpid = 'LP-' . $n;
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE land_person_id = :l");
        $stmt->execute([':l' => $lpid]);
        if (!$stmt->fetchColumn()) {
            return $lpid;
        }
    }
    // Deterministic fallback
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(land_person_id, 4) AS UNSIGNED)) FROM users WHERE land_person_id LIKE 'LP-%'");
    $max = (int)$stmt->fetchColumn();
    $n   = $max + 1;
    while (true) {
        $lpid = 'LP-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE land_person_id = :l");
        $stmt->execute([':l' => $lpid]);
        if (!$stmt->fetchColumn()) {
            return $lpid;
        }
        $n++;
    }
}