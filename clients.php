<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$admin = require_admin();
$title = 'Clients - Magerwa Vehicle Tracking';
$active = 'clients';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? 'create';
    $names = clean_string($_POST['names'] ?? '');
    $nationalId = clean_string($_POST['national_id'] ?? '');
    $telephone = clean_string($_POST['telephone'] ?? '');
    $address = clean_string($_POST['address'] ?? '');
    $clientId = (int) ($_POST['client_id'] ?? 0);

    if ($action === 'delete' && $clientId > 0) {
        $stmt = db()->prepare('DELETE FROM clients WHERE id = ?');
        $stmt->execute([$clientId]);
        flash('success', 'Client deleted successfully.');
        header('Location: clients.php');
        exit;
    }

    $errors = [];
    if ($names === '') $errors[] = 'Client names are required.';
    if (!valid_national_id($nationalId)) $errors[] = 'National ID must be 8 to 30 digits.';
    if (!valid_phone_number($telephone)) $errors[] = 'Telephone must contain 7 to 15 digits, may start with +, and may use spaces or hyphens between digits.';
    if ($address === '') $errors[] = 'Address is required.';

    if (!$errors) {
        try {
            if ($action === 'update' && $clientId > 0) {
                $stmt = db()->prepare('UPDATE clients SET names = ?, national_id = ?, telephone = ?, address = ? WHERE id = ?');
                $stmt->execute([$names, $nationalId, $telephone, $address, $clientId]);
                flash('success', 'Client updated successfully.');
            } else {
                $stmt = db()->prepare('INSERT INTO clients (names, national_id, telephone, address, created_by) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$names, $nationalId, $telephone, $address, $admin['id']]);
                flash('success', 'Client registered successfully.');
            }
            header('Location: clients.php');
            exit;
        } catch (PDOException $exception) {
            flash('danger', 'A client with that national ID already exists.');
        }
    } else {
        flash('danger', implode(' ', $errors));
    }
}

$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$total = (int) db()->query('SELECT COUNT(*) FROM clients')->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare('SELECT * FROM clients ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$clients = $stmt->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading d-flex justify-content-between align-items-start mb-4">
    <div>
        <span class="section-kicker text-teal">Client registry</span>
        <h1 class="page-title h2 mb-1">Client management</h1>
        <p class="text-secondary mb-0">Register vehicle owners or importing clients.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xxl-4">
        <section class="content-panel registry-side-panel p-3 p-lg-4">
            <h2 class="h5 mb-3"><i class="bi bi-person-plus me-2 text-teal"></i>Register client</h2>
            <form method="post" class="vstack gap-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="form-label">Names</label>
                    <input class="form-control" name="names" required>
                </div>
                <div>
                    <label class="form-label">National ID</label>
                    <input class="form-control" name="national_id" minlength="8" maxlength="30" pattern="[0-9]{8,30}" inputmode="numeric" required>
                </div>
                <div>
                    <label class="form-label">Telephone</label>
                    <input class="form-control" type="tel" name="telephone" pattern="\+?[0-9][0-9 -]{5,18}[0-9]" required>
                </div>
                <div>
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3" required></textarea>
                </div>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Save client</button>
            </form>
        </section>
    </div>
    <div class="col-xxl-8">
        <section class="content-panel p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0"><i class="bi bi-people me-2 text-teal"></i>Registered clients</h2>
                <span class="text-secondary small"><?= $total ?> total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Names</th>
                        <th>National ID</th>
                        <th>Telephone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?= e($client['names']) ?></td>
                            <td><?= e($client['national_id']) ?></td>
                            <td><?= e($client['telephone']) ?></td>
                            <td><?= e($client['address']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#editClient<?= (int) $client['id'] ?>" title="Edit client">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-soft-danger" data-bs-toggle="modal" data-bs-target="#deleteClient<?= (int) $client['id'] ?>" title="Delete client">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                                <div class="modal fade" id="editClient<?= (int) $client['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h3 class="modal-title h5">Edit client</h3>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body vstack gap-3">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
                                                    <div>
                                                        <label class="form-label">Names</label>
                                                        <input class="form-control" name="names" value="<?= e($client['names']) ?>" required>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">National ID</label>
                                                        <input class="form-control" name="national_id" minlength="8" maxlength="30" pattern="[0-9]{8,30}" inputmode="numeric" value="<?= e($client['national_id']) ?>" required>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Telephone</label>
                                                        <input class="form-control" type="tel" name="telephone" pattern="\+?[0-9][0-9 -]{5,18}[0-9]" value="<?= e($client['telephone']) ?>" required>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Address</label>
                                                        <textarea class="form-control" name="address" rows="3" required><?= e($client['address']) ?></textarea>
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

                                <div class="modal fade" id="deleteClient<?= (int) $client['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="post">
                                                <div class="modal-header">
                                                    <h3 class="modal-title h5">Delete client</h3>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
                                                    <p class="mb-0">Delete <strong><?= e($client['names']) ?></strong>? Any linked vehicle assignment for this client will also be removed.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button class="btn btn-danger">Delete client</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$clients): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">No clients registered yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pages > 1): ?>
                <nav aria-label="Clients pagination">
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
