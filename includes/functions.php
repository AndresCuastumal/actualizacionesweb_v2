<?php
/**
 * Funciones comunes del sistema
 */

require_once __DIR__ . '/../config/database.php';

session_start();

/**
 * Genera un token CSRF
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF
 */
function validarCsrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token de seguridad inválido. Recarga la página.');
    }
}

/**
 * Verifica si el usuario está logueado
 */
function requiereLogin() {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Verifica si es administrador
 */
function requiereAdmin() {
    requiereLogin();
    if ($_SESSION['usuario_rol'] !== 'administrador') {
        header('Location: ' . BASE_URL . 'operador/formulario.php');
        exit;
    }
}

/**
 * Verifica si es operador
 */
function requiereOperador() {
    requiereLogin();
    if ($_SESSION['usuario_rol'] !== 'operador') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        exit;
    }
}

/**
 * Escapa salida HTML (previene XSS)
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Muestra mensajes flash
 */
function flash($tipo, $mensaje, $permitir_html = false) {
    $_SESSION['flash'] = [
        'tipo' => $tipo, 
        'mensaje' => $mensaje,
        'permitir_html' => $permitir_html
    ];
}

function mostrarFlash($permitir_html = false) {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        $clase = $f['tipo'] === 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $clase . ' alert-dismissible fade show" role="alert">';
        
        if ($permitir_html || ($f['permitir_html'] ?? false)) {
            echo $f['mensaje'];  // Sin escapar, permite HTML
        } else {
            echo e($f['mensaje']);  // Con escape
        }
        
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['flash']);
    }
}

/**
 * Redirección segura
 */
function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}
