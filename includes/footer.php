<?php
/**
 * includes/footer.php
 * Closes the layout wrapper, loads JS bundles.
 */
?>
    </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<!-- Bootstrap 5 Bundle (Popper included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js (loaded globally; pages that need it will use it) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- Global dashboard JS -->
<script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
<?php if (isset($extraScripts)): ?>
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= htmlspecialchars($src) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>