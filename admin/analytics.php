<?php
/**
 * admin/analytics.php
 * AdHub - Admin analytics overview.
 */
define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$db = getDB();
$range = $_GET['range'] ?? 'all';
$validRanges = ['all', '30', '90', '365'];
if (!in_array($range, $validRanges, true)) {
    $range = 'all';
}

$dateFilter = '';
$dateParams = [];
if ($range !== 'all') {
    $dateFilter = 'WHERE tl.log_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)';
    $dateParams[] = (int)$range;
}

$totalBudget = (float)($db->query('SELECT COALESCE(SUM(budget),0) FROM campaigns')->fetchColumn() ?: 0);
$totalSpent = (float)($db->query('SELECT COALESCE(SUM(amount),0) FROM time_logs')->fetchColumn() ?: 0);
$totalHours = (float)($db->query('SELECT COALESCE(SUM(hours),0) FROM time_logs')->fetchColumn() ?: 0);
$approvalRate = (float)($db->query("
    SELECT COALESCE(ROUND(SUM(status = 'Approved') / NULLIF(COUNT(*), 0) * 100), 0)
    FROM milestones
")->fetchColumn() ?: 0);

$budgetRows = $db->query("
    SELECT cl.company_name, COALESCE(SUM(c.budget),0) AS total_budget
    FROM clients cl
    LEFT JOIN campaigns c ON c.client_id = cl.id
    GROUP BY cl.id, cl.company_name
    ORDER BY total_budget DESC
    LIMIT 5
")->fetchAll();

$statusRows = $db->query("
    SELECT status, COUNT(*) AS total
    FROM campaigns
    GROUP BY status
    ORDER BY FIELD(status, 'Planning', 'Active', 'Under Review', 'Approved', 'Completed')
")->fetchAll();

$hoursStmt = $db->prepare("
    SELECT DATE_FORMAT(tl.log_date, '%b %Y') AS month_label,
           DATE_FORMAT(tl.log_date, '%Y-%m') AS month_key,
           COALESCE(SUM(tl.hours),0) AS hours
    FROM time_logs tl
    $dateFilter
    GROUP BY month_key, month_label
    ORDER BY month_key
");
$hoursStmt->execute($dateParams);
$hoursRows = $hoursStmt->fetchAll();

$trendStmt = $db->prepare("
    SELECT DATE_FORMAT(tl.log_date, '%b %Y') AS month_label,
           DATE_FORMAT(tl.log_date, '%Y-%m') AS month_key,
           COALESCE(SUM(tl.amount),0) AS amount
    FROM time_logs tl
    $dateFilter
    GROUP BY month_key, month_label
    ORDER BY month_key
");
$trendStmt->execute($dateParams);
$trendRows = $trendStmt->fetchAll();

$topCampaigns = $db->query("
    SELECT c.title, cl.company_name, c.budget, COALESCE(SUM(tl.amount),0) AS spent
    FROM campaigns c
    JOIN clients cl ON cl.id = c.client_id
    LEFT JOIN time_logs tl ON tl.campaign_id = c.id
    GROUP BY c.id, c.title, cl.company_name, c.budget
    ORDER BY spent DESC
    LIMIT 8
")->fetchAll();

$chartData = [
    'budget' => [
        'labels' => array_column($budgetRows, 'company_name'),
        'data' => array_map('floatval', array_column($budgetRows, 'total_budget')),
    ],
    'status' => [
        'labels' => array_column($statusRows, 'status'),
        'data' => array_map('intval', array_column($statusRows, 'total')),
    ],
    'hours' => [
        'labels' => array_column($hoursRows, 'month_label'),
        'data' => array_map('floatval', array_column($hoursRows, 'hours')),
    ],
    'trend' => [
        'labels' => array_column($trendRows, 'month_label'),
        'data' => array_map('floatval', array_column($trendRows, 'amount')),
    ],
];

$utilization = $totalBudget > 0 ? min(100, round(($totalSpent / $totalBudget) * 100)) : 0;

$pageTitle = 'Analytics';
$activePage = 'analytics';
$extraScripts = [
    BASE_URL . '/assets/js/charts.js',
    BASE_URL . '/assets/js/analytics.js',
];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Analytics</h1>
            <p class="page-subtitle">Campaign performance, budget utilisation, and delivery trends</p>
        </div>
        <div style="min-width:180px">
            <label class="form-label" for="analyticsRange">Time Range</label>
            <select id="analyticsRange" class="form-select">
                <option value="all" <?= $range === 'all' ? 'selected' : '' ?>>All time</option>
                <option value="30" <?= $range === '30' ? 'selected' : '' ?>>Last 30 days</option>
                <option value="90" <?= $range === '90' ? 'selected' : '' ?>>Last 90 days</option>
                <option value="365" <?= $range === '365' ? 'selected' : '' ?>>Last 12 months</option>
            </select>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="bi bi-wallet2"></i></div>
                <div class="stat-value">PHP <?= number_format($totalBudget / 1000, 0) ?>K</div>
                <div class="stat-label">Total Budget</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="stat-value">PHP <?= number_format($totalSpent / 1000, 0) ?>K</div>
                <div class="stat-label">Logged Spend</div>
                <div class="stat-change up"><i class="bi bi-pie-chart"></i><?= $utilization ?>% utilised</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="bi bi-clock-history"></i></div>
                <div class="stat-value"><?= number_format($totalHours, 1) ?></div>
                <div class="stat-label">Logged Hours</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon icon-pink"><i class="bi bi-check2-circle"></i></div>
                <div class="stat-value"><?= number_format($approvalRate, 0) ?>%</div>
                <div class="stat-label">Milestone Approval Rate</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="chart-container h-100">
                <div class="chart-title">Budget by Client</div>
                <div style="height:280px"><canvas id="chartBudget"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="chart-container h-100">
                <div class="chart-title">Campaign Status Mix</div>
                <div style="height:280px"><canvas id="chartCampaignStatus"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="chart-container h-100">
                <div class="chart-title">Logged Hours by Month</div>
                <div style="height:280px"><canvas id="chartHours"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="chart-container h-100">
                <div class="chart-title">Spend Trend</div>
                <div style="height:280px"><canvas id="chartBudgetTrend"></canvas></div>
            </div>
        </div>
    </div>

    <div class="card-adhub">
        <div class="card-title">Top Campaign Spend</div>
        <div class="table-responsive">
            <table class="table-adhub">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Client</th>
                        <th class="text-end">Budget</th>
                        <th class="text-end">Spent</th>
                        <th>Utilisation</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($topCampaigns)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No campaign spend has been logged yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($topCampaigns as $campaign):
                        $pct = $campaign['budget'] > 0 ? min(100, round(($campaign['spent'] / $campaign['budget']) * 100)) : 0;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($campaign['title']) ?></strong></td>
                        <td><?= htmlspecialchars($campaign['company_name']) ?></td>
                        <td class="text-end font-monospace">PHP <?= number_format($campaign['budget'], 2) ?></td>
                        <td class="text-end font-monospace">PHP <?= number_format($campaign['spent'], 2) ?></td>
                        <td style="min-width:160px">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress-adhub flex-grow-1">
                                    <div class="progress-fill" data-width="<?= $pct ?>" style="width:0"></div>
                                </div>
                                <span class="text-muted small"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        window.adhubChartData = <?= json_encode($chartData, JSON_UNESCAPED_SLASHES) ?>;
    </script>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
