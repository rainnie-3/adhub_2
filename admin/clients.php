<?php
/**
 * admin/clients.php
 * AdHub – Manage Clients (Add / Edit / Delete)
 */
define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$db  = getDB();
$msg = '';

// ── POST handlers ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // Create client user account
        $userStmt = $db->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
        $hash = password_hash($_POST['password'] ?? 'Client@1234', PASSWORD_BCRYPT);
        $userStmt->execute([
            $_POST['contact_person'],
            $_POST['email'],
            $hash,
            'client'
        ]);
        $newUserId = $db->lastInsertId();

        $stmt = $db->prepare('INSERT INTO clients (user_id,company_name,contact_person,email,phone,address,retainer_budget,notes) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $newUserId,
            $_POST['company_name'],
            $_POST['contact_person'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['retainer_budget'],
            $_POST['notes'],
        ]);
        $msg = 'success:Client added successfully.';

    } elseif ($action === 'edit') {
        $stmt = $db->prepare('UPDATE clients SET company_name=?,contact_person=?,email=?,phone=?,address=?,retainer_budget=?,notes=? WHERE id=?');
        $stmt->execute([
            $_POST['company_name'],
            $_POST['contact_person'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['retainer_budget'],
            $_POST['notes'],
            $_POST['id'],
        ]);
        $msg = 'success:Client updated successfully.';

    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Remove linked user account too
        $client = $db->prepare('SELECT user_id FROM clients WHERE id=?');
        $client->execute([$id]);
        $row = $client->fetch();
        $db->prepare('DELETE FROM clients WHERE id=?')->execute([$id]);
        if ($row && $row['user_id']) {
            $db->prepare('DELETE FROM users WHERE id=?')->execute([$row['user_id']]);
        }
        $msg = 'success:Client deleted.';
    }
}

$clients = $db->query("
    SELECT cl.*, u.email AS login_email,
           COUNT(DISTINCT c.id) AS campaign_count
    FROM clients cl
    LEFT JOIN users u ON u.id = cl.user_id
    LEFT JOIN campaigns c ON c.client_id = cl.id
    GROUP BY cl.id
    ORDER BY cl.company_name
")->fetchAll();

[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['',''];
$pageTitle  = 'Clients';
$activePage = 'clients';
$extraScripts = [BASE_URL . '/assets/js/campaigns.js'];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Clients</h1>
            <p class="page-subtitle"><?= count($clients) ?> client<?= count($clients) !== 1 ? 's' : '' ?> registered</p>
        </div>
        <button class="btn-primary-adhub" data-bs-toggle="modal" data-bs-target="#addClientModal">
            <i class="bi bi-plus-lg"></i> Add Client
        </button>
    </div>

    <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'danger' ?> alert-dismissible alert-auto-dismiss mb-3" role="alert">
        <?= htmlspecialchars($msgText) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card-adhub">
        <div class="topbar mb-3">
            <div class="topbar-search">
                <i class="bi bi-search"></i>
                <input type="text" id="tableSearch" placeholder="Search clients…">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-adhub">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Retainer</th>
                        <th>Campaigns</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clients as $c): ?>
                <tr class="searchable-row">
                    <td><strong><?= htmlspecialchars($c['company_name']) ?></strong></td>
                    <td><?= htmlspecialchars($c['contact_person']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                    <td>₱<?= number_format($c['retainer_budget'], 2) ?></td>
                    <td><?= $c['campaign_count'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary me-1"
                            data-bs-toggle="modal" data-bs-target="#editClientModal"
                            data-id="<?= $c['id'] ?>"
                            data-company_name="<?= htmlspecialchars($c['company_name'], ENT_QUOTES) ?>"
                            data-contact_person="<?= htmlspecialchars($c['contact_person'], ENT_QUOTES) ?>"
                            data-email="<?= htmlspecialchars($c['email'], ENT_QUOTES) ?>"
                            data-phone="<?= htmlspecialchars($c['phone'] ?? '', ENT_QUOTES) ?>"
                            data-address="<?= htmlspecialchars($c['address'] ?? '', ENT_QUOTES) ?>"
                            data-retainer_budget="<?= $c['retainer_budget'] ?>"
                            data-notes="<?= htmlspecialchars($c['notes'] ?? '', ENT_QUOTES) ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                data-confirm="Delete <?= htmlspecialchars($c['company_name'], ENT_QUOTES) ?> and all associated data?">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.main-content -->

<!-- ADD CLIENT MODAL -->
<div class="modal fade" id="addClientModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title">Add New Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Company Name *</label>
              <input type="text" name="company_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact Person *</label>
              <input type="text" name="contact_person" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Retainer Budget (₱)</label>
              <input type="number" step="0.01" name="retainer_budget" class="form-control" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">Login Password</label>
              <input type="text" name="password" class="form-control" placeholder="Default: Client@1234" value="Client@1234">
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <input type="text" name="address" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-adhub">Save Client</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT CLIENT MODAL -->
<div class="modal fade" id="editClientModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id">
        <div class="modal-header">
          <h5 class="modal-title">Edit Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Company Name *</label>
              <input type="text" name="company_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact Person *</label>
              <input type="text" name="contact_person" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email *</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Retainer Budget (₱)</label>
              <input type="number" step="0.01" name="retainer_budget" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <input type="text" name="address" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-adhub">Update Client</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>