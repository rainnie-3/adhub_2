<?php
/**
 * client/files.php
 * AdHub – Client View Assets / Files
 */
define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('client');

$db = getDB();

$clientStmt = $db->prepare("SELECT id FROM clients WHERE user_id = ? LIMIT 1");
$clientStmt->execute([userId()]);
$clientId = $clientStmt->fetchColumn() ?: 0;

// Fetch assets filtered to this specific client account
$assetsStmt = $db->prepare("
    SELECT a.*, c.title AS campaign_title, u.name AS uploader_name
    FROM assets a
    JOIN campaigns c ON a.campaign_id = c.id
    LEFT JOIN users u ON a.uploaded_by = u.id
    WHERE a.client_id = ?
    ORDER BY a.created_at DESC
");
$assetsStmt->execute([$clientId]);
$assets = $assetsStmt->fetchAll();

$pageTitle  = 'Files';
$activePage = 'files';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Shared Assets & Files</h1>
            <p class="page-subtitle">Download your creative outputs, graphics, and documentation</p>
        </div>
    </div>

    <div class="card-adhub">
        <div class="table-responsive">
            <table class="table-adhub">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Campaign</th>
                        <th>Uploaded By</th>
                        <th>Size</th>
                        <th>Shared Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No assets uploaded for your account yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($assets as $a): 
                        $sizeKB = $a['file_size'] > 0 ? round($a['file_size'] / 1024, 2) . ' KB' : '—';
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-arrow-down text-primary h4 mb-0 me-2"></i>
                                <div>
                                    <strong><?= htmlspecialchars($a['original_name']) ?></strong>
                                    <?php if ($a['description']): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($a['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><span class="small text-secondary"><?= htmlspecialchars($a['campaign_title']) ?></span></td>
                        <td><?= htmlspecialchars($a['uploader_name'] ?? 'System') ?></td>
                        <td><span class="font-monospace small"><?= $sizeKB ?></span></td>
                        <td><small><?= date('M d, Y', strtotime($a['created_at'])) ?></small></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/assets/uploads/<?= htmlspecialchars($a['stored_name']) ?>" download="<?= htmlspecialchars($a['original_name']) ?>" class="btn btn-sm btn-primary text-white">
                                <i class="bi bi-download"></i> Download
                            </a>
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