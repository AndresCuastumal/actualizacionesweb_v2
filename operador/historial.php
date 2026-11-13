<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requiereOperador();

$db = getDBConsultas();

// Paginación
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 15;
$offset = ($pagina - 1) * $porPagina;

// Búsqueda
$busqueda = trim($_GET['busqueda'] ?? '');
$params = [$_SESSION['usuario_id']];
$where = "WHERE USUARIO_REGISTRO = ?";

if (!empty($busqueda)) {
    $where .= " AND (DOC LIKE ? OR NOM1 LIKE ? OR APE1 LIKE ?)";
    $like = '%' . $busqueda . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Total registros
$stmt = $db->prepare("SELECT COUNT(*) FROM ppna $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPaginas = (int) ceil($total / $porPagina);

// Registros de la página actual (con alias para que el HTML no cambie)
$stmt = $db->prepare("
    SELECT TD           AS td,
           DOC          AS doc,
           NOM1         AS nom1,
           NOM2         AS nom2,
           APE1         AS ape1,
           APE2         AS ape2,
           SEXO         AS sexo,
           FECHANACE    AS fechanace,
           NIVEL_SISBEN AS nivel_sisben,
           FECREPORTE   AS fecha_registro
    FROM ppna
    $where
    ORDER BY FECREPORTE DESC
    LIMIT ? OFFSET ?
");
$params[] = $porPagina;
$params[] = $offset;
$stmt->execute($params);
$registros = $stmt->fetchAll();

$titulo = 'Mis Registros';
include '../includes/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="formulario.php">🏛️ Sistema de Registro</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="formulario.php">📝 Nuevo Registro</a></li>
                <li class="nav-item"><a class="nav-link active" href="historial.php">📋 Mis Registros</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        👤 <?php echo e($_SESSION['usuario_nombre']); ?> (Operador)
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
    <?php mostrarFlash(); ?>

    <div class="form-section">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h4>📋 Mis Registros (<?php echo $total; ?> total)</h4>
            <a href="formulario.php" class="btn btn-primary-custom">➕ Nuevo Registro</a>
        </div>

        <!-- Buscador -->
        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="busqueda" placeholder="Buscar por documento, nombre o apellido..." 
                       value="<?php echo e($busqueda); ?>">
                <button type="submit" class="btn btn-outline-primary">🔍 Buscar</button>
                <?php if ($busqueda): ?>
                    <a href="historial.php" class="btn btn-outline-secondary">🔄 Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-custom table-hover">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Nombre Completo</th>
                        <th>Sexo</th>
                        <th>Fecha Nac.</th>
                        <th>Nivel SISBEN</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><strong><?php echo e($r['td'] . ' ' . $r['doc']); ?></strong></td>
                        <td><?php echo e($r['nom1'] . ' ' . $r['nom2'] . ' ' . $r['ape1'] . ' ' . $r['ape2']); ?></td>
                        <td><?php echo $r['sexo'] === 'M' ? '♂️ Masculino' : '♀️ Femenino'; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($r['fechanace'])); ?></td>
                        <td><?php echo e($r['nivel_sisben'] ?: '-'); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($r['fecha_registro'])); ?></td>
                        <td>
                            <a href="http://appsalud.pasto.gov.co:8011/consultasweb/ppna/reporte.php?doc=<?php echo urlencode($r['doc']); ?>"
                            target="_blank"
                            class="btn btn-sm btn-primary">
                                📄 Imprimir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($registros)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <?php echo $busqueda ? 'No se encontraron resultados para tu búsqueda.' : 'Aún no has realizado registros.'; ?>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Paginación">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>">← Anterior</a>
                </li>
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>">Siguiente →</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<div class="footer-app">
    Sistema de Registro © <?php echo date('Y'); ?> - Alcaldía
</div>

<?php include '../includes/footer.php'; ?>
