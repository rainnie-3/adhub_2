/**
 * assets/js/analytics.js
 * Additional analytics page interactions (delegated to charts.js for rendering).
 */
document.addEventListener('DOMContentLoaded', function () {
    // Date-range filter (UI only, full implementation would submit via AJAX)
    const rangeSelect = document.getElementById('analyticsRange');
    if (rangeSelect) {
        rangeSelect.addEventListener('change', function () {
            // Refresh page with new range parameter
            const url = new URL(window.location.href);
            url.searchParams.set('range', this.value);
            window.location.href = url.toString();
        });
    }
});