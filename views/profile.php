<?php
require_login();
$user = current_user();
$pdo  = db();

$pageTitle = t('profile.title');

// Fetch fresh user row
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user['id']]);
$row = $stmt->fetch();

$errors = [];
$name  = $row['name'];
$email = $row['email'];
$phone = (string)($row['phone'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name  = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));

    if ($name === '' || $email === '') {
        $errors[] = t('flash.invalid_input');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('flash.invalid_input');
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = :e AND id <> :i LIMIT 1");
        $stmt->execute([':e' => $email, ':i' => $user['id']]);
        if ($stmt->fetchColumn()) {
            $errors[] = t('flash.email_taken');
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE users SET name = :n, email = :e, phone = :p WHERE id = :i");
        $stmt->execute([
            ':n' => $name,
            ':e' => $email,
            ':p' => $phone !== '' ? $phone : null,
            ':i' => $user['id'],
        ]);
        refresh_session_user($pdo, $user['id']);
        redirect('index.php?page=profile&notice=saved');
    }
}
?>
<main class="container py-4">
    <h1 class="h3 fw-bold mb-4"><?= h(t('profile.title')) ?></h1>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="lb-card-lg h-100">
                <h2 class="h6 fw-bold mb-3"><?= h(t('profile.readonly')) ?></h2>
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted"><?= h(t('profile.land_person_id')) ?></dt>
                    <dd class="col-6 font-monospace fw-semibold"><?= h($row['land_person_id']) ?></dd>
                    <dt class="col-6 text-muted"><?= h(t('profile.role')) ?></dt>
                    <dd class="col-6"><?= role_badge($row['role']) ?></dd>
                    <dt class="col-6 text-muted"><?= h(t('common.date')) ?></dt>
                    <dd class="col-6"><?= h(date('Y-m-d', strtotime($row['created_at']))) ?></dd>
                </dl>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="lb-card-lg">
                <h2 class="h6 fw-bold mb-3"><?= h(t('profile.edit')) ?></h2>

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
                    <div class="mb-4">
                        <label class="form-label"><?= h(t('form.phone')) ?> <span class="text-muted small"><?= h(t('common.optional')) ?></span></label>
                        <input type="text" name="phone" class="form-control" value="<?= h($phone) ?>">
                    </div>
                    <button class="btn btn-primary" type="submit"><?= h(t('common.save')) ?></button>
                </form>
            </div>
        </div>
    </div>
</main>