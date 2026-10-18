<?php
$pageTitle = t('register.title');

if (is_logged_in()) {
    redirect('index.php?page=dashboard');
}

$errors = [];
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name    = trim((string)($_POST['name'] ?? ''));
    $email   = trim((string)($_POST['email'] ?? ''));
    $phone   = trim((string)($_POST['phone'] ?? ''));
    $pass1   = (string)($_POST['password'] ?? '');
    $pass2   = (string)($_POST['password_confirm'] ?? '');

    if ($name === '' || $email === '' || $pass1 === '' || $pass2 === '') {
        $errors[] = t('flash.invalid_input');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('flash.invalid_input');
    }
    if ($pass1 !== '' && $pass1 !== $pass2) {
        $errors[] = t('flash.invalid_input');
    }

    if (!$errors) {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        if ($stmt->fetchColumn()) {
            $errors[] = t('flash.email_taken');
        }
    }

    if (!$errors) {
        $pdo = db();
        $lpid = generate_lpid($pdo);
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (land_person_id, name, email, phone, password_hash, role, is_active)
                 VALUES (:l, :n, :e, :p, :h, 'citizen', 1)"
            );
            $stmt->execute([
                ':l' => $lpid,
                ':n' => $name,
                ':e' => $email,
                ':p' => $phone !== '' ? $phone : null,
                ':h' => $hash,
            ]);
            redirect('index.php?page=login&notice=registered');
        } catch (Throwable $ex) {
            $errors[] = t('flash.invalid_input');
        }
    }
}
?>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="lb-card-lg">
                <h1 class="h4 fw-bold mb-1"><?= h(t('register.title')) ?></h1>
                <p class="text-muted mb-4"><?= h(t('register.subtitle')) ?></p>

                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger py-2"><?= h($err) ?></div>
                <?php endforeach; ?>

                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label"><?= h(t('form.name')) ?></label>
                        <input type="text" name="name" class="form-control" value="<?= h($name) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= h(t('form.email')) ?></label>
                        <input type="email" name="email" class="form-control" value="<?= h($email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= h(t('form.phone')) ?> <span class="text-muted small"><?= h(t('common.optional')) ?></span></label>
                        <input type="text" name="phone" class="form-control" value="<?= h($phone) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= h(t('form.password')) ?></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><?= h(t('form.confirm_password')) ?></label>
                        <input type="password" name="password_confirm" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100 btn-lg" type="submit"><?= h(t('register.submit')) ?></button>
                </form>

                <p class="text-center text-muted small mt-3 mb-0">
                    <?= h(t('register.have_account')) ?> <a href="index.php?page=login"><?= h(t('register.go_login')) ?></a>
                </p>
            </div>
        </div>
    </div>
</main>