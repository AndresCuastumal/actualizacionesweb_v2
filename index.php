<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!empty($_SESSION['usuario_id'])) {
    if ($_SESSION['usuario_rol'] === 'administrador') {
        redirect('admin/dashboard.php');
    } else {
        redirect('operador/formulario.php');
    }
}
redirect('login.php');
