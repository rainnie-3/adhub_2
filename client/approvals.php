<?php

define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('client');

$db = getDB();
$msg = '';

$clientStmt = $db->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
$clientStmt->execute([userId()]);
$clientId = $clientStmt->fetchColumn() ?: 0;

// POST Action Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $milestoneId = (int)($_POST['milestone_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $comment = trim($_POST['client_comment'] ?? '');

    // Verify milestone belongs to this specific client to secure actions
    $verifyStmt = $db->prepare("
        SELECT m.id FROM milestones m 
        JOIN campaigns c ON m.campaign_id = c.id 
        WHERE m.id = ? AND c.client_id = ?
    ");
    $verifyStmt->execute([$milestoneId, $clientId]);
    
    if ($verifyStmt->fetch()) {
        $status = ($action === 'approve') ? 'Approved' : 'Revision Requested';
        $stmt = $db->prepare("UPDATE milestones SET status = ?, client_comment = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $comment, $milestoneId]);
        

        $msg = 'success:Feedback submitted successfully.';
    } else {
        $msg = 'error:Unauthorized action request.';
    }
}

// Fetch Milestones needing review or active tracking
$milestonesStmt = $db->prepare("
    SELECT m.*, c.title AS campaign_title 
    FROM milestones m
    JOIN campaigns c ON m.campaign_id = c.id
    WHERE c.client_id = ?
    ORDER BY CASE WHEN m.status = 'Under Review' THEN 1 WHEN m.status = 'Pending' THEN 2 ELSE 3 END, m.due_date ASC
");
$milestonesStmt->execute([$clientId]);
$milestones = $milestonesStmt->fetchAll();

[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['',''];
$pageTitle  = 'Approvals';
$activePage = 'approvals';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Sign-offs & Approvals</h1>
            <p class="page-subtitle">Review items pending your agency approval</p>
        </div>
    </div>

    <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show mb-4" role="alert">
        <?= htmlspecialchars($msgText) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card-adhub">
        <div class="table-responsive">
            <table class="table-adhub">
                <thead>
                    <tr>
                        <th>Campaign / Milestone</th>
                        <th>Target Due Date</th>
                        <th>Status</th>
                        <th>Your Comments</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($milestones)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No deliverables listed yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($milestones as $m): 
                        $statusBadge = match($m['status']) {
                            'Approved' => 'badge bg-success',
                            'Under Review' => 'badge bg-warning text-dark',
                            'Revision Requested' => 'badge bg-danger',
                            'Completed' => 'badge bg-primary',
                            default => 'badge bg-secondary'
                        };
                    ?>
                    <tr>
                        <td>
                            <div class="small text-muted font-monospace mb-1"><?= htmlspecialchars($m['campaign_title']) ?></div>
                            <strong><?= htmlspecialchars($m['title']) ?></strong>
                            <?php if ($m['description']): ?>
                                <div class="small text-secondary mt-1"><?= htmlspecialchars($m['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= $m['due_date'] ? date('M d, Y', strtotime($m['due_date'])) : '—' ?></td>
                        <td><span class="<?= $statusBadge ?>"><?= $m['status'] ?></span></td>
                        <td><small class="text-secondary"><?= htmlspecialchars($m['client_comment'] ?? '—') ?></small></td>
                        <td class="text-end">
                            <?php if ($m['status'] === 'Under Review' || $m['status'] === 'Pending' || $m['status'] === 'Revision Requested'): ?>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reviewModal<?= $m['id'] ?>">
                                    <i class="bi bi-pencil-square"></i> Review
                                </button>

                                <div class="modal fade" id="reviewModal<?= $m['id'] ?>" tabindex="-1">
                                  <div class="modal-dialog">
                                    <div class="modal-content text-start">
                                      <form method="POST">
                                        <input type="hidden" name="milestone_id" value="<?= $m['id'] ?>">
                                        <div class="modal-header">
                                          <h5 class="modal-title">Review: <?= htmlspecialchars($m['title']) ?></h5>
                                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                          <div class="mb-3">
                                            <label class="form-label d-block text-start">Your Decision *</label>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="radio" name="action" id="app<?= $m['id'] ?>" value="approve" checked>
                                              <label class="form-check-label" for="app<?= $m['id'] ?>">Approve Deliverable</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="radio" name="action" id="rev<?= $m['id'] ?>" value="revision">
                                              <label class="form-check-label" for="rev<?= $m['id'] ?>">Request Changes</label>
                                            </div>
                                          </div>
                                          <div class="mb-3">
                                            <label class="form-label text-start d-block">Comments / Revision Notes</label>
                                            <textarea name="client_comment" class="form-control" rows="3" placeholder="Provide feedback or requested adjustments..."><?= htmlspecialchars($m['client_comment'] ?? '') ?></textarea>
                                          </div>
                                        </div>
                                        <div class="modal-footer">
                                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                          <button type="submit" class="btn-primary-adhub">Submit Feedback</button>
                                        </div>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-check-all text-success"></i> Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>