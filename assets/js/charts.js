/**
 * assets/js/charts.js
 * Renders all Chart.js visuals on the analytics page.
 * Data is injected via PHP into window.adhubChartData before this file loads.
 */

document.addEventListener('DOMContentLoaded', function () {
    const d = window.adhubChartData || {};

    const defaults = {
        color: '#9BA8CC',
        gridColor: 'rgba(42,51,84,.6)',
        fontFamily: "'Inter', sans-serif",
    };

    Chart.defaults.color           = defaults.color;
    Chart.defaults.font.family     = defaults.fontFamily;
    Chart.defaults.borderColor     = defaults.gridColor;
    Chart.defaults.plugins.legend.labels.color = defaults.color;

    // ── 1. Budget Utilisation – Doughnut ──────────────────────
    const budgetCtx = document.getElementById('chartBudget');
    if (budgetCtx && d.budget) {
        new Chart(budgetCtx, {
            type: 'doughnut',
            data: {
                labels: d.budget.labels,
                datasets: [{
                    data: d.budget.data,
                    backgroundColor: ['#4361EE','#2EC4B6','#F72585','#FF9F1C','#6B7FD4'],
                    borderWidth: 2,
                    borderColor: '#1C2233',
                }]
            },
            options: {
                cutout: '68%',
                plugins: { legend: { position: 'right' } },
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    }

    // ── 2. Campaign Status – Pie ───────────────────────────────
    const statusCtx = document.getElementById('chartCampaignStatus');
    if (statusCtx && d.status) {
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: d.status.labels,
                datasets: [{
                    data: d.status.data,
                    backgroundColor: ['#6B7FD4','#2EC4B6','#FF9F1C','#4361EE','#22d3b6'],
                    borderWidth: 2,
                    borderColor: '#1C2233',
                }]
            },
            options: {
                plugins: { legend: { position: 'bottom' } },
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    }

    // ── 3. Monthly Hours – Bar ────────────────────────────────
    const hoursCtx = document.getElementById('chartHours');
    if (hoursCtx && d.hours) {
        new Chart(hoursCtx, {
            type: 'bar',
            data: {
                labels: d.hours.labels,
                datasets: [{
                    label: 'Logged Hours',
                    data: d.hours.data,
                    backgroundColor: 'rgba(67,97,238,.7)',
                    borderColor: '#4361EE',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { color: defaults.gridColor } },
                    y: { grid: { color: defaults.gridColor }, beginAtZero: true }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // ── 4. Budget Trend – Line ────────────────────────────────
    const trendCtx = document.getElementById('chartBudgetTrend');
    if (trendCtx && d.trend) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: d.trend.labels,
                datasets: [{
                    label: 'Spending (₱)',
                    data: d.trend.data,
                    fill: true,
                    backgroundColor: 'rgba(67,97,238,.12)',
                    borderColor: '#4361EE',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: '#4361EE',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { color: defaults.gridColor } },
                    y: { grid: { color: defaults.gridColor }, beginAtZero: true }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});