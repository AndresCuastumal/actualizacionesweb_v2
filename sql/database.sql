-- =====================================================
-- SISTEMA DE REGISTRO - BASE DE DATOS
-- Ejecutar este script en phpMyAdmin o MySQL CLI
-- =====================================================

-- Crear la base de datos (cambia el nombre si lo deseas)
CREATE DATABASE IF NOT EXISTS sistema_registro 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE sistema_registro;

-- =====================================================
-- TABLA: usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100),
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'operador') NOT NULL DEFAULT 'operador',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_por INT DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: registros (formulario del operador)
-- =====================================================
CREATE TABLE IF NOT EXISTS registros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    td VARCHAR(10) NOT NULL COMMENT 'Tipo de documento',
    doc VARCHAR(20) NOT NULL COMMENT 'Número de documento',
    ape1 VARCHAR(50) NOT NULL COMMENT 'Primer apellido',
    ape2 VARCHAR(50) DEFAULT NULL COMMENT 'Segundo apellido',
    nom1 VARCHAR(50) NOT NULL COMMENT 'Primer nombre',
    nom2 VARCHAR(50) DEFAULT NULL COMMENT 'Segundo nombre',
    fechanace DATE NOT NULL COMMENT 'Fecha de nacimiento',
    sexo ENUM('M', 'F') NOT NULL COMMENT 'Sexo',
    mensaje TEXT DEFAULT NULL,
    parentesco VARCHAR(50) DEFAULT NULL,
    observacion TEXT DEFAULT NULL,
    nivel_sisben VARCHAR(20) DEFAULT NULL,
    ficha VARCHAR(50) DEFAULT NULL,
    discapacidad VARCHAR(100) DEFAULT NULL,
    gp VARCHAR(50) DEFAULT NULL COMMENT 'Grupo poblacional',
    gp_sisben VARCHAR(50) DEFAULT NULL COMMENT 'Grupo poblacional SISBEN',

    -- Metadatos
    registrado_por INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTAR USUARIO ADMINISTRADOR POR DEFECTO
-- Contraseña: Admin2024!
-- IMPORTANTE: Cambiar esta contraseña en producción
-- =====================================================
INSERT INTO usuarios (nombre_completo, username, email, password_hash, rol, activo) 
VALUES (
    'Administrador Principal',
    'admin',
    'admin@sistema.local',
    '$2y$10$N9qo8uLOickB2sBjCZ1Y.uRq1z1z1z1z1z1z1z1z1z1z1z1z1z1z1z',
    'administrador',
    1
);

-- NOTA: El hash de arriba es un placeholder.
-- Para generar el hash real de 'Admin2024!', ejecuta en PHP:
-- echo password_hash('Admin2024!', PASSWORD_DEFAULT);
-- Luego actualiza el registro con el hash generado.
