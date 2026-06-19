<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    return is_array($data) ? $data : $_POST;
}

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function validation_error(string $message): void
{
    respond(['success' => false, 'message' => $message], 422);
}

function validate_client_payload(array $data): array
{
    $names = clean_string($data['names'] ?? '');
    $nationalId = clean_string($data['national_id'] ?? '');
    $telephone = clean_string($data['telephone'] ?? '');
    $address = clean_string($data['address'] ?? '');

    if ($names === '') validation_error('Client names are required.');
    if (!valid_national_id($nationalId)) validation_error('National ID must be 8 to 30 digits.');
    if (!valid_phone_number($telephone)) validation_error('Telephone must contain 7 to 15 digits, may start with +, and may use spaces or hyphens between digits.');
    if ($address === '') validation_error('Address is required.');

    return [$names, $nationalId, $telephone, $address];
}

function validate_vehicle_payload(array $data): array
{
    $chassis = strtoupper(clean_string($data['chassis_number'] ?? ''));
    $company = clean_string($data['manufacture_company'] ?? '');
    $year = (int) ($data['manufacture_year'] ?? 0);
    $price = (float) ($data['price'] ?? -1);
    $model = clean_string($data['model_name'] ?? '');

    if (!valid_chassis_number($chassis)) validation_error('Chassis/VIN must be 17 letters or numbers and cannot include I, O, or Q.');
    if ($company === '') validation_error('Manufacture company is required.');
    if (!valid_year($year)) validation_error('Manufacture year must be between 1901 and the current year.');
    if (!valid_price($price)) validation_error('Price must be greater than zero.');
    if ($model === '') validation_error('Model name is required.');

    return [$chassis, $company, $year, $price, $model];
}

function validate_link_payload(array $data): array
{
    $clientId = (int) ($data['client_id'] ?? 0);
    $vehicleId = (int) ($data['vehicle_id'] ?? 0);
    $plate = strtoupper(clean_string($data['plate_number'] ?? ''));

    if ($clientId <= 0) validation_error('Client ID is required.');
    if ($vehicleId <= 0) validation_error('Vehicle ID is required.');
    if (!valid_plate_number($plate)) validation_error('Plate number must follow the Rwanda format, for example RAA 123 A.');

    return [$clientId, $vehicleId, $plate];
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

    if ($action === 'login' && $method === 'POST') {
    $data = json_input();
    $email = strtolower(clean_string($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if (!valid_email_address($email) || $password === '') {
        respond(['success' => false, 'message' => 'Valid email and password are required.'], 422);
    }

    $stmt = db()->prepare('SELECT id, password_hash, names, email FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        respond(['success' => true, 'admin' => ['id' => $admin['id'], 'names' => $admin['names'], 'email' => $admin['email']]]);
    }

    respond(['success' => false, 'message' => 'Invalid credentials.'], 401);
}

$admin = current_admin();
if (!$admin) {
    respond(['success' => false, 'message' => 'Authentication required. Login first.'], 401);
}

try {
    if ($action === 'clients' && $method === 'GET') {
        $clients = db()->query('SELECT id, names, national_id, telephone, address, created_at FROM clients ORDER BY created_at DESC')->fetchAll();
        respond(['success' => true, 'data' => $clients]);
    }

    if ($action === 'clients' && $method === 'POST') {
        $data = json_input();
        [$names, $nationalId, $telephone, $address] = validate_client_payload($data);
        $stmt = db()->prepare('INSERT INTO clients (names, national_id, telephone, address, created_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $names,
            $nationalId,
            $telephone,
            $address,
            $admin['id'],
        ]);
        respond(['success' => true, 'id' => db()->lastInsertId()], 201);
    }

    if ($action === 'clients' && in_array($method, ['PUT', 'PATCH'], true)) {
        $data = json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) validation_error('Client ID is required.');
        [$names, $nationalId, $telephone, $address] = validate_client_payload($data);
        $stmt = db()->prepare('UPDATE clients SET names = ?, national_id = ?, telephone = ?, address = ? WHERE id = ?');
        $stmt->execute([
            $names,
            $nationalId,
            $telephone,
            $address,
            $id,
        ]);
        respond(['success' => true, 'updated' => $stmt->rowCount()]);
    }

    if ($action === 'clients' && $method === 'DELETE') {
        $data = json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) validation_error('Client ID is required.');
        $stmt = db()->prepare('DELETE FROM clients WHERE id = ?');
        $stmt->execute([$id]);
        respond(['success' => true, 'deleted' => $stmt->rowCount()]);
    }

    if ($action === 'vehicles' && $method === 'GET') {
        $vehicles = db()->query('SELECT id, chassis_number, manufacture_company, manufacture_year, price, model_name, created_at FROM vehicles ORDER BY created_at DESC')->fetchAll();
        respond(['success' => true, 'data' => $vehicles]);
    }

    if ($action === 'vehicles' && $method === 'POST') {
        $data = json_input();
        [$chassis, $company, $year, $price, $model] = validate_vehicle_payload($data);
        $stmt = db()->prepare(
            'INSERT INTO vehicles (chassis_number, manufacture_company, manufacture_year, price, model_name, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $chassis,
            $company,
            $year,
            $price,
            $model,
            $admin['id'],
        ]);
        respond(['success' => true, 'id' => db()->lastInsertId()], 201);
    }

    if ($action === 'vehicles' && in_array($method, ['PUT', 'PATCH'], true)) {
        $data = json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) validation_error('Vehicle ID is required.');
        [$chassis, $company, $year, $price, $model] = validate_vehicle_payload($data);
        $stmt = db()->prepare(
            'UPDATE vehicles
             SET chassis_number = ?, manufacture_company = ?, manufacture_year = ?, price = ?, model_name = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $chassis,
            $company,
            $year,
            $price,
            $model,
            $id,
        ]);
        respond(['success' => true, 'updated' => $stmt->rowCount()]);
    }

    if ($action === 'vehicles' && $method === 'DELETE') {
        $data = json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) validation_error('Vehicle ID is required.');
        $stmt = db()->prepare('DELETE FROM vehicles WHERE id = ?');
        $stmt->execute([$id]);
        respond(['success' => true, 'deleted' => $stmt->rowCount()]);
    }

    if ($action === 'links' && $method === 'POST') {
        $data = json_input();
        [$clientId, $vehicleId, $plate] = validate_link_payload($data);
        $stmt = db()->prepare('INSERT INTO vehicle_client_links (client_id, vehicle_id, plate_number, linked_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $clientId,
            $vehicleId,
            $plate,
            $admin['id'],
        ]);
        respond(['success' => true, 'id' => db()->lastInsertId()], 201);
    }

    if ($action === 'links' && in_array($method, ['PUT', 'PATCH'], true)) {
        $data = json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) validation_error('Link ID is required.');
        [$clientId, $vehicleId, $plate] = validate_link_payload($data);
        $stmt = db()->prepare(
            'UPDATE vehicle_client_links
             SET client_id = ?, vehicle_id = ?, plate_number = ?, linked_by = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $clientId,
            $vehicleId,
            $plate,
            $admin['id'],
            $id,
        ]);
        respond(['success' => true, 'updated' => $stmt->rowCount()]);
    }

    if ($action === 'links' && $method === 'DELETE') {
        $data = json_input();
        $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) validation_error('Link ID is required.');
        $stmt = db()->prepare('DELETE FROM vehicle_client_links WHERE id = ?');
        $stmt->execute([$id]);
        respond(['success' => true, 'deleted' => $stmt->rowCount()]);
    }

    if ($action === 'records' && $method === 'GET') {
        $perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 10)));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $total = (int) db()->query('SELECT COUNT(*) FROM vehicle_client_links')->fetchColumn();

        $stmt = db()->prepare(
            'SELECT l.id, l.plate_number, l.linked_at,
                    c.names AS client_name, c.national_id AS client_national_id, c.telephone, c.address,
                    v.chassis_number, v.manufacture_company, v.manufacture_year, v.price, v.model_name
             FROM vehicle_client_links l
             JOIN clients c ON c.id = l.client_id
             JOIN vehicles v ON v.id = l.vehicle_id
             ORDER BY l.linked_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        respond([
            'success' => true,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => (int) ceil($total / $perPage),
            ],
            'data' => $stmt->fetchAll(),
        ]);
    }

    respond(['success' => false, 'message' => 'Unknown endpoint.'], 404);
} catch (PDOException $exception) {
    respond(['success' => false, 'message' => 'Database operation failed.', 'detail' => $exception->getMessage()], 422);
}
