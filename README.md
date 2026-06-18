# MAGERWA Vehicle Tracking Management System

A responsive vehicle tracking management system built for **MAGERWA, Rwanda's Public Bonded Warehouse**. The system helps authenticated admins manage clients, register vehicles, link vehicles to clients using unique plate numbers, and view paginated vehicle-client records.

## Project Preview

Add your project screenshots inside a folder named `screenshots/`, then replace the placeholder paths below.

```markdown
![Login Page](screenshots/login.png)
![Dashboard](screenshots/dashboard.png)
![Client Management](screenshots/clients.png)
![Vehicle Management](screenshots/vehicles.png)
![Vehicle Assignments](screenshots/assignments.png)
```

## Features

- Admin signup and login
- Protected pages for logged-in admins only
- Client registration, editing, and deletion
- Vehicle registration, editing, and deletion
- Vehicle-client assignment using a unique plate number
- Assignment editing and unlinking
- Paginated vehicle-client records
- Server-side and frontend validation
- Protected JSON API endpoints
- Responsive blue-and-white dashboard interface
- Bootstrap modal confirmations for destructive actions
- Auto-dismissing success alerts

## Technology Stack

- **Frontend:** HTML5, CSS3, Bootstrap 5, Bootstrap Icons
- **Backend:** Native PHP
- **Database:** MySQL / MariaDB
- **Server:** Apache through XAMPP, WAMP, or any PHP-compatible web server

## Main Modules

- **Authentication:** Admin signup, login, logout, and session protection
- **Client Management:** Create, read, update, and delete clients
- **Vehicle Management:** Create, read, update, and delete vehicles
- **Assignments:** Link vehicles to clients, update assignments, and unlink records
- **Records:** Paginated display of linked vehicle-client records
- **API:** Session-protected JSON endpoints for Postman or external clients

## Folder Structure

```text
Magerwa/
|-- assets/
|   |-- css/
|   |   `-- style.css
|   `-- magerwa-trucks-login.png
|-- includes/
|   |-- header.php
|   `-- footer.php
|-- api.php
|-- auth.php
|-- clients.php
|-- config.php
|-- index.php
|-- link_vehicle.php
|-- login.php
|-- logout.php
|-- schema.sql
|-- signup.php
|-- vehicles.php
`-- README.md
```

## Requirements

- PHP 8.0 or newer
- MySQL or MariaDB
- Apache server
- XAMPP/WAMP/Laragon or a similar local server environment
- A modern browser

## Installation

1. Clone or copy the project folder into your web server directory.

   For XAMPP on Windows:

   ```text
   C:\xampp\htdocs\magerwa
   ```

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Create the database by importing `schema.sql`.

   Using phpMyAdmin:

   - Open `http://localhost/phpmyadmin`
   - Click **Import**
   - Select `schema.sql`
   - Click **Go**

   Or using MySQL command line:

   ```sql
   SOURCE schema.sql;
   ```

4. Configure the database connection in `config.php` if needed.

   ```php
   const DB_HOST = '127.0.0.1';
   const DB_NAME = 'magerwa_vehicle_tracking';
   const DB_USER = 'root';
   const DB_PASS = '';
   ```

5. Open the application in your browser.

   ```text
   http://localhost/magerwa/login.php
   ```

## Test Admin Account

Use this account if it exists in your local database:

```text
Email: admin@magerwa.rw
Password: Admin12345
```

If it does not exist, create a new admin from:

```text
http://localhost/magerwa/signup.php
```

## Validation Rules

- Email must be valid.
- Password must contain at least 8 characters, including uppercase, lowercase, and a number.
- Phone number must contain 7 to 20 characters and may start with `+`.
- National ID must contain 8 to 30 letters, numbers, or hyphens.
- Chassis number must contain 6 to 80 letters, numbers, or hyphens.
- Plate number must contain 3 to 30 letters, numbers, spaces, or hyphens.
- Manufacture year must be between `1900` and the next calendar year.
- Price must be a valid positive amount.

## API Usage

Login first and keep the returned PHP session cookie in Postman.

```http
POST /api.php?action=login
Content-Type: application/json

{
  "email": "admin@magerwa.rw",
  "password": "Admin12345"
}
```

### Client Endpoints

```http
GET /api.php?action=clients
POST /api.php?action=clients
PUT /api.php?action=clients
DELETE /api.php?action=clients&id=1
```

Example create client body:

```json
{
  "names": "Jean Claude Ndayisaba",
  "national_id": "1199001122334455",
  "telephone": "0788123456",
  "address": "Kigali, Nyarugenge"
}
```

### Vehicle Endpoints

```http
GET /api.php?action=vehicles
POST /api.php?action=vehicles
PUT /api.php?action=vehicles
DELETE /api.php?action=vehicles&id=1
```

Example create vehicle body:

```json
{
  "chassis_number": "JTDBR32E720123456",
  "manufacture_company": "Toyota",
  "manufacture_year": 2021,
  "price": 18500000,
  "model_name": "Corolla"
}
```

### Assignment Endpoints

```http
POST /api.php?action=links
PUT /api.php?action=links
DELETE /api.php?action=links&id=1
GET /api.php?action=records&page=1&per_page=10
```

Example create assignment body:

```json
{
  "client_id": 1,
  "vehicle_id": 1,
  "plate_number": "RAA 123 A"
}
```

## Important Pages

```text
/signup.php          Admin registration
/login.php           Admin login
/index.php           Dashboard
/clients.php         Client management
/vehicles.php        Vehicle management
/link_vehicle.php    Vehicle-client assignments and records
/api.php             JSON API endpoints
```

## Notes

- The system uses PHP sessions, so admins must be logged in before accessing protected pages or API endpoints.
- Deleting a client or vehicle also removes related vehicle-client assignments because of database foreign key cascade rules.
- Bootstrap, Bootstrap Icons, and Google Fonts are loaded from CDNs.
- For offline demos, download those assets locally and update the links.

## Author

**UWIMANA Krif**

Built with love as a MAGERWA vehicle tracking management system project.
