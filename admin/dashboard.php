<?php
/**
 * admin/dashboard.php
 * AdHub – Admin Dashboard Overview
 */
define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$db = getDB();

// ── Stats ─────────────────────────────────────────────────────
$totalClients   = $db->query('SELECT COUNT(*) FROM clients')->fetchColumn();
$totalCampaigns = $db->query('SELECT COUNT(*) FROM campaigns')->fetchColumn();
$activeCampaigns= $db->query("SELECT COUNT(*) FROM campaigns WHERE status='Active'")->fetchColumn();
$totalBudget    = $db->query('SELECT SUM(budget) FROM campaigns')->fetchColumn() ?? 0;

// Total spent (sum of all time_log amounts)
$totalSpent     = $db->query('SELECT COALESCE(SUM(amount),0) FROM time_logs')->fetchColumn();

// Pending approvals
$pendingApproval= $db->query("SELECT COUNT(*) FROM milestones WHERE status='Under Review'")->fetchColumn();

// ── Recent Activity (latest 8 milestones with changes) ────────
$recentActivity = $db->query("
    SELECT m.title, m.status, m.updated_at, c.title AS campaign_title
    FROM milestones m
    JOIN campaigns c ON c.id = m.campaign_id
    ORDER BY m.updated_at DESC
    LIMIT 8
")->fetchAll();

// ── Upcoming Deadlines ────────────────────────────────────────
$deadlines = $db->query("
    SELECT m.title, m.due_date, c.title AS campaign_title
    FROM milestones m
    JOIN campaigns c ON c.id = m.campaign_id
    WHERE m.due_date >= CURDATE() AND m.status NOT IN ('Completed','Approved')
    ORDER BY m.due_date ASC
    LIMIT 6
")->fetchAll();

// ── Budget per client ─────────────────────────────────────────
$budgetRows = $db->query("
    SELECT cl.company_name,
           SUM(c.budget) AS total_budget,
           COALESCE(SUM(tl.amount),0) AS spent
    FROM clients cl
    LEFT JOIN campaigns c  ON c.client_id = cl.id
    LEFT JOIN time_logs tl ON tl.campaign_id = c.id
    GROUP BY cl.id
    ORDER BY total_budget DESC
    LIMIT 5
")->fetchAll();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Agency performance overview – <?= date('F j, Y') ?></p>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="bi bi-people"></i></div>
                <div class="stat-value"><?= $totalClients ?></div>
                <div class="stat-label">Total Clients</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="bi bi-megaphone"></i></div>
                <div class="stat-value"><?= $totalCampaigns ?></div>
                <div class="stat-label">Campaigns</div>
                <div class="stat-change up"><i class="bi bi-arrow-up-short"></i><?= $activeCampaigns ?> Active</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="bi bi-currency-dollar"></i></div>
                <div class="stat-value">₱<?= number_format($totalBudget/1000,0) ?>K</div>
                <div class="stat-label">Total Budget</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon icon-pink"><i class="bi bi-check2-circle"></i></div>
                <div class="stat-value"><?= $pendingApproval ?></div>
                <div class="stat-label">Pending Approvals</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Budget per Client -->
        <div class="col-12 col-lg-7">
            <div class="card-adhub h-100">
                <div class="card-title">Budget Utilisation by Client</div>
                <?php foreach ($budgetRows as $row):
                    $pct = $row['total_budget'] > 0 ? min(100, round($row['spent']/$row['total_budget']*100)) : 0;
                ?>
                <div class="budget-bar-row">
                    <span class="budget-label"><?= htmlspecialchars($row['company_name']) ?></span>
                    <div class="budget-bar-wrap">
                        <div class="progress-adhub">
                            <div class="progress-fill" data-width="<?= $pct ?>" style="width:0"></div>
                        </div>
                    </div>
                    <span class="budget-amount"><?= $pct ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Upcoming Deadlines -->
        <div class="col-12 col-lg-5">
            <div class="card-adhub h-100">
                <div class="card-title">Upcoming Deadlines</div>
                <?php if ($deadlines): ?>
                <?php foreach ($deadlines as $d): ?>
                <div class="deadline-item">
                    <div>
                        <div class="deadline-title"><?= htmlspecialchars($d['title']) ?></div>
                        <div class="deadline-campaign"><?= htmlspecialchars($d['campaign_title']) ?></div>
                    </div>
                    <div class="deadline-date"><i class="bi bi-calendar3 me-1"></i><?= date('M j', strtotime($d['due_date'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted-adhub small">No upcoming deadlines.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card-adhub">
        <div class="card-title">Recent Milestone Activity</div>
        <ul class="activity-feed">
            <?php foreach ($recentActivity as $act): ?>
            <li class="activity-item">
                <div class="activity-dot"></div>
                <div>
                    <div class="activity-text">
                        <strong><?= htmlspecialchars($act['title']) ?></strong>
                        marked as <span class="status-badge status-<?= strtolower(str_replace(' ','-',$act['status'])) ?>"><?= htmlspecialchars($act['status']) ?></span>
                        in <em><?= htmlspecialchars($act['campaign_title']) ?></em>
                    </div>
                    <div class="activity-time"><?= date('M j, Y g:i A', strtotime($act['updated_at'])) ?></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>