/**
 * assets/js/dashboard.js
 * Global UI interactions for AdHub
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Mobile sidebar toggle ──────────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // ── Table search filter ────────────────────────────────────
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll('.searchable-row');
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }

    // ── Auto-dismiss alerts ────────────────────────────────────
    setTimeout(() => {
        document.querySelectorAll('.alert-auto-dismiss').forEach(el => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            bsAlert.close();
        });
    }, 4000);

    // ── Delete confirm ─────────────────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });

    // ── Progress bar animation on load ────────────────────────
    document.querySelectorAll('.progress-fill[data-width]').forEach(bar => {
        setTimeout(() => { bar.style.width = bar.dataset.width + '%'; }, 100);
    });

});