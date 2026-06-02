<?php
/**
 * admin/campaigns.php
 * AdHub – Campaign Management with Kanban view + Milestones + Time Logging
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

    if ($action === 'add_campaign') {
        $stmt = $db->prepare('INSERT INTO campaigns (client_id,title,description,status,budget,start_date,end_date,created_by) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$_POST['client_id'],$_POST['title'],$_POST['description'],$_POST['status'],$_POST['budget'],$_POST['start_date'],$_POST['end_date'],userId()]);
        $msg = 'success:Campaign created.';

    } elseif ($action === 'edit_campaign') {
        $stmt = $db->prepare('UPDATE campaigns SET client_id=?,title=?,description=?,status=?,budget=?,start_date=?,end_date=? WHERE id=?');
        $stmt->execute([$_POST['client_id'],$_POST['title'],$_POST['description'],$_POST['status'],$_POST['budget'],$_POST['start_date'],$_POST['end_date'],$_POST['id']]);
        $msg = 'success:Campaign updated.';

    } elseif ($action === 'delete_campaign') {
        $db->prepare('DELETE FROM campaigns WHERE id=?')->execute([(int)$_POST['id']]);
        $msg = 'success:Campaign deleted.';

    } elseif ($action === 'add_milestone') {
        $stmt = $db->prepare('INSERT INTO milestones (campaign_id,title,description,status,due_date) VALUES (?,?,?,?,?)');
        $stmt->execute([$_POST['campaign_id'],$_POST['title'],$_POST['description'],$_POST['status'],$_POST['due_date']]);
        $msg = 'success:Milestone added.';

    } elseif ($action === 'edit_milestone') {
        $stmt = $db->prepare('UPDATE milestones SET title=?,description=?,status=?,due_date=? WHERE id=?');
        $stmt->execute([$_POST['title'],$_POST['description'],$_POST['status'],$_POST['due_date'],(int)$_POST['id']]);
        $msg = 'success:Milestone updated.';

    } elseif ($action === 'delete_milestone') {
        $db->prepare('DELETE FROM milestones WHERE id=?')->execute([(int)$_POST['id']]);
        $msg = 'success:Milestone deleted.';

    } elseif ($action === 'log_time') {
        $stmt = $db->prepare('INSERT INTO time_logs (campaign_id,logged_by,hours,hourly_rate,description,log_date) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$_POST['campaign_id'],userId(),$_POST['hours'],$_POST['hourly_rate'],$_POST['description'],$_POST['log_date']]);
        $msg = 'success:Time logged.';
    }
}

// ── Data ─────────────────────────────────────────────────────
$clients = $db->query('SELECT id, company_name FROM clients ORDER BY company_name')->fetchAll();
$statuses = ['Planning','Active','Under Review','Approved','Completed'];

$campaigns = $db->query("
    SELECT c.*, cl.company_name,
           COALESCE(SUM(tl.amount),0) AS spent,
           COUNT(DISTINCT m.id) AS milestone_count
    FROM campaigns c
    JOIN clients cl ON cl.id = c.client_id
    LEFT JOIN time_logs tl ON tl.campaign_id = c.id
    LEFT JOIN milestones m  ON m.campaign_id  = c.id
    GROUP BY c.id
    ORDER BY c.created_at DESC
")->fetchAll();

// Group by status for Kanban
$kanban = array_fill_keys($statuses, []);
foreach ($campaigns as $camp) {
    $kanban[$camp['status']][] = $camp;
}

// Selected campaign for detail view
$selectedId  = (int)($_GET['view'] ?? 0);
$selectedCamp= null;
$milestones  = [];
$timeLogs    = [];
if ($selectedId) {
    $stmt = $db->prepare("SELECT c.*,cl.company_name FROM campaigns c JOIN clients cl ON cl.id=c.client_id WHERE c.id=?");
    $stmt->execute([$selectedId]);
    $selectedCamp = $stmt->fetch();
    if ($selectedCamp) {
        $ms = $db->prepare('SELECT * FROM milestones WHERE campaign_id=? ORDER BY due_date');
        $ms->execute([$selectedId]);
        $milestones = $ms->fetchAll();

        $tl = $db->prepare("SELECT tl.*,u.name AS logged_by_name FROM time_logs tl LEFT JOIN users u ON u.id=tl.logged_by WHERE tl.campaign_id=? ORDER BY tl.log_date DESC");
        $tl->execute([$selectedId]);
        $timeLogs = $tl->fetchAll();
    }
}

[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['',''];
$pageTitle  = 'Campaigns';
$activePage = 'campaigns';
$extraScripts = [BASE_URL . '/assets/js/campaigns.js'];

function statusClass(string $s): string {
    return 'status-' . strtolower(str_replace([' '],'-', $s));
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="app-wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Campaigns</h1>
            <p class="page-subtitle">Manage and track all client campaigns</p>
        </div>
        <button class="btn-primary-adhub" data-bs-toggle="modal" data-bs-target="#addCampaignModal">
            <i class="bi bi-plus-lg"></i> New Campaign
        </button>
    </div>

    <?php if ($msgText): ?>
    <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'danger' ?> alert-dismissible alert-auto-dismiss mb-3">
        <?= htmlspecialchars($msgText) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($selectedCamp): ?>
    <!-- ── Campaign Detail View ──────────────────────────── -->
    <div class="mb-3">
        <a href="campaigns.php" class="btn-outline-adhub"><i class="bi bi-arrow-left"></i> Back to Kanban</a>
    </div>
    <div class="row g-3">
        <!-- Info -->
        <div class="col-12 col-lg-5">
            <div class="card-adhub mb-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h4 style="font-size:1.1rem;margin:0"><?= htmlspecialchars($selectedCamp['title']) ?></h4>
                    <span class="status-badge <?= statusClass($selectedCamp['status']) ?>"><?= $selectedCamp['status'] ?></span>
                </div>
                <p style="font-size:.85rem;color:var(--text-muted)"><?= nl2br(htmlspecialchars($selectedCamp['description'] ?? '')) ?></p>
                <div class="row g-2" style="font-size:.82rem">
                    <div class="col-6"><span style="color:var(--text-muted)">Client</span><br><strong><?= htmlspecialchars($selectedCamp['company_name']) ?></strong></div>
                    <div class="col-6"><span style="color:var(--text-muted)">Budget</span><br><strong>₱<?= number_format($selectedCamp['budget'],2) ?></strong></div>
                    <div class="col-6"><span style="color:var(--text-muted)">Start</span><br><strong><?= $selectedCamp['start_date'] ?></strong></div>
                    <div class="col-6"><span style="color:var(--text-muted)">End</span><br><strong><?= $selectedCamp['end_date'] ?></strong></div>
                </div>
            </div>

            <!-- Log Time -->
            <div class="card-adhub">
                <div class="card-title">Log Time</div>
                <form method="POST" class="row g-2">
                    <input type="hidden" name="action" value="log_time">
                    <input type="hidden" name="campaign_id" value="<?= $selectedId ?>">
                    <div class="col-6">
                        <label class="form-label">Hours</label>
                        <input type="number" step="0.25" name="hours" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Hourly Rate (₱)</label>
                        <input type="number" step="0.01" name="hourly_rate" class="form-control" value="1500" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="log_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <button type="submit" class="btn-primary-adhub w-100">Log</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Work performed…">
                    </div>
                </form>

                <!-- Time log list -->
                <?php if ($timeLogs): ?>
                <div class="mt-3" style="max-height:220px;overflow-y:auto">
                    <table class="table-adhub" style="font-size:.78rem">
                        <thead><tr><th>Date</th><th>By</th><th>Hours</th><th>Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($timeLogs as $tl): ?>
                        <tr>
                            <td><?= $tl['log_date'] ?></td>
                            <td><?= htmlspecialchars($tl['logged_by_name'] ?? '—') ?></td>
                            <td><?= $tl['hours'] ?>h</td>
                            <td>₱<?= number_format($tl['amount'],2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Milestones -->
        <div class="col-12 col-lg-7">
            <div class="card-adhub">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title mb-0">Milestones</div>
                    <button class="btn-primary-adhub btn-sm" data-bs-toggle="modal" data-bs-target="#addMilestoneModal" style="font-size:.75rem;padding:.35rem .8rem">
                        <i class="bi bi-plus"></i> Add
                    </button>
                </div>
                <?php foreach ($milestones as $ms): ?>
                <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:8px;padding:.85rem 1rem;margin-bottom:.6rem">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong style="font-size:.9rem"><?= htmlspecialchars($ms['title']) ?></strong><br>
                            <span style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($ms['description'] ?? '') ?></span>
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            <span class="status-badge <?= statusClass($ms['status']) ?>"><?= $ms['status'] ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mt-2 align-items-center" style="font-size:.76rem;color:var(--text-muted)">
                        <span><i class="bi bi-calendar3 me-1"></i><?= $ms['due_date'] ?></span>
                        <?php if ($ms['client_comment']): ?>
                        <span><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($ms['client_comment']) ?></span>
                        <?php endif; ?>
                        <div class="ms-auto">
                            <button class="btn btn-sm btn-outline-secondary me-1"
                                data-bs-toggle="modal" data-bs-target="#editMilestoneModal"
                                data-id="<?= $ms['id'] ?>"
                                data-campaign_id="<?= $ms['campaign_id'] ?>"
                                data-title="<?= htmlspecialchars($ms['title'],ENT_QUOTES) ?>"
                                data-description="<?= htmlspecialchars($ms['description']??'',ENT_QUOTES) ?>"
                                data-status="<?= $ms['status'] ?>"
                                data-due_date="<?= $ms['due_date'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete_milestone">
                                <input type="hidden" name="id" value="<?= $ms['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this milestone?">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!$milestones): ?>
                <p class="text-muted-adhub small">No milestones yet. Add one to get started.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ── Kanban Board ──────────────────────────────────── -->
    <div class="row g-3">
        <?php foreach ($statuses as $status):
            $cols = ['Planning'=>2,'Active'=>2,'Under Review'=>2,'Approved'=>3,'Completed'=>3];
            $col  = $cols[$status] ?? 2;
        ?>
        <div class="col-12 col-sm-6 col-xl-<?= $col ?> kanban-col">
            <div class="kanban-col-header">
                <span><?= $status ?></span>
                <span class="badge" style="background:var(--border);color:var(--text-muted)"><?= count($kanban[$status]) ?></span>
            </div>
            <?php foreach ($kanban[$status] as $camp): ?>
            <div class="campaign-card">
                <div class="campaign-title"><?= htmlspecialchars($camp['title']) ?></div>
                <div class="campaign-client"><?= htmlspecialchars($camp['company_name']) ?></div>
                <div class="campaign-meta">
                    <?php if ($camp['end_date']): ?>
                    <span><i class="bi bi-calendar3"></i> <?= date('M j', strtotime($camp['end_date'])) ?></span>
                    <?php endif; ?>
                    <span><i class="bi bi-flag"></i> <?= $camp['milestone_count'] ?> milestones</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="campaign-budget">₱<?= number_format($camp['budget'],0) ?></span>
                    <div class="d-flex gap-1">
                        <a href="?view=<?= $camp['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#editCampaignModal"
                            data-id="<?= $camp['id'] ?>"
                            data-client_id="<?= $camp['client_id'] ?>"
                            data-title="<?= htmlspecialchars($camp['title'],ENT_QUOTES) ?>"
                            data-description="<?= htmlspecialchars($camp['description']??'',ENT_QUOTES) ?>"
                            data-status="<?= $camp['status'] ?>"
                            data-budget="<?= $camp['budget'] ?>"
                            data-start_date="<?= $camp['start_date'] ?>"
                            data-end_date="<?= $camp['end_date'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete_campaign">
                            <input type="hidden" name="id" value="<?= $camp['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this campaign?"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- ADD CAMPAIGN MODAL -->
<div class="modal fade" id="addCampaignModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add_campaign">
        <div class="modal-header"><h5 class="modal-title">New Campaign</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Title *</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach ($statuses as $s): ?><option><?= $s ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Client *</label>
              <select name="client_id" class="form-select" required>
                <option value="">Select client…</option>
                <?php foreach ($clients as $cl): ?>
                <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['company_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Budget (₱)</label>
              <input type="number" step="0.01" name="budget" class="form-control" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">Start Date</label>
              <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date</label>
              <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-adhub">Create Campaign</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT CAMPAIGN MODAL -->
<div class="modal fade" id="editCampaignModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="edit_campaign">
        <input type="hidden" name="id">
        <div class="modal-header"><h5 class="modal-title">Edit Campaign</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Title *</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach ($statuses as $s): ?><option><?= $s ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Client *</label>
              <select name="client_id" class="form-select" required>
                <?php foreach ($clients as $cl): ?>
                <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['company_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Budget (₱)</label>
              <input type="number" step="0.01" name="budget" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Start Date</label>
              <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date</label>
              <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-adhub">Update Campaign</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ADD MILESTONE MODAL -->
<div class="modal fade" id="addMilestoneModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add_milestone">
        <input type="hidden" name="campaign_id" value="<?= $selectedId ?>">
        <div class="modal-header"><h5 class="modal-title">Add Milestone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Title *</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['Pending','Under Review','Approved','Revision Requested','Completed'] as $s): ?>
                <option><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Due Date</label>
              <input type="date" name="due_date" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-adhub">Add Milestone</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT MILESTONE MODAL -->
<div class="modal fade" id="editMilestoneModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="edit_milestone">
        <input type="hidden" name="id">
        <input type="hidden" name="campaign_id">
        <div class="modal-header"><h5 class="modal-title">Edit Milestone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Title *</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['Pending','Under Review','Approved','Revision Requested','Completed'] as $s): ?>
                <option><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Due Date</label>
              <input type="date" name="due_date" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-primary-adhub">Update Milestone</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>