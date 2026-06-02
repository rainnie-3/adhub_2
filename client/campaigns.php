<?php
/**
 * client/campaigns.php
 * AdHub – Client View Campaigns
 */
define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('client');

$db = getDB();

// Fetch the client profile belonging to this logged-in user account
$clientStmt = $db->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
$clientStmt->execute([userId()]);
$client = $clientStmt->fetch();
$clientId = $client ? $client['id'] : 0;

// Fetch active campaigns assigned to this client profile
$campaignsStmt = $db->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM milestones m WHERE m.campaign_id = c.id) as total_milestones,
           (SELECT COUNT(*) FROM milestones m WHERE m.campaign_id = c.id AND m.status = 'Completed') as completed_milestones
    FROM campaigns c
    WHERE c.client_id = ?
    ORDER BY c.created_at DESC
");
$campaignsStmt->execute([$clientId]);
$campaigns = $campaignsStmt->fetchAll();

$pageTitle  = 'My Campaigns';
$activePage = 'campaigns';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">My Campaigns</h1>
            <p class="page-subtitle">Track your marketing progress and allocations</p>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($campaigns)): ?>
            <div class="col-12">
                <div class="card-adhub text-center py-5">
                    <i class="bi bi-megaphone text-muted display-4 mb-3"></i>
                    <p class="text-secondary mb-0">No active campaigns found at the moment.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($campaigns as $c): 
                $statusClass = match($c['status']) {
                    'Active' => 'bg-success',
                    'Under Review' => 'bg-warning text-dark',
                    'Approved' => 'bg-info text-dark',
                    'Completed' => 'bg-primary',
                    default => 'bg-secondary'
                };
                
                $pct = $c['total_milestones'] > 0 ? round(($c['completed_milestones'] / $c['total_milestones']) * 100) : 0;
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="card-adhub h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge <?= $statusClass ?>"><?= $c['status'] ?></span>
                            <span class="text-muted small font-monospace">₱<?= number_format($c['budget'], 2) ?></span>
                        </div>
                        <h3 class="h5 mb-2"><strong><?= htmlspecialchars($c['title']) ?></strong></h3>
                        <p class="text-secondary small mb-4"><?= htmlspecialchars($c['description'] ?? 'No description provided.') ?></p>
                    </div>
                    
                    <div>
                        <div class="d-flex justify-content-between text-muted small mb-1">
                            <span>Milestones</span>
                            <span><?= $pct ?>% (<?= $c['completed_milestones'] ?>/<?= $c['total_milestones'] ?>)</span>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light text-muted small">
                            <span><i class="bi bi-calendar3 me-1"></i> <?= $c['start_date'] ? date('M d, Y', strtotime($c['start_date'])) : '—' ?></span>
                            <span>至 <?= $c['end_date'] ? date('M d, Y', strtotime($c['end_date'])) : '—' ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>