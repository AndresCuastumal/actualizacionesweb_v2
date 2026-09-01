<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requiereAdmin();

$db = getDB();

// Estadísticas
$totalOperadores = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'operador'")->fetchColumn();
$totalRegistros = $db->query("SELECT COUNT(*) FROM registros")->fetchColumn();
$registrosHoy = $db->query("SELECT COUNT(*) FROM registros WHERE DATE(fecha_registro) = CURDATE()")->fetchColumn();

// Últimos registros
$ultimosRegistros = $db->query("
    SELECT r.*, u.nombre_completo as operador 
    FROM registros r 
    JOIN usuarios u ON r.registrado_por = u.id 
    ORDER BY r.fecha_registro DESC 
    LIMIT 10
")->fetchAll();

$titulo = 'Panel de Administración';
include '../includes/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">🏛️ Sistema de Registro</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">📊 Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="usuarios.php">👥 Gestión de Usuarios</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        👤 <?php echo e($_SESSION['usuario_nombre']); ?> (Admin)
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../logout.php">🚪 Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="dashboard-container">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="icon bg-admin text-white">👷</div>
                    <h5 class="card-title">Operadores</h5>
                    <h2 class="mb-0"><?php echo $totalOperadores; ?></h2>
                    <small class="text-muted">Usuarios operadores activos</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="icon bg-registro text-white">📋</div>
                    <h5 class="card-title">Total Registros</h5>
                    <h2 class="mb-0"><?php echo $totalRegistros; ?></h2>
                    <small class="text-muted">Registros en el sistema</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="icon bg-operador text-white">📅</div>
                    <h5 class="card-title">Registros Hoy</h5>
                    <h2 class="mb-0"><?php echo $registrosHoy; ?></h2>
                    <small class="text-muted">Registros realizados hoy</small>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>📋 Últimos Registros</h4>
            <a href="usuarios.php" class="btn btn-primary btn-sm">👥 Gestionar Usuarios</a>
        </div>

        <div class="table-responsive">
            <table class="table table-custom table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Documento</th>
                        <th>Nombre Completo</th>
                        <th>Operador</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimosRegistros as $reg): ?>
                    <tr>
                        <td><?php echo $reg['id']; ?></td>
                        <td><strong><?php echo e($reg['td'] . ' ' . $reg['doc']); ?></strong></td>
                        <td><?php echo e($reg['nom1'] . ' ' . $reg['nom2'] . ' ' . $reg['ape1'] . ' ' . $reg['ape2']); ?></td>
                        <td><?php echo e($reg['operador']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($reg['fecha_registro'])); ?></td>
                        <td>
                            <a href="../operador/formulario.php?ver=<?php echo $reg['id']; ?>" class="btn btn-sm btn-info">👁️ Ver</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ultimosRegistros)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay registros aún</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="footer-app">
    Sistema de Registro © <?php echo date('Y'); ?> - Alcaldía
</div>

<?php include '../includes/footer.php'; ?>
