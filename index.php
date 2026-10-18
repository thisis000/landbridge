<?php
/**
 * LandBridge single entry point.
 */
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/i18n.php';
require_once __DIR__ . '/includes/auth.php';

// Language switch: ?lang=bn|en → set cookie/session and redirect to clean URL
if (isset($_GET['lang'])) {
    set_lang((string)$_GET['lang']);
    $params = $_GET;
    unset($params['lang']);
    $qs = http_build_query($params);
    redirect('index.php' . ($qs !== '' ? '?' . $qs : ''));
}

$__translations = [
    'en' => require __DIR__ . '/includes/lang/en.php',
    'bn' => require __DIR__ . '/includes/lang/bn.php',
];

$page = (string)($_GET['page'] ?? 'home');

// [view file, required roles] — null = public, 'any' = any authenticated user, array = specific roles
$routes = [
    'home'            => ['home.php',           null],
    'login'           => ['login.php',          null],
    'register'        => ['register.php',       null],
    'logout'          => ['logout.php',         null],

    'dashboard'       => ['dashboard.php',      'any'],
    'profile'         => ['profile.php',        'any'],
    'transfers'       => ['transfers.php',      'any'],
    'transfer'        => ['transfer.php',       'any'],

    'parcels'         => ['parcels.php',        ['citizen']],
    'land-profile'    => ['land-profile.php',   ['citizen']],
    'parcel'          => ['parcel.php',         ['citizen']],
    'transfer-new'    => ['transfer-new.php',   ['citizen']],
    'corrections'     => ['corrections.php',    ['citizen']],

    'transfer-action' => ['transfer-action.php',['registration_officer', 'land_office_officer', 'administrator']],
    'reg-queue'       => ['reg-queue.php',      ['registration_officer', 'administrator']],
    'lo-queue'        => ['lo-queue.php',       ['land_office_officer', 'administrator']],
];

if (!isset($routes[$page])) {
    $page = 'home';
}

[$viewFile, $requiredRoles] = $routes[$page];

// Enforce access
if ($requiredRoles === 'any') {
    require_login();
} elseif (is_array($requiredRoles)) {
    require_role(...$requiredRoles);
}

$viewPath = __DIR__ . '/views/' . $viewFile;
if (!file_exists($viewPath)) {
    http_response_code(404);
    exit('Not found');
}

// Pages that bypass the standard layout (redirect-only handlers)
if ($page === 'logout' || $page === 'transfer-action') {
    require $viewPath;
    exit;
}

// Standard layout wrapper
$pageTitle = '';    // views may override
ob_start();
require $viewPath;
$viewContent = ob_get_clean();

require __DIR__ . '/views/layout/header.php';
echo $viewContent;
require __DIR__ . '/views/layout/footer.php';