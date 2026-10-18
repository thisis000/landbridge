<?php
$pageTitle = t('login.title');

if (is_logged_in()) {
    redirect('index.php?page=dashboard');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors[] = t('flash.invalid_input');
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            $return = (string)($_GET['return'] ?? '');
            if ($return !== '' && str_starts_with($return, '/') && !str_starts_with($return, '//')) {
                redirect($return);
            }
            redirect('index.php?page=dashboard&notice=logged_in');
        }
        $errors[] = t('flash.bad_credentials');
    }
}
?>
<main class="container py-5">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-5">
            <div class="lb-card-lg">
                <h1 class="h4 fw-bold mb-1"><?= h(t('login.title')) ?></h1>
                <p class="text-muted mb-4"><?= h(t('login.subtitle')) ?></p>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><?= h($err) ?></div>
                <?php endforeach; ?>

                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label"><?= h(t('form.email')) ?></label>
                        <input type="email" name="email" class="form-control" value="<?= h($email) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><?= h(t('form.password')) ?></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100 btn-lg" type="submit"><?= h(t('nav.login')) ?></button>
                </form>

                <p class="text-center text-muted small mt-3 mb-0">
                    <?= h(t('login.no_account')) ?> <a href="index.php?page=register"><?= h(t('login.go_register')) ?></a>
                </p>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="lb-card-lg">
                <h2 class="h6 fw-bold mb-1"><i class="bi bi-key me-1"></i><?= h(t('login.demo_title')) ?></h2>
                <p class="text-muted small mb-3"><?= h(t('login.demo_hint')) ?></p>
                <div class="lb-demo-table">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr><th><?= h(t('form.email')) ?></th><th><?= h(t('form.password')) ?></th><th><?= h(t('profile.role')) ?></th></tr>
                        </thead>
                        <tbody class="small">
                            <?php
                            $demo = [
                                ['rahim@example.com', 'citizen123', 'citizen'],
                                ['karim@example.com', 'citizen123', 'citizen'],
                                ['joya@example.com', 'citizen123', 'citizen'],
                                ['nasir@example.com', 'citizen123', 'citizen'],
                                ['reg@example.com', 'reg123', 'registration_officer'],
                                ['land@example.com', 'land123', 'land_office_officer'],
                                ['admin@example.com', 'admin123', 'administrator'],
                            ];
                            foreach ($demo as [$e, $p, $r]): ?>
                                <tr>
                                    <td class="font-monospace"><?= h($e) ?></td>
                                    <td class="font-monospace"><?= h($p) ?></td>
                                    <td><?= role_badge($r) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>