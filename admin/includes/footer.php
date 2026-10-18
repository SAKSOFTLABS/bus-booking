<?php
/**
 * Admin Portal Footer Layout
 */
?>
        </main>
        <footer class="bg-white border-top py-3 px-4 text-muted small text-center text-md-start d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> Admin Management System.
            </div>
            <div>
                <span class="badge bg-light text-dark border">PHP 8.2 • MySQL • Bootstrap 5</span>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Admin JS -->
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
