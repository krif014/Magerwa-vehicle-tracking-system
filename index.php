<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$admin = require_admin();
$title = 'Dashboard - Magerwa Vehicle Tracking';
$active = 'dashboard';

$clientCount = (int) db()->query('SELECT COUNT(*) FROM clients')->fetchColumn();
$vehicleCount = (int) db()->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
$linkedCount = (int) db()->query('SELECT COUNT(*) FROM vehicle_client_links')->fetchColumn();
$unlinkedCount = (int) db()->query('SELECT COUNT(*) FROM vehicles v LEFT JOIN vehicle_client_links l ON l.vehicle_id = v.id WHERE l.id IS NULL')->fetchColumn();

$recent = db()->query(
    'SELECT l.plate_number, l.linked_at, c.names AS client_name, v.chassis_number, v.model_name
     FROM vehicle_client_links l
     JOIN clients c ON c.id = l.client_id
     JOIN vehicles v ON v.id = l.vehicle_id
     ORDER BY l.linked_at DESC
     LIMIT 6'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="mb-0">Monitor clients, vehicles, and assignments for Magerwa warehouse operations.</p>
    </div>
    <a href="link_vehicle.php" class="btn btn-primary"><i class="bi bi-link-45deg me-1"></i>Link vehicle</a>
</section>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon stat-blue"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-label">Total Clients</div>
                <div class="stat-value"><?= $clientCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon stat-blue"><i class="bi bi-truck-front"></i></div>
            <div>
                <div class="stat-label">Total Vehicles</div>
                <div class="stat-value"><?= $vehicleCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon stat-blue"><i class="bi bi-link-45deg"></i></div>
            <div>
                <div class="stat-label">Linked Vehicles</div>
                <div class="stat-value"><?= $linkedCount ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon stat-amber"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-label">Pending Assignments</div>
                <div class="stat-value"><?= $unlinkedCount ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <section class="content-panel p-3 p-lg-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Recent Assignments</h2>
                <a href="link_vehicle.php" class="btn btn-outline-secondary btn-sm">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Plate No</th>
                        <th>Client</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><?= e($row['plate_number']) ?></td>
                            <td><?= e($row['client_name']) ?></td>
                            <td><?= e($row['model_name']) ?></td>
                            <td><span class="badge text-bg-success">Completed</span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-4">No linked records yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-xl-5">
        <section class="content-panel p-3 p-lg-4 h-100">
            <h2 class="h5 mb-4">Quick Actions</h2>
            <div class="quick-actions">
                <a href="clients.php"><span><i class="bi bi-person-plus"></i></span>Add Client</a>
                <a href="vehicles.php"><span><i class="bi bi-truck"></i></span>Register Vehicle</a>
                <a href="link_vehicle.php"><span><i class="bi bi-link-45deg"></i></span>Link Vehicle</a>
                <a href="link_vehicle.php"><span><i class="bi bi-file-earmark-text"></i></span>View Records</a>
            </div>
        </section>
    </div>
    <div class="col-xl-12">
        <section class="content-panel p-3 p-lg-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-4">
                <div>
                    <h2 class="h5 mb-2">Fleet Status</h2>
                    <p class="text-secondary mb-0">Linked and pending vehicle assignments at a glance.</p>
                </div>
                <div class="fleet-summary ms-lg-auto">
                    <div class="donut" style="--linked: <?= $vehicleCount > 0 ? (int) round(($linkedCount / max(1, $vehicleCount)) * 100) : 0 ?>;">
                        <span><?= $vehicleCount ?></span>
                        <small>Total Vehicles</small>
                    </div>
                    <div class="fleet-legend">
                        <span><i class="legend-dot linked"></i>Linked Vehicles <strong><?= $linkedCount ?></strong></span>
                        <span><i class="legend-dot pending"></i>Unlinked Vehicles <strong><?= $unlinkedCount ?></strong></span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
