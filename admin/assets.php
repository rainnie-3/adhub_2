<?php
/**
 * admin/assets.php
 * AdHub – Asset / File Upload Manager
 */
define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$db      = getDB();
$msg     = '';
$uploadDir = __DIR__ . '/../assets/uploads/';

// ── POST handlers ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $msg = 'error:No file selected or upload error.';
        } else {
            $mime = mime_content_type($_FILES['file']['tmp_name']);
            if (false) {
                // No file type restrictions – all types are allowed
            } else {
                $origName   = basename($_FILES['file']['name']);
                $ext        = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $storedName = bin2hex(random_bytes(12)) . '.' . $ext;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $storedName)) {
                    $stmt = $db->prepare('INSERT INTO assets (campaign_id,client_id,uploaded_by,original_name,stored_name,file_type,file_size,description) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute([
                        $_POST['campaign_id'],
                        $_POST['client_id'],
                        userId(),
                        $origName,
                        $storedName,
                        $mime,
                        $_FILES['file']['size'],
                        $_POST['description'],
                    ]);
                    $msg = 'success:File uploaded successfully.';
                } else {
                    $msg = 'error:Could not save file. Check directory permissions.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id   = (int)$_POST['id'];
        $stmt = $db->prepare('SELECT stored_name FROM assets WHERE id=?');
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        if ($row) {
            @unlink($uploadDir . $row['stored_name']);
            $db->prepare('DELETE FROM assets WHERE id=?')->execute([$id]);
        }
        $msg = 'success:File deleted.';
    }
}

// ── Data ─────────────────────────────────────────────────────
$clients   = $db->query('SELECT id,company_name FROM clients ORDER BY company_name')->fetchAll();
$campaigns = $db->query('SELECT id,title,client_id FROM campaigns ORDER BY title')->fetchAll();

$filterClient   = (int)($_GET['client'] ?? 0);
$filterCampaign = (int)($_GET['campaign'] ?? 0);

$qWhere = 'WHERE 1=1';
$qParams = [];
if ($filterClient)   { $qWhere .= ' AND a.client_id=?';   $qParams[] = $filterClient; }
if ($filterCampaign) { $qWhere .= ' AND a.campaign_id=?';  $qParams[] = $filterCampaign; }

$stmt = $db->prepare("
    SELECT a.*, cl.company_name, c.title AS campaign_title, u.name AS uploader
    FROM assets a
    JOIN clients cl  ON cl.id = a.client_id
    JOIN campaigns c ON c.id  = a.campaign_id
    LEFT JOIN users u ON u.id = a.uploaded_by
    $qWhere
    ORDER BY a.created_at DESC
");
$stmt->execute($qParams);
$assets = $stmt->fetchAll();

function fileIcon(string $mime): string {
    if (str_starts_with($mime,'image/')) return 'bi-file-earmark-image text-info';
    if ($mime === 'application/pdf')     return 'bi-file-earmark-pdf text-danger';
    if (str_contains($mime,'word'))      return 'bi-file-earmark-word text-primary';
    if (str_contains($mime,'excel') || str_contains($mime,'spreadsheet')) return 'bi-file-earmark-excel text-success';
    if (str_contains($mime,'powerpoint') || str_contains($mime,'presentation')) return 'bi-file-earmark-ppt text-warning';
    return 'bi-file-earmark text-secondary';
}
function formatBytes(int $b): string {
    if ($b < 1024) return $b . ' B';
    if ($b < 1048576) return round($b/1024) . ' KB';
    return round($b/1048576,1) . ' MB';
}

[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['',''];
$pageTitle  = 'Assets';
$activePage = 'assets';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Assets</h1>
            <p class="page-subtitle"><?= count($assets) ?> file<?= count($assets) !== 1 ? 's' : '' ?> stored</p>
        </div>
        <button class="btn-primary-adhub" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bi bi-upload"></i> Upload File
        </button>
    </div>

    <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'danger' ?> alert-dismissible alert-auto-dismiss mb-3">
        <?= htmlspecialchars($msgText) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <form class="d-flex gap-2 mb-3 flex-wrap" method="GET">
        <select name="client" class="form-select" style="max-width:200px" onchange="this.form.submit()">
            <option value="">All Clients</option>
            <?php foreach ($clients as $cl): ?>
            <option value="<?= $cl['id'] ?>" <?= $filterClient == $cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['company_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="campaign" class="form-select" style="max-width:240px" onchange="this.form.submit()">
            <option value="">All Campaigns</option>
            <?php foreach ($campaigns as $cp): ?>
            <option value="<?= $cp['id'] ?>" <?= $filterCampaign == $cp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cp['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterClient || $filterCampaign): ?>
        <a href="assets.php" class="btn-outline-adhub">Clear</a>
        <?php endif; ?>
    </form>

    <!-- File Grid -->
    <?php if ($assets): ?>
    <div class="row g-3">
        <?php foreach ($assets as $a): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="file-card">
                <div class="file-icon"><i class="bi <?= fileIcon($a['file_type'] ?? '') ?>"></i></div>
                <div class="file-name" title="<?= htmlspecialchars($a['original_name']) ?>">
                    <?= htmlspecialchars(mb_strimwidth($a['original_name'],0,28,'…')) ?>
                </div>
                <div class="file-meta"><?= htmlspecialchars($a['company_name']) ?> · <?= formatBytes((int)$a['file_size']) ?></div>
                <div class="file-meta" style="font-size:.7rem"><?= htmlspecialchars($a['campaign_title']) ?></div>
                <div class="d-flex gap-1 justify-content-center mt-2">
                    <a href="<?= BASE_URL ?>/assets/uploads/<?= urlencode($a['stored_name']) ?>"
                       class="btn btn-sm btn-outline-secondary" target="_blank" title="Preview">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/assets/uploads/<?= urlencode($a['stored_name']) ?>"
                       download="<?= htmlspecialchars($a['original_name']) ?>"
                       class="btn btn-sm btn-outline-secondary" title="Download">
                        <i class="bi bi-download"></i>
                    </a>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this file?">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card-adhub text-center py-4">
        <i class="bi bi-folder2-open" style="font-size:2.5rem;color:var(--text-muted)"></i>
        <p class="mt-2" style="color:var(--text-muted)">No files found. Upload an asset to get started.</p>
    </div>
    <?php endif; ?>

</div>

<!-- UPLOAD MODAL -->
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="modal-header"><h5 class="modal-title">Upload Asset</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">File *</label>
              <input type="file" name="file" class="form-control" required>
              <div style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem">All file types accepted – no size limit</div>
            </div>
            <div class="col-12">
              <label class="form-label">Client *</label>
              <select name="client_id" id="uploadClientSelect" class="form-select" required onchange="filterCampaigns(this.value)">
                <option value="">Select client…</option>
                <?php foreach ($clients as $cl): ?>
                <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['company_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Campaign *</label>
              <select name="campaign_id" id="uploadCampaignSelect" class="form-select" required>
                <option value="">Select campaign…</option>
                <?php foreach ($campaigns as $cp): ?>
                <option value="<?= $cp['id'] ?>" data-client="<?= $cp['client_id'] ?>"><?= htmlspecialchars($cp['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <input type="text" name="description" class="form-control" placeholder="Brief description…">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-adhub"><i class="bi bi-upload me-1"></i>Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function filterCampaigns(clientId) {
    const opts = document.querySelectorAll('#uploadCampaignSelect option[data-client]');
    opts.forEach(opt => {
        opt.hidden = clientId && opt.dataset.client !== clientId;
    });
    document.getElementById('uploadCampaignSelect').value = '';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>