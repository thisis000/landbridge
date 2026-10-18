<?php $pageTitle = t('site.name'); ?>

<section class="lb-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-success-subtle text-success-emphasis mb-3">
                    <i class="bi bi-stars me-1"></i><?= h(t('home.hero.badge')) ?>
                </span>
                <h1 class="display-4 fw-bold mb-3 lh-sm"><?= h(t('home.hero.title')) ?></h1>
                <p class="lead text-secondary mb-4"><?= h(t('home.hero.subtitle')) ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="index.php?page=register" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-person-plus me-1"></i><?= h(t('home.hero.cta_get_started')) ?>
                    </a>
                    <a href="#workflow" class="btn btn-outline-success btn-lg px-4">
                        <i class="bi bi-diagram-3 me-1"></i><?= h(t('home.hero.cta_how_it_works')) ?>
                    </a>
                </div>
                <div class="mt-4 small text-muted">
                    <i class="bi bi-info-circle me-1"></i><?= h(t('site.demo_notice')) ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="lb-hero-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="small text-muted"><?= h(t('home.hero.sample_case')) ?></span>
                        <?= status_badge('registration_review') ?>
                    </div>
                    <div class="lb-hero-case mb-3">TC-2025-0042</div>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted"><?= h(t('home.hero.sample_parcel')) ?></span>
                        <span class="fw-semibold">PB-1002 · Savar · Dag 451</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-3">
                        <span class="text-muted"><?= h(t('home.hero.sample_share')) ?></span>
                        <span class="fw-semibold">1/2</span>
                    </div>
                    <div class="lb-hero-progress">
                        <div class="lb-step done"></div>
                        <div class="lb-step done"></div>
                        <div class="lb-step active"></div>
                        <div class="lb-step"></div>
                        <div class="lb-step"></div>
                        <div class="lb-step"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<main class="container py-5">

    <!-- Problem -->
    <section class="mb-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold"><?= h(t('home.problem.title')) ?></h2>
            <p class="text-muted mb-0"><?= h(t('home.problem.subtitle')) ?></p>
        </div>
        <div class="row g-4">
            <?php for ($i = 1; $i <= 3; $i++): ?>
                <div class="col-md-4">
                    <div class="lb-card h-100">
                        <div class="lb-icon lb-icon-amber mb-3"><i class="bi bi-exclamation-triangle"></i></div>
                        <h5 class="fw-semibold"><?= h(t("home.problem.$i.title")) ?></h5>
                        <p class="text-muted mb-0"><?= h(t("home.problem.$i.desc")) ?></p>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Features -->
    <section class="mb-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold"><?= h(t('home.features.title')) ?></h2>
            <p class="text-muted mb-0"><?= h(t('home.features.subtitle')) ?></p>
        </div>
        <div class="row g-4">
            <?php
            $icons = ['bi-folder2-open', 'bi-arrow-left-right', 'bi-fraction', 'bi-shield-check'];
            for ($i = 1; $i <= 4; $i++):
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="lb-card h-100">
                        <div class="lb-icon mb-3"><i class="bi <?= h($icons[$i-1]) ?>"></i></div>
                        <h6 class="fw-semibold"><?= h(t("home.features.$i.title")) ?></h6>
                        <p class="text-muted mb-0 small"><?= h(t("home.features.$i.desc")) ?></p>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Workflow -->
    <section id="workflow" class="mb-5 pt-4">
        <div class="text-center mb-4">
            <h2 class="fw-bold"><?= h(t('home.workflow.title')) ?></h2>
            <p class="text-muted mb-0"><?= h(t('home.workflow.subtitle')) ?></p>
        </div>
        <div class="lb-stepper">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <div class="lb-stepper-item">
                    <div class="lb-stepper-dot"><?= $i ?></div>
                    <div class="small fw-semibold text-center mt-2"><?= h(t("home.workflow.step$i")) ?></div>
                </div>
                <?php if ($i < 6): ?><div class="lb-stepper-line"></div><?php endif; ?>
            <?php endfor; ?>
        </div>
    </section>

    <!-- About -->
    <section class="mb-5">
        <div class="lb-card-lg">
            <div class="row align-items-center g-4">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-3"><?= h(t('home.about.title')) ?></h2>
                    <p class="text-secondary mb-0"><?= h(t('home.about.text')) ?></p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="lb-badge-large">
                        <i class="bi bi-bank2"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="mb-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold"><?= h(t('home.faq.title')) ?></h2>
        </div>
        <div class="accordion" id="faqAccordion">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $i>1?'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                            <?= h(t("home.faq.q$i")) ?>
                        </button>
                    </h2>
                    <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i===1?'show':'' ?>" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary"><?= h(t("home.faq.a$i")) ?></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </section>

</main>