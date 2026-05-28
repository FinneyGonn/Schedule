-- ============================================================
--  Migration Script — Sincroniza la BD existente con el código
--  Ejecutar después de importar Dump20260524.sql
--  Uso: mysql -u root -p horarios < migrate.sql
-- ============================================================

-- ── 1. Roles faltantes ──────────────────────────────────────
-- La BD solo tiene Administrador (1) y Usuario (2).
-- El código espera: Administrador (1), Profesor (2), Estudiante (3)
INSERT IGNORE INTO roles (id, nombre) VALUES (2, 'Profesor');
INSERT IGNORE INTO roles (id, nombre) VALUES (3, 'Estudiante');
UPDATE roles SET nombre = 'Estudiante' WHERE id = 3;

-- ── 2. Columna activo en usuarios ──────────────────────────
-- El código hace soft-delete (UPDATE usuarios SET activo = 0)
ALTER TABLE usuarios
  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1
  AFTER contrasena;

-- ── 3. Renombrar/agregar columnas en horarios ──────────────
-- La BD actual tiene: id, grupo_id, profesor_id, salon_id,
--   dia_semana (enum), hora_inicio (time), hora_fin (time), created_at
-- El código espera: id, grupo_id, nombre, dia (varchar),
--   hora_inicio (varchar), hora_fin (varchar), salon (varchar),
--   profesor (varchar), color (varchar)

-- Renombrar dia_semana → dia y cambiar de ENUM a VARCHAR
ALTER TABLE horarios
  CHANGE COLUMN dia_semana dia VARCHAR(20) NOT NULL;

-- Cambiar hora_inicio y hora_fin de TIME a VARCHAR(5) (formato HH:MM)
ALTER TABLE horarios
  MODIFY COLUMN hora_inicio VARCHAR(5) NOT NULL,
  MODIFY COLUMN hora_fin VARCHAR(5) NOT NULL;

-- Hacer profesor_id y salon_id opcionales (el código usa nombres, no IDs)
ALTER TABLE horarios
  MODIFY COLUMN profesor_id INT(11) DEFAULT NULL,
  MODIFY COLUMN salon_id INT(11) DEFAULT NULL;

-- Agregar columnas que el frontend usa
ALTER TABLE horarios
  ADD COLUMN nombre VARCHAR(200) NOT NULL DEFAULT '' AFTER grupo_id;

ALTER TABLE horarios
  ADD COLUMN color VARCHAR(10) DEFAULT 'c0' AFTER hora_fin;

ALTER TABLE horarios
  ADD COLUMN salon VARCHAR(100) DEFAULT '' AFTER color;

ALTER TABLE horarios
  ADD COLUMN profesor VARCHAR(200) DEFAULT '' AFTER salon;
