<?php
/**
 * includes/sidebar.php
 * Renders the left sidebar for admin OR client based on session role.
 * Expects:  $activePage  (string)  – e.g. 'dashboard', 'clients', etc.
 */
$activePage = $activePage ?? '';
$role = userRole();
$base = BASE_URL;
?>

<nav id="sidebar" class="sidebar d-flex flex-column">

    <!-- Brand -->
    <div class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-layers-fill"></i></span>
        <span class="brand-name">AdHub</span>
    </div>

    <!-- Role badge -->
    <div class="sidebar-role-badge">
        <?= $role === 'admin'
            ? '<span class="badge bg-primary-soft">Agency Admin</span>'
            : '<span class="badge bg-client-soft">Client Portal</span>' ?>
    </div>

    <!-- Navigation links -->
    <ul class="sidebar-nav list-unstyled flex-grow-1">

        <?php if ($role === 'admin'): ?>

            <li class="nav-section-label">Main</li>
            <li>
                <a href="<?= $base ?>/admin/dashboard.php"
                   class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-section-label">Management</li>
            <li>
                <a href="<?= $base ?>/admin/clients.php"
                   class="sidebar-link <?= $activePage === 'clients' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    <span>Clients</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>/admin/campaigns.php"
                   class="sidebar-link <?= $activePage === 'campaigns' ? 'active' : '' ?>">
                    <i class="bi bi-megaphone"></i>
                    <span>Campaigns</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>/admin/assets.php"
                   class="sidebar-link <?= $activePage === 'assets' ? 'active' : '' ?>">
                    <i class="bi bi-folder2-open"></i>
                    <span>Assets</span>
                </a>
            </li>

            <li class="nav-section-label">Insights</li>
            <li>
                <a href="<?= $base ?>/admin/reports.php"
                   class="sidebar-link <?= $activePage === 'reports' ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>/admin/analytics.php"
                   class="sidebar-link <?= $activePage === 'analytics' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </li>

        <?php else: ?>

            <li class="nav-section-label">My Work</li>
            <li>
                <a href="<?= $base ?>/client/campaigns.php"
                   class="sidebar-link <?= $activePage === 'campaigns' ? 'active' : '' ?>">
                    <i class="bi bi-megaphone"></i>
                    <span>Campaigns</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>/client/approvals.php"
                   class="sidebar-link <?= $activePage === 'approvals' ? 'active' : '' ?>">
                    <i class="bi bi-check2-circle"></i>
                    <span>Approvals</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>/client/files.php"
                   class="sidebar-link <?= $activePage === 'files' ? 'active' : '' ?>">
                    <i class="bi bi-cloud-download"></i>
                    <span>Files</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>/client/budget.php"
                   class="sidebar-link <?= $activePage === 'budget' ? 'active' : '' ?>">
                    <i class="bi bi-currency-dollar"></i>
                    <span>Budget</span>
                </a>
            </li>

        <?php endif; ?>

    </ul>

    <!-- User + Logout -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar"><?= strtoupper(substr(userName(), 0, 1)) ?></div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars(userName()) ?></span>
                <span class="user-role"><?= ucfirst($role) ?></span>
            </div>
        </div>
        <a href="<?= $base ?>/logout.php" class="sidebar-link logout-link mt-2">
            <i class="bi bi-box-arrow-left"></i>
            <span>Sign out</span>
        </a>
    </div>

</nav>