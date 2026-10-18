<?php
/**
 * Authentication, CSRF, and role helpers.
 */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function has_role(string ...$roles): bool
{
    $u = current_user();
    return $u !== null && in_array($u['role'], $roles, true);
}

function require_login(): void
{
    if (!is_logged_in()) {
        $return = $_SERVER['REQUEST_URI'] ?? '/';
        redirect('index.php?page=login&return=' . urlencode($return));
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!has_role(...$roles)) {
        redirect('index.php?page=dashboard&notice=unauthorized');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        redirect('index.php?page=dashboard&notice=invalid_csrf');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'             => (int)$user['id'],
        'name'           => $user['name'],
        'email'          => $user['email'],
        'role'           => $user['role'],
        'land_person_id' => $user['land_person_id'],
    ];
    // Rotate CSRF token on privilege change.
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function refresh_session_user(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare("SELECT id, name, email, role, land_person_id FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['user'] = [
            'id'             => (int)$u['id'],
            'name'           => $u['name'],
            'email'          => $u['email'],
            'role'           => $u['role'],
            'land_person_id' => $u['land_person_id'],
        ];
    }
}

function logout_user(): void
{
    // Keep language cookie intact; only destroy session.
    $lang = current_lang();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    // Restart session so we can pass a flash through the URL only — nothing needed.
    // (Language continues to be served from the cookie.)
}