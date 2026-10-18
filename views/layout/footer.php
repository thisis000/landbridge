<footer class="lb-footer mt-auto">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 fw-bold mb-2">
                    <span class="lb-brand-mark"><i class="bi bi-geo-alt-fill"></i></span>
                    <span><?= h(t('site.name')) ?></span>
                </div>
                <p class="text-muted mb-2 small"><?= h(t('footer.about')) ?></p>
                <span class="badge bg-warning-subtle text-warning-emphasis"><?= h(t('footer.demo_notice')) ?></span>
            </div>
            <div class="col-md-3">
                <h6 class="fw-semibold mb-2"><?= h(t('footer.links')) ?></h6>
                <ul class="list-unstyled small mb-0">
                    <li><a class="link-secondary" href="index.php?page=home"><?= h(t('nav.home')) ?></a></li>
                    <li><a class="link-secondary" href="index.php?page=login"><?= h(t('nav.login')) ?></a></li>
                    <li><a class="link-secondary" href="index.php?page=register"><?= h(t('nav.register')) ?></a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="fw-semibold mb-2"><?= h(t('site.switch_lang')) ?></h6>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-secondary <?= current_lang()==='bn'?'active':'' ?>" href="<?= h(lang_link('bn')) ?>">বাংলা</a>
                    <a class="btn btn-sm btn-outline-secondary <?= current_lang()==='en'?'active':'' ?>" href="<?= h(lang_link('en')) ?>">English</a>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="small text-muted text-center"><?= h(t('footer.copyright', ['year' => date('Y')])) ?></div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss flash notices after 5s
    document.querySelectorAll('.lb-flash .alert').forEach(function (el) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                bootstrap.Alert.getOrCreateInstance(el).close();
            }
        }, 5000);
    });
</script>
</body>
</html>