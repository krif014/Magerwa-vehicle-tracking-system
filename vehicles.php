<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$admin = require_admin();
$title = 'Vehicles - Magerwa Vehicle Tracking';
$active = 'vehicles';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? 'create';
    $chassis = strtoupper(clean_string($_POST['chassis_number'] ?? ''));
    $company = clean_string($_POST['manufacture_company'] ?? '');
    $year = (int) ($_POST['manufacture_year'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $model = clean_string($_POST['model_name'] ?? '');
    $currentYear = (int) date('Y');
    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);

    if ($action === 'delete' && $vehicleId > 0) {
        $stmt = db()->prepare('DELETE FROM vehicles WHERE id = ?');
        $stmt->execute([$vehicleId]);
        flash('success', 'Vehicle deleted successfully.');
        header('Location: vehicles.php');
        exit;
    }

    $errors = [];
    if (!valid_chassis_number($chassis)) $errors[] = 'Chassis/VIN must be 17 letters or numbers and cannot include I, O, or Q.';
    if ($company === '') $errors[] = 'Manufacture company is required.';
    if (!valid_year($year)) $errors[] = 'Manufacture year must be between 1901 and ' . $currentYear . '.';
    if (!valid_price($price)) $errors[] = 'Price must be greater than zero.';
    if ($model === '') $errors[] = 'Model name is required.';

    if (!$errors) {
        try {
            if ($action === 'update' && $vehicleId > 0) {
                $stmt = db()->prepare(
                    'UPDATE vehicles
                     SET chassis_number = ?, manufacture_company = ?, manufacture_year = ?, price = ?, model_name = ?
                     WHERE id = ?'
                );
                $stmt->execute([$chassis, $company, $year, $price, $model, $vehicleId]);
                flash('success', 'Vehicle updated successfully.');
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO vehicles (chassis_number, manufacture_company, manufacture_year, price, model_name, created_by)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$chassis, $company, $year, $price, $model, $admin['id']]);
                flash('success', 'Vehicle registered successfully.');
            }
            header('Location: vehicles.php');
            exit;
        } catch (PDOException $exception) {
            flash('danger', 'A vehicle with that chassis number already exists.');
        }
    } else {
        flash('danger', implode(' ', $errors));
    }
}

$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$total = (int) db()->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    'SELECT v.*, l.plate_number
     FROM vehicles v
     LEFT JOIN vehicle_client_links l ON l.vehicle_id = v.id
     ORDER BY v.created_at DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$vehicles = $stmt->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading d-flex justify-content-between align-items-start mb-4">
    <div>
        <span class="section-kicker text-teal">Vehicle registry</span>
        <h1 class="page-title h2 mb-1">Vehicle management</h1>
        <p class="text-secondary mb-0">Capture vehicle identity, value, model, and manufacturer details.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xxl-4">
        <section class="content-panel registry-side-panel p-3 p-lg-4">
            <h2 class="h5 mb-3"><i class="bi bi-truck-front me-2 text-teal"></i>Register vehicle</h2>
            <form method="post" class="vstack gap-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="form-label">Chassis/VIN number</label>
                    <input class="form-control text-uppercase" name="chassis_number" minlength="17" maxlength="17" pattern="[A-HJ-NPR-Za-hj-npr-z0-9]{17}" required>
                </div>
                <div>
                    <label class="form-label">Manufacture company</label>
                    <input class="form-control" name="manufacture_company" required>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label">Year</label>
                        <input class="form-control" type="number" name="manufacture_year" min="1901" max="<?= (int) date('Y') ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Price</label>
                        <input class="form-control" type="number" name="price" min="0.01" step="0.01" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Model name</label>
                    <input class="form-control" name="model_name" required>
                </div>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save vehicle</button>
            </form>
        </section>
    </div>
    <div class="col-xxl-8">
        <section class="content-panel p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0"><i class="bi bi-list-check me-2 text-teal"></i>Registered vehicles</h2>
                <span class="text-secondary small"><?= $total ?> total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Chassis</th>
                        <th>Company</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td><?= e($vehicle['chassis_number']) ?></td>
                            <td><?= e($vehicle['manufacture_company']) ?></td>
                            <td><?= e($vehicle['model_name']) ?></td>
                            <td><?= e((string) $vehicle['manufacture_year']) ?></td>
                            <td><?= e(number_format((float) $vehicle['price'], 2)) ?></td>
                            <td>
                                <?php if ($vehicle['plate_number']): ?>
                                    <span class="badge text-bg-success"><?= e($vehicle['plate_number']) ?></span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Unlinked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#editVehicle<?= (int) $vehicle['id'] ?>" title="Edit vehicle">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-danger" data-bs-toggle="modal" data-bs-target="#deleteVehicle<?= (int) $vehicle['id'] ?>" title="Delete vehicle">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                                <div class="modal fade" id="editVehicle<?= (int) $vehicle['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h3 class="modal-title h5">Edit vehicle</h3>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body vstack gap-3">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['id'] ?>">
                                                    <div>
                                                        <label class="form-label">Chassis/VIN number</label>
                                                        <input class="form-control text-uppercase" name="chassis_number" minlength="17" maxlength="17" pattern="[A-HJ-NPR-Za-hj-npr-z0-9]{17}" value="<?= e($vehicle['chassis_number']) ?>" required>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Manufacture company</label>
                                                        <input class="form-control" name="manufacture_company" value="<?= e($vehicle['manufacture_company']) ?>" required>
                                                    </div>
                                                    <div class="row g-3">
                                                        <div class="col-sm-6">
                                                            <label class="form-label">Year</label>
                                                            <input class="form-control" type="number" name="manufacture_year" min="1901" max="<?= (int) date('Y') ?>" value="<?= e((string) $vehicle['manufacture_year']) ?>" required>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <label class="form-label">Price</label>
                                                            <input class="form-control" type="number" name="price" min="0.01" step="0.01" value="<?= e((string) $vehicle['price']) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Model name</label>
                                                        <input class="form-control" name="model_name" value="<?= e($vehicle['model_name']) ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button class="btn btn-primary">Save changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="deleteVehicle<?= (int) $vehicle['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h3 class="modal-title h5">Delete vehicle</h3>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['id'] ?>">
                                                    <p class="mb-0">Delete <strong><?= e($vehicle['model_name']) ?></strong> with chassis <strong><?= e($vehicle['chassis_number']) ?></strong>? Any linked assignment for this vehicle will also be removed.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button class="btn btn-danger">Delete vehicle</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$vehicles): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-4">No vehicles registered yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pages > 1): ?>
                <nav aria-label="Vehicles pagination">
                    <ul class="pagination mb-0">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
