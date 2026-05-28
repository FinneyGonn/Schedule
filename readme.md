# Schedule Manager

A web-based schedule management platform for administrators, instructors, and students. Manage groups, classrooms, schedules, and role requests from a single place.

Built with PHP + MySQL + vanilla JavaScript (dark theme UI).

---

## Requirements

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL)
- MySQL Workbench (optional, for DB management)

Start **Apache** and **MySQL** from the XAMPP Control Panel.

## Project Setup

1. Clone or copy the project into `C:\xampp\htdocs\` (or your web server's document root).
2. Open the project in your browser: `http://localhost/gestor-de-horarios/` (or the appropriate folder name).

## Database Setup

### Option A: Import the dump (recommended)

1. Open MySQL Workbench or the MySQL CLI.
2. Create the database:
   ```sql
   CREATE DATABASE IF NOT EXISTS horarios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the dump file:
   ```bash
   mysql -u root -p horarios < Dump20260524.sql
   ```
4. The dump already includes sample data (users, groups, notifications, etc.).

### Option B: Run the schema from scratch

The `Dump20260524.sql` file contains both the schema and sample data. You can also create the tables manually using the structure in that file.

### Database Configuration

Edit `config/config.php` to match your MySQL credentials:

| Setting    | Default       |
|-----------|---------------|
| Host      | `localhost`   |
| User      | `root`        |
| Password  | *(empty)*     |
| Database  | `horarios`    |
| Port      | `3306` (try `3307` if it fails) |

---

## Roles & Access

| Role | ID | URL | Description |
|------|----|-----|-------------|
| Admin | 1 | `/admin.php` | Full control: users, groups, classrooms, schedules, role requests, global notifications |
| Instructor | 2 | `/instru.html` | Create and manage own groups, generate join codes, view members, request admin role |
| Student | 3 | `/user.html` | Join groups via code, view schedule, classmates, notifications, request role change |

---

## Features

### Admin Panel (`admin.php`)

- **Dashboard** — Stats overview (users, groups, classrooms, schedules, pending requests)
- **Schedule Editor** — Visual weekly grid with drag-to-add blocks, color-coded subjects, multi-hour spanning
- **User Management** — Create, list, soft-delete users (prepared statements + CSRF)
- **Group Management** — Create/delete groups with member count
- **Classroom Management** — Register classrooms with capacity
- **Role Requests** — Approve, reject, or revoke role changes with optional reason
- **Notifications** — Send system-wide announcements to all users
- **CSRF Protection** — Token-based on all mutating requests

### Instructor Panel (`instru.html`)

- **Group CRUD** — Create/delete your own groups with auto-generated 6-character join codes (unique, collision-free)
- **Group Details** — View members (name, email), copy join code, see capacity usage
- **Role Requests** — Send admin role requests, view request history with status
- **Notifications** — Receive system-wide announcements

### Student Panel (`user.html`)

- **Join Groups** — Enter a join code to enroll in a group
- **Schedule View** — Weekly schedule display
- **Groupmates** — See other members in your groups
- **Role Requests** — Request role change to Instructor or Admin
- **Notifications** — Receive system messages

---

## API Endpoints

### Public / Auth

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login.php` | Login (returns user data + rol_id) |
| POST | `/api/register.php` | Register new user (default role: Student) |
| GET | `/api/v1/csrf-token.php` | Get CSRF token for authenticated users |

### Admin (`rol_id = 1`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/stats.php` | Dashboard statistics |
| GET | `/api/v1/admin/actividad.php` | Recent role request activity |
| GET/POST/DELETE | `/api/v1/admin/usuarios.php` | User CRUD |
| GET/POST/DELETE | `/api/v1/admin/grupos.php` | Group CRUD |
| GET/POST/DELETE | `/api/v1/admin/salones.php` | Classroom CRUD |
| GET/POST/PUT/DELETE | `/api/v1/admin/horarios.php` | Schedule block CRUD |
| GET/POST/DELETE | `/api/v1/admin/solicitudes.php` | Role request management |
| GET/POST | `/api/v1/admin/notificaciones.php` | System notifications |
| POST | `/api/v1/admin/solicitudes/crear.php` | Create role request (any auth user) |

### Instructor (`rol_id = 2`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET/POST/DELETE | `/api/v1/instructor/grupos.php` | Own group CRUD (with join codes) |
| GET | `/api/v1/instructor/mis-solicitudes.php` | Own role request history |

---

## Security

- **CSRF tokens** on all state-changing requests (POST, PUT, DELETE)
- **Password hashing** via `password_hash()` (bcrypt)
- **Prepared statements** for all SQL queries (no raw SQL injection)
- **XSS prevention** via `htmlspecialchars()` on server and `esc()` helper on client
- **Session-based authentication** with role verification on every API call
- **Soft delete** for users (keeps referential integrity)

---

## Known Limitations

- The instructor panel (`instru.html`) does not include a schedule editor yet — that is managed by the admin.
- The student panel (`user.html`) has stub functions for `renderSched()` and `changeWeek()` that are not yet connected to the backend.
- The codebase uses both PDO and MySQLi (PDO in `login.php`/`register.php`, MySQLi everywhere else). Unifying to one interface is planned.
- Column names in the `usuarios` table use mixed case (`Nombre`, `Apellido`, `nickname`, `contrasena`). On Windows/MySQL case-insensitive mode this works fine.

---

## License

This project is developed and maintained by Finn. Use freely for educational and institutional purposes.
