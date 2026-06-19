<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$admin = require_admin();
$title = 'Linkage & Records - Magerwa Vehicle Tracking';
$active = 'links';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? 'create';
    $linkId = (int) ($_POST['link_id'] ?? 0);
    $clientId = (int) ($_POST['client_id'] ?? 0);
    $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
    $plate = strtoupper(clean_string($_POST['plate_number'] ?? ''));

    if ($action === 'delete' && $linkId > 0) {
        $stmt = db()->prepare('DELETE FROM vehicle_client_links WHERE id = ?');
        $stmt->execute([$linkId]);
        flash('success', 'Vehicle assignment unlinked successfully.');
        header('Location: link_vehicle.php');
        exit;
    }

    $errors = [];
    if ($clientId <= 0) $errors[] = 'Please select a client.';
    if ($vehicleId <= 0) $errors[] = 'Please select a vehicle.';
    if (!valid_plate_number($plate)) $errors[] = 'Plate number must follow the Rwanda format, for example RAA 123 A.';

    if (!$errors) {
        try {
            if ($action === 'update' && $linkId > 0) {
                $stmt = db()->prepare(
                    'UPDATE vehicle_client_links
                     SET client_id = ?, vehicle_id = ?, plate_number = ?, linked_by = ?
                     WHERE id = ?'
                );
                $stmt->execute([$clientId, $vehicleId, $plate, $admin['id'], $linkId]);
                flash('success', 'Vehicle assignment updated successfully.');
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO vehicle_client_links (client_id, vehicle_id, plate_number, linked_by) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$clientId, $vehicleId, $plate, $admin['id']]);
                flash('success', 'Vehicle linked to client successfully.');
            }
            header('Location: link_vehicle.php');
            exit;
        } catch (PDOException $exception) {
            flash('danger', 'Plate number must be unique, and each vehicle can only be linked once.');
        }
    } else {
        flash('danger', implode(' ', $errors));
    }
}

$clients = db()->query('SELECT id, names, national_id FROM clients ORDER BY names ASC')->fetchAll();
$vehicles = db()->query(
    'SELECT v.id, v.chassis_number, v.model_name, v.manufacture_company
     FROM vehicles v
     LEFT JOIN vehicle_client_links l ON l.vehicle_id = v.id
     WHERE l.id IS NULL
     ORDER BY v.created_at DESC'
)->fetchAll();
$allVehicles = db()->query(
    'SELECT v.id, v.chassis_number, v.model_name, v.manufacture_company, l.id AS link_id
     FROM vehicles v
     LEFT JOIN vehicle_client_links l ON l.vehicle_id = v.id
     ORDER BY v.created_at DESC'
)->fetchAll();

$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$total = (int) db()->query('SELECT COUNT(*) FROM vehicle_client_links')->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    'SELECT l.id, l.client_id, l.vehicle_id, l.plate_number, l.linked_at,
            c.names AS client_name, c.national_id AS client_national_id, c.telephone, c.address,
            v.chassis_number, v.manufacture_company, v.manufacture_year, v.price, v.model_name,
            a.names AS linked_by_name
     FROM vehicle_client_links l
     JOIN clients c ON c.id = l.client_id
     JOIN vehicles v ON v.id = l.vehicle_id
     JOIN admins a ON a.id = l.linked_by
     ORDER BY l.linked_at DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$records = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="page-heading d-flex justify-content-between align-items-start mb-4">
    <div>
        <span class="section-kicker text-teal">Vehicle assignment</span>
        <h1 class="page-title h2 mb-1">Linkage & display</h1>
        <p class="text-secondary mb-0">Assign plate numbers and browse linked client-vehicle records.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xxl-4">
        <section class="content-panel registry-side-panel p-3 p-lg-4">
            <h2 class="h5 mb-3"><i class="bi bi-link-45deg me-2 text-teal"></i>Link vehicle</h2>
            <form method="post" class="vstack gap-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="form-label">Client</label>
                    <select class="form-select" name="client_id" required>
                        <option value="">Choose client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= (int) $client['id'] ?>"><?= e($client['names'] . ' - ' . $client['national_id']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Unlinked vehicle</label>
                    <select class="form-select" name="vehicle_id" required>
                        <option value="">Choose vehicle</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?= (int) $vehicle['id'] ?>"><?= e($vehicle['chassis_number'] . ' - ' . $vehicle['model_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Unique plate number</label>
                    <input class="form-control text-uppercase" name="plate_number" placeholder="RAA 123 A" minlength="9" maxlength="9" pattern="R[A-Za-z]{2} [0-9]{3} [A-Za-z]" required>
                </div>
                <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Assign vehicle</button>
            </form>
        </section>
        <section class="content-panel registry-side-panel p-3 p-lg-4 mt-4">
            <h2 class="h5 mb-3"><i class="bi bi-shield-check me-2 text-teal"></i>Assignment rules</h2>
            <ul class="rule-list">
                <li><i class="bi bi-check-circle-fill"></i>A vehicle can only be linked to one active client.</li>
                <li><i class="bi bi-check-circle-fill"></i>The plate number must be unique.</li>
                <li><i class="bi bi-check-circle-fill"></i>Client and vehicle information must be correct.</li>
                <li><i class="bi bi-check-circle-fill"></i>Admin must be logged in to assign records.</li>
            </ul>
        </section>
    </div>
    <div class="col-xxl-8">
        <section class="content-panel p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0"><i class="bi bi-card-checklist me-2 text-teal"></i>Linked records</h2>
                <span class="text-secondary small"><?= $total ?> total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Plate</th>
                        <th>Client</th>
                        <th>Telephone</th>
                        <th>Vehicle</th>
                        <th>Year</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($records as $record): ?>
                        <tr>
                            <td><span class="badge badge-soft"><?= e($record['plate_number']) ?></span></td>
                            <td>
                                <div class="fw-semibold"><?= e($record['client_name']) ?></div>
                                <div class="small text-secondary"><?= e($record['client_national_id']) ?></div>
                            </td>
                            <td><?= e($record['telephone']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($record['model_name']) ?></div>
                                <div class="small text-secondary"><?= e($record['chassis_number']) ?></div>
                            </td>
                            <td><?= e((string) $record['manufacture_year']) ?></td>
                            <td><?= e(number_format((float) $record['price'], 2)) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#editLink<?= (int) $record['id'] ?>" title="Edit assignment">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-danger" data-bs-toggle="modal" data-bs-target="#deleteLink<?= (int) $record['id'] ?>" title="Unlink assignment">
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                </div>

                                <div class="modal fade" id="editLink<?= (int) $record['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h3 class="modal-title h5">Edit assignment</h3>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body vstack gap-3">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="link_id" value="<?= (int) $record['id'] ?>">
                                                    <div>
                                                        <label class="form-label">Client</label>
                                                        <select class="form-select" name="client_id" required>
                                                            <?php foreach ($clients as $client): ?>
                                                                <option value="<?= (int) $client['id'] ?>" <?= (int) $client['id'] === (int) $record['client_id'] ? 'selected' : '' ?>>
                                                                    <?= e($client['names'] . ' - ' . $client['national_id']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Vehicle</label>
                                                        <select class="form-select" name="vehicle_id" required>
                                                            <?php foreach ($allVehicles as $vehicle): ?>
                                                                <?php $isCurrent = (int) $vehicle['id'] === (int) $record['vehicle_id']; ?>
                                                                <?php if ($vehicle['link_id'] === null || $isCurrent): ?>
                                                                    <option value="<?= (int) $vehicle['id'] ?>" <?= $isCurrent ? 'selected' : '' ?>>
                                                                        <?= e($vehicle['chassis_number'] . ' - ' . $vehicle['model_name']) ?>
                                                                    </option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Unique plate number</label>
                                                        <input class="form-control text-uppercase" name="plate_number" minlength="9" maxlength="9" pattern="R[A-Za-z]{2} [0-9]{3} [A-Za-z]" value="<?= e($record['plate_number']) ?>" required>
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

                                <div class="modal fade" id="deleteLink<?= (int) $record['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h3 class="modal-title h5">Unlink assignment</h3>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="link_id" value="<?= (int) $record['id'] ?>">
                                                    <p class="mb-0">Unlink plate <strong><?= e($record['plate_number']) ?></strong> from <strong><?= e($record['client_name']) ?></strong>? The client and vehicle will remain registered.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button class="btn btn-danger">Unlink vehicle</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$records): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-4">No linked records yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pages > 1): ?>
                <nav aria-label="Records pagination">
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
