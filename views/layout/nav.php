<?php
$u = current_user();
$role = $u['role'] ?? null;
?>
<nav class="navbar navbar-expand-lg lb-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php?page=home">
            <span class="lb-brand-mark"><i class="bi bi-geo-alt-fill"></i></span>
            <span><?= h(t('site.name')) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($u): ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=dashboard"><?= h(t('nav.dashboard')) ?></a></li>
                    <?php if ($role === 'citizen'): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=land-profile"><?= h(t('nav.land_profile')) ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=parcels"><?= h(t('nav.parcels')) ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=transfers"><?= h(t('nav.transfers')) ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=corrections"><?= h(t('nav.corrections')) ?></a></li>
                    <?php elseif ($role === 'registration_officer'): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=reg-queue"><?= h(t('nav.reg_queue')) ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=transfers"><?= h(t('nav.transfers')) ?></a></li>
                    <?php elseif ($role === 'land_office_officer'): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=lo-queue"><?= h(t('nav.lo_queue')) ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=transfers"><?= h(t('nav.transfers')) ?></a></li>
                    <?php elseif ($role === 'administrator'): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=reg-queue"><?= h(t('nav.reg_queue')) ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=lo-queue"><?= h(t('nav.lo_queue')) ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=transfers"><?= h(t('nav.transfers')) ?></a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=home#workflow"><?= h(t('home.hero.cta_how_it_works')) ?></a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-globe2 me-1"></i><?= h(t('site.switch_lang')) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item <?= current_lang()==='bn'?'active':'' ?>" href="<?= h(lang_link('bn')) ?>">বাংলা</a></li>
                        <li><a class="dropdown-item <?= current_lang()==='en'?'active':'' ?>" href="<?= h(lang_link('en')) ?>">English</a></li>
                    </ul>
                </li>
                <?php if ($u): ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=profile"><i class="bi bi-person-circle me-1"></i><?= h($u['name']) ?></a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="index.php?page=logout"><i class="bi bi-box-arrow-right me-1"></i><?= h(t('nav.logout')) ?></a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="index.php?page=login"><?= h(t('nav.login')) ?></a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-sm px-3 ms-lg-2" href="index.php?page=register"><?= h(t('nav.register')) ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>