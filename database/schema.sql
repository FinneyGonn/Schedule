-- ============================================================
--  Esquema de Base de Datos — Gestor de Horarios
--  Base de datos: horarios
-- ============================================================

CREATE DATABASE IF NOT EXISTS horarios
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE horarios;

-- ── Roles de usuario ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS roles (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    nombre  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (id, nombre) VALUES
    (1, 'Administrador'),
    (2, 'Profesor'),
    (3, 'Estudiante')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ── Usuarios ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    Nombre     VARCHAR(100) NOT NULL,
    Apellido   VARCHAR(100) NOT NULL,
    nickname   VARCHAR(100) NOT NULL UNIQUE,
    correo     VARCHAR(255) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol_id     INT NOT NULL DEFAULT 3,
    activo     TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Grupos ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS grupos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT,
    creado_por  INT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Relación Grupo ↔ Usuario (miembros) ────────────────────
CREATE TABLE IF NOT EXISTS grupo_usuario (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id  INT NOT NULL,
    usuario_id INT NOT NULL,
    UNIQUE KEY uq_grupo_usuario (grupo_id, usuario_id),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Salones ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS salones (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL UNIQUE,
    capacidad INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Bloques de horario ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS horarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id    INT NOT NULL,
    nombre      VARCHAR(200) NOT NULL,
    dia         VARCHAR(20) NOT NULL,
    hora_inicio VARCHAR(5) NOT NULL,
    hora_fin    VARCHAR(5) NOT NULL,
    salon       VARCHAR(100) DEFAULT '',
    profesor    VARCHAR(200) DEFAULT '',
    color       VARCHAR(10) DEFAULT 'c0',
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Solicitudes de cambio de rol ───────────────────────────
CREATE TABLE IF NOT EXISTS solicitudes_rol (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT NOT NULL,
    rol_solicitado_id INT NOT NULL,
    rol_anterior_id  INT DEFAULT NULL,
    motivo_solicitud TEXT,
    estado           ENUM('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (rol_solicitado_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Notificaciones ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notificaciones (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    asunto     VARCHAR(255) NOT NULL,
    mensaje    TEXT NOT NULL,
    tipo       VARCHAR(50) DEFAULT 'Sistema',
    leida      TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
