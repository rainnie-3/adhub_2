<?php
/**
 * admin/reports.php
 * AdHub – Report Generation
 */
define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$db = getDB();

// ── Load campaign list for selector ──────────────────────────
$campaigns = $db->query("
    SELECT c.id, c.title, cl.company_name
    FROM campaigns c
    JOIN clients cl ON cl.id = c.client_id
    ORDER BY cl.company_name, c.title
")->fetchAll();

$selectedId = (int)($_GET['campaign'] ?? 0);
$report     = null;

if ($selectedId) {
    // Campaign
    $stmt = $db->prepare("
        SELECT c.*, cl.company_name, cl.contact_person, cl.email AS client_email,
               cl.retainer_budget,
               COALESCE(SUM(tl.amount),0) AS total_spent,
               cl.retainer_budget - COALESCE(SUM(tl.amount),0) AS balance
        FROM campaigns c
        JOIN clients cl ON cl.id = c.client_id
        LEFT JOIN time_logs tl ON tl.campaign_id = c.id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$selectedId]);
    $campaign = $stmt->fetch();

    if ($campaign) {
        // Milestones
        $ms = $db->prepare('SELECT * FROM milestones WHERE campaign_id=? ORDER BY due_date');
        $ms->execute([$selectedId]);
        $milestones = $ms->fetchAll();

        // Time logs
        $tl = $db->prepare("SELECT tl.*,u.name AS staff FROM time_logs tl LEFT JOIN users u ON u.id=tl.logged_by WHERE tl.campaign_id=? ORDER BY tl.log_date");
        $tl->execute([$selectedId]);
        $timeLogs = $tl->fetchAll();

        $report = compact('campaign','milestones','timeLogs');

        // Save report record
        $db->prepare('INSERT INTO reports (campaign_id,generated_by,title) VALUES (?,?,?)')->execute([
            $selectedId, userId(), 'Report: ' . $campaign['title'] . ' – ' . date('Y-m-d')
        ]);
    }
}

// Previous reports
$prevReports = $db->query("
    SELECT r.*, c.title AS campaign_title, u.name AS generated_by_name, cl.company_name
    FROM reports r
    JOIN campaigns c ON c.id = r.campaign_id
    JOIN clients cl  ON cl.id = c.client_id
    LEFT JOIN users u ON u.id = r.generated_by
    ORDER BY r.created_at DESC
    LIMIT 20
")->fetchAll();

$pageTitle  = 'Reports';
$activePage = 'reports';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-header no-print">
        <div>
            <h1 class="page-title">Reports</h1>
            <p class="page-subtitle">Generate and print campaign reports</p>
        </div>
        <?php if ($report): ?>
        <button onclick="window.print()" class="btn-primary-adhub no-print">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <?php endif; ?>
    </div>

    <!-- Campaign Selector -->
    <div class="card-adhub mb-4 no-print">
        <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
            <div style="flex:1;min-width:240px">
                <label class="form-label">Select Campaign</label>
                <select name="campaign" class="form-select" required>
                    <option value="">Choose a campaign to generate a report…</option>
                    <?php foreach ($campaigns as $cp): ?>
                    <option value="<?= $cp['id'] ?>" <?= $selectedId == $cp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cp['company_name'] . ' – ' . $cp['title']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary-adhub">Generate Report</button>
        </form>
    </div>

    <?php if ($report): ?>
    <?php $c = $report['campaign']; ?>
    <!-- ── PRINTABLE REPORT ── -->
    <div class="card-adhub report-body" style="max-width:860px;margin:0 auto">
        <!-- Header -->
        <div style="border-bottom:2px solid var(--primary);padding-bottom:1.25rem;margin-bottom:1.5rem">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem">
                <div>
                    <div style="font-family:'Poppins',sans-serif;font-size:1.5rem;font-weight:700;color:var(--primary)">AdHub</div>
                    <div style="font-size:.75rem;color:var(--text-muted)">Agency Campaign Report</div>
                </div>
                <div style="text-align:right;font-size:.8rem;color:var(--text-muted)">
                    Generated: <?= date('F j, Y') ?><br>
                    Prepared by: <?= htmlspecialchars(userName()) ?>
                </div>
            </div>
        </div>

        <h2 style="font-size:1.2rem;margin-bottom:.25rem"><?= htmlspecialchars($c['title']) ?></h2>
        <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1.5rem"><?= htmlspecialchars($c['description'] ?? '') ?></p>

        <!-- Summary Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:8px;padding:.8rem 1rem;text-align:center">
                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">Client</div>
                    <div style="font-weight:600;font-size:.9rem;margin-top:.2rem"><?= htmlspecialchars($c['company_name']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:8px;padding:.8rem 1rem;text-align:center">
                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">Status</div>
                    <div style="font-weight:600;font-size:.9rem;margin-top:.2rem"><?= htmlspecialchars($c['status']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:8px;padding:.8rem 1rem;text-align:center">
                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">Budget</div>
                    <div style="font-weight:600;font-size:.9rem;margin-top:.2rem">₱<?= number_format($c['budget'],2) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:8px;padding:.8rem 1rem;text-align:center">
                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">Balance</div>
                    <div style="font-weight:600;font-size:.9rem;margin-top:.2rem;color:var(--success)">₱<?= number_format(max(0,$c['balance']),2) ?></div>
                </div>
            </div>
        </div>

        <!-- Budget Summary -->
        <h5 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:.75rem">Budget Summary</h5>
        <table class="table-adhub mb-4">
            <thead><tr><th>Item</th><th>Amount</th></tr></thead>
            <tbody>
                <tr><td>Campaign Budget</td><td>₱<?= number_format($c['budget'],2) ?></td></tr>
                <tr><td>Total Spent (Logged Hours)</td><td>₱<?= number_format($c['total_spent'],2) ?></td></tr>
                <tr><td><strong>Remaining Balance</strong></td><td><strong style="color:var(--success)">₱<?= number_format(max(0,$c['balance']),2) ?></strong></td></tr>
            </tbody>
        </table>

        <!-- Milestones -->
        <h5 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:.75rem">Milestones</h5>
        <table class="table-adhub mb-4">
            <thead><tr><th>Milestone</th><th>Status</th><th>Due Date</th><th>Client Comment</th></tr></thead>
            <tbody>
            <?php foreach ($report['milestones'] as $ms): ?>
            <tr>
                <td><strong><?= htmlspecialchars($ms['title']) ?></strong><br><span style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars($ms['description']??'') ?></span></td>
                <td><span class="status-badge status-<?= strtolower(str_replace([' ','_'],'-',$ms['status'])) ?>"><?= $ms['status'] ?></span></td>
                <td><?= $ms['due_date'] ?></td>
                <td style="font-size:.8rem"><?= htmlspecialchars($ms['client_comment'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Time Logs -->
        <h5 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:.75rem">Time Logs</h5>
        <table class="table-adhub mb-2">
            <thead><tr><th>Date</th><th>Staff</th><th>Hours</th><th>Rate</th><th>Amount</th><th>Description</th></tr></thead>
            <tbody>
            <?php foreach ($report['timeLogs'] as $tl): ?>
            <tr>
                <td><?= $tl['log_date'] ?></td>
                <td><?= htmlspecialchars($tl['staff'] ?? '—') ?></td>
                <td><?= $tl['hours'] ?>h</td>
                <td>₱<?= number_format($tl['hourly_rate'],2) ?>/hr</td>
                <td>₱<?= number_format($tl['amount'],2) ?></td>
                <td style="font-size:.8rem"><?= htmlspecialchars($tl['description'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="text-align:right;font-size:.85rem;color:var(--text-muted);margin-top:.5rem">
            <strong>Total Logged: ₱<?= number_format($c['total_spent'],2) ?></strong>
        </div>

        <div style="border-top:1px solid var(--border);margin-top:2rem;padding-top:1rem;font-size:.75rem;color:var(--text-muted);text-align:center">
            Confidential – AdHub Agency-Client Campaign Report · Generated <?= date('F j, Y g:i A') ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report History -->
    <?php if ($prevReports && !$report): ?>
    <div class="card-adhub mt-4">
        <div class="card-title">Report History</div>
        <div class="table-responsive">
            <table class="table-adhub">
                <thead><tr><th>Report</th><th>Client</th><th>Generated By</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($prevReports as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['campaign_title']) ?></strong></td>
                    <td><?= htmlspecialchars($r['company_name']) ?></td>
                    <td><?= htmlspecialchars($r['generated_by_name'] ?? '—') ?></td>
                    <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                    <td><a href="?campaign=<?= $r['campaign_id'] ?>" class="btn-outline-adhub" style="padding:.3rem .7rem;font-size:.78rem">Re-generate</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>