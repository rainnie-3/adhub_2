<?php
/**
 * client/budget.php
 * AdHub – Client Retainer & Budget Breakdown
 */
define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('client');

$db = getDB();

$clientStmt = $db->prepare("SELECT * FROM clients WHERE user_id = ? LIMIT 1");
$clientStmt->execute([userId()]);
$client = $clientStmt->fetch();
$clientId = $client ? $client['id'] : 0;

// Fetch all allocations from campaigns tied to this client
$campaignsStmt = $db->prepare("SELECT title, budget, status FROM campaigns WHERE client_id = ?");
$campaignsStmt->execute([$clientId]);
$campaigns = $campaignsStmt->fetchAll();

$totalAllocated = 0;
foreach ($campaigns as $camp) {
    $totalAllocated += $camp['budget'];
}

$pageTitle  = 'Budget Overview';
$activePage = 'budget';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Financial Status</h1>
            <p class="page-subtitle">Review active investment structures and setup allocations</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card-adhub p-4" style="border-left: 4px solid #3498db;">
                <div class="text-uppercase tracking-wider small text-muted mb-1">Contract Retainer Budget</div>
                <h2 class="display-6 font-monospace mb-0">₱<?= number_format($client['retainer_budget'] ?? 0, 2) ?></h2>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-adhub p-4" style="border-left: 4px solid #e67e22;">
                <div class="text-uppercase tracking-wider small text-muted mb-1">Total Allocated Campaign Budgets</div>
                <h2 class="display-6 font-monospace mb-0">₱<?= number_format($totalAllocated, 2) ?></h2>
            </div>
        </div>
    </div>

    <div class="card-adhub">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0"><strong>Campaign Budget Breakdown</strong></h5>
        </div>
        <div class="table-responsive">
            <table class="table-adhub mb-0">
                <thead>
                    <tr>
                        <th>Campaign Title</th>
                        <th>Project Status</th>
                        <th class="text-end">Allocated Budget</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($campaigns)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">No tracked campaign metrics are currently live.</td></tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $camp): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($camp['title']) ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><?= $camp['status'] ?></span></td>
                        <td class="text-end font-monospace text-secondary">₱<?= number_format($camp['budget'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-light">
                        <td colspan="2" class="text-end"><strong>Total Assigned:</strong></td>
                        <td class="text-end font-monospace text-primary"><strong>₱<?= number_format($totalAllocated, 2) ?></strong></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div></div><?php include __DIR__ . '/../includes/footer.php'; ?>