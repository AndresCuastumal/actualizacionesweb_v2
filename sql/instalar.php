<?php
/**
 * SCRIPT DE INSTALACIÓN
 * Ejecutar UNA SOLA VEZ en el navegador:
 * http://localhost/sistema_registro/sql/instalar.php
 * 
 * Luego BORRAR este archivo por seguridad.
 */

require_once '../config/database.php';

echo "<h2>🔧 Instalación del Sistema de Registro</h2>";
echo "<pre>";

try {
    $db = getDB();

    // Crear base de datos
    $db->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de datos '" . DB_NAME . "' creada/verificada\n";

    $db->exec("USE " . DB_NAME);

    // Tabla usuarios
    $db->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabla 'usuarios' creada\n";

    // Tabla registros
    $db->exec("
        CREATE TABLE IF NOT EXISTS registros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            td VARCHAR(10) NOT NULL,
            doc VARCHAR(20) NOT NULL,
            ape1 VARCHAR(50) NOT NULL,
            ape2 VARCHAR(50) DEFAULT NULL,
            nom1 VARCHAR(50) NOT NULL,
            nom2 VARCHAR(50) DEFAULT NULL,
            fechanace DATE NOT NULL,
            sexo ENUM('M', 'F') NOT NULL,
            mensaje TEXT DEFAULT NULL,
            parentesco VARCHAR(50) DEFAULT NULL,
            observacion TEXT DEFAULT NULL,
            nivel_sisben VARCHAR(20) DEFAULT NULL,
            ficha VARCHAR(50) DEFAULT NULL,
            discapacidad VARCHAR(100) DEFAULT NULL,
            gp VARCHAR(50) DEFAULT NULL,
            gp_sisben VARCHAR(50) DEFAULT NULL,
            registrado_por INT NOT NULL,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabla 'registros' creada\n";

    // Verificar si ya existe admin
    $stmt = $db->query("SELECT id FROM usuarios WHERE username = 'admin'");
    if (!$stmt->fetch()) {
        $hash = password_hash('Admin2024!', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO usuarios (nombre_completo, username, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute(['Administrador Principal', 'admin', 'admin@sistema.local', $hash, 'administrador']);
        echo "✅ Usuario administrador creado\n";
        echo "   Usuario: admin\n";
        echo "   Contraseña: Admin2024!\n";
    } else {
        echo "ℹ️ El usuario admin ya existe\n";
    }

    echo "\n🎉 ¡INSTALACIÓN COMPLETADA!\n";
    echo "⚠️ IMPORTANTE: Borra este archivo (sql/instalar.php) por seguridad.\n";
    echo "🔗 Accede al sistema: " . BASE_URL . "login.php\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
