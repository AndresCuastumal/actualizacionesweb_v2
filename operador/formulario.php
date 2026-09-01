<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requiereOperador();

// Usar getDB() para consultas de usuarios y conteos
$db = getDB();
// Usar getDBConsultas() para inserción de registros
$dbConsultas = getDBConsultas();

$mensaje = '';
$modo = 'crear';
$registro = null;
$verId = $_GET['ver'] ?? null;

// Ver detalle (también accesible para admin)
if ($verId && $_SESSION['usuario_rol'] === 'administrador') {
    $stmt = $db->prepare("SELECT * FROM registros WHERE id = ?");
    $stmt->execute([$verId]);
    $registro = $stmt->fetch();
    $modo = 'ver';
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $modo === 'crear') {
    validarCsrf();

    $datos = [
        'td' => strtoupper(trim($_POST['td'] ?? '')),
        'doc' => trim($_POST['doc'] ?? ''),
        'ape1' => strtoupper(trim($_POST['ape1'] ?? '')),
        'ape2' => strtoupper(trim($_POST['ape2'] ?? '')),
        'nom1' => strtoupper(trim($_POST['nom1'] ?? '')),
        'nom2' => strtoupper(trim($_POST['nom2'] ?? '')),
        // 'fechanace' se debe pasar a formato 'YYYY-MM-DD'
        'fechanace' => !empty($_POST['fechanace']) ? date('Y-m-d', strtotime($_POST['fechanace'])) : null,
        'sexo' => $_POST['sexo'] ?? '',
        'mensaje' => trim($_POST['mensaje'] ?? ''),
        'parentesco' => strtoupper(trim($_POST['parentesco'] ?? '')),
        'observacion' => trim($_POST['observacion'] ?? ''),
        'nivel_sisben' => strtoupper(trim($_POST['nivel_sisben'] ?? '')),
        'ficha' => strtoupper(trim($_POST['ficha'] ?? '')),
        'discapacidad' => strtoupper(trim($_POST['discapacidad'] ?? '')),
        'gp' => strtoupper(trim($_POST['gp'] ?? '')),
        'gp_sisben' => strtoupper(trim($_POST['gp_sisben'] ?? '')),
        'activo_mostrar' => 1,
        'fecreporte' => date('Y-m-d') // Por defecto activo para mostrar
    ];

    // Validaciones básicas
    $errores = [];
    if (empty($datos['td'])) $errores[] = 'Tipo de documento es obligatorio';
    if (empty($datos['doc'])) $errores[] = 'Número de documento es obligatorio';
    if (empty($datos['ape1'])) $errores[] = 'Primer apellido es obligatorio';
    if (empty($datos['nom1'])) $errores[] = 'Primer nombre es obligatorio';
    if (empty($datos['fechanace'])) $errores[] = 'Fecha de nacimiento es obligatoria';
    if (empty($datos['sexo'])) $errores[] = 'Sexo es obligatorio';

    if (empty($errores)) {
        try {
            // INSERTAR EN LA TABLA ppna DE consultasweb
            $stmt = $dbConsultas->prepare("
                INSERT INTO ppna 
                (TD, DOC, APE1, APE2, NOM1, NOM2, FECHANACE, SEXO, MENSAJE, PARENTESCO, 
                 OBSERVACION, NIVEL_SISBEN, FICHA, DISCAPACIDAD, USUARIO_REGISTRO, FECREPORTE, GP, GP_SISBEN, ACTIVO_MOSTRAR)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $datos['td'], $datos['doc'], $datos['ape1'], $datos['ape2'],
                $datos['nom1'], $datos['nom2'], $datos['fechanace'], $datos['sexo'],
                $datos['mensaje'], $datos['parentesco'], $datos['observacion'],
                $datos['nivel_sisben'], $datos['ficha'], $datos['discapacidad'],
                $_SESSION['usuario_id'], $datos['fecreporte'],
                $datos['gp'], $datos['gp_sisben'],  $datos['activo_mostrar']
            ]);

            $doc = $datos['doc'];
            $boton = "<br><br><a href='http://localhost:8011/consultasweb/ppna/reporte.php?doc={$doc}' target='_blank' class='btn btn-primary'>📄 Imprimir Reporte</a>";

            flash('success', 
                "Registro guardado exitosamente en PPNA. Documento: {$datos['td']} {$doc}" . $boton,
                true  // Permite HTML
            );
            redirect('operador/formulario.php');
            
        } catch (PDOException $e) {
            // Verificar si el error es porque la tabla no existe
            if (strpos($e->getMessage(), "Table 'consultasweb.ppna' doesn't exist") !== false) {
                flash('danger', 'Error: La tabla ppna no existe en la base de datos consultasweb. Contacte al administrador.');
            } else {
                flash('danger', 'Error al guardar el registro: ' . $e->getMessage());
            }
        }
    } else {
        $mensaje = implode('<br>', $errores);
        flash('danger', $mensaje);
    }
}

// Contar registros del operador (desde la tabla ppna en consultasweb)
try {
    $totalMisRegistros = $dbConsultas->prepare("SELECT COUNT(*) FROM ppna WHERE registrado_por = ?");
    $totalMisRegistros->execute([$_SESSION['usuario_id']]);
    $misRegistros = $totalMisRegistros->fetchColumn();
} catch (PDOException $e) {
    // Si la tabla no existe, mostrar 0
    $misRegistros = 0;
}

$titulo = 'Formulario de Registro';
include '../includes/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="formulario.php">
            <img src="../includes/img/logoNuevoSMS.png" alt="Logo" class="img-fluid" style="height: 40px;">       Sistema de Registro PPNA - Urgencias </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="formulario.php">📝 Nuevo Registro</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="historial.php">📋 Mis Registros (<?php echo $misRegistros; ?>)</a>
                </li>
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

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="form-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>📝 <?php echo $modo === 'ver' ? 'Detalle del Registro' : 'Nuevo Registro de Datos'; ?></h4>
                    <?php if ($modo === 'ver'): ?>
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn btn-outline-secondary btn-sm">↩️ Volver al Dashboard</a>
                    <?php endif; ?>
                </div>

                <form method="POST" action="" <?php echo $modo === 'ver' ? 'style="pointer-events:none;"' : ''; ?>>
                    <?php if ($modo !== 'ver'): ?>
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    <?php endif; ?>

                    <!-- SECCIÓN: IDENTIFICACIÓN -->
                    <h5 class="text-primary mb-3">🪪 Identificación</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">TD (Tipo Documento) *</label>
                            <select class="form-select" name="td" required <?php echo $modo === 'ver' ? 'disabled' : ''; ?>>
                                <option value="">Seleccione...</option>
                                <option value="CC" <?php echo ($registro && $registro['td'] === 'CC') ? 'selected' : ''; ?>>CC - Cédula de Ciudadanía</option>
                                <option value="CE" <?php echo ($registro && $registro['td'] === 'CE') ? 'selected' : ''; ?>>CE - Cédula de Extranjería</option>
                                <option value="PA" <?php echo ($registro && $registro['td'] === 'PA') ? 'selected' : ''; ?>>PA - Pasaporte</option>
                                <option value="RC" <?php echo ($registro && $registro['td'] === 'RC') ? 'selected' : ''; ?>>RC - Registro Civil</option>
                                <option value="TI" <?php echo ($registro && $registro['td'] === 'TI') ? 'selected' : ''; ?>>TI - Tarjeta de Identidad</option>
                                <option value="PEP" <?php echo ($registro && $registro['td'] === 'PEP') ? 'selected' : ''; ?>>PEP - Permiso Especial</option>
                                <option value="PPT" <?php echo ($registro && $registro['td'] === 'PPT') ? 'selected' : ''; ?>>PPT - Permiso Protección Temporal</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">DOC (Número) *</label>
                            <input type="text" class="form-control" name="doc" required 
                                   value="<?php echo $registro ? e($registro['doc']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ficha</label>
                            <input type="text" class="form-control" name="ficha" 
                                   value="<?php echo $registro ? e($registro['ficha']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sexo *</label>
                            <select class="form-select" name="sexo" required <?php echo $modo === 'ver' ? 'disabled' : ''; ?>>
                                <option value="">Seleccione...</option>
                                <option value="M" <?php echo ($registro && $registro['sexo'] === 'M') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="F" <?php echo ($registro && $registro['sexo'] === 'F') ? 'selected' : ''; ?>>Femenino</option>
                            </select>
                        </div>
                    </div>

                    <!-- SECCIÓN: NOMBRES -->
                    <h5 class="text-primary mb-3">👤 Nombres y Apellidos</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">APE1 (Primer Apellido) *</label>
                            <input type="text" class="form-control" name="ape1" required 
                                   value="<?php echo $registro ? e($registro['ape1']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">APE2 (Segundo Apellido)</label>
                            <input type="text" class="form-control" name="ape2" 
                                   value="<?php echo $registro ? e($registro['ape2']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">NOM1 (Primer Nombre) *</label>
                            <input type="text" class="form-control" name="nom1" required 
                                   value="<?php echo $registro ? e($registro['nom1']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">NOM2 (Segundo Nombre)</label>
                            <input type="text" class="form-control" name="nom2" 
                                   value="<?php echo $registro ? e($registro['nom2']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <!-- SECCIÓN: DATOS ADICIONALES -->
                    <h5 class="text-primary mb-3">📅 Información Adicional</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">FECHANACE (Fecha Nacimiento) *</label>
                            <input type="date" class="form-control" name="fechanace" required 
                                   value="<?php echo $registro ? e($registro['fechanace']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Parentesco</label>
                            <input type="text" class="form-control" name="parentesco" 
                                   value="<?php echo $registro ? e($registro['parentesco']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discapacidad</label>
                            <input type="text" class="form-control" name="discapacidad" 
                                   value="<?php echo $registro ? e($registro['discapacidad']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <!-- SECCIÓN: SISBEN -->
                    <h5 class="text-primary mb-3">🏥 Información SISBEN</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">NIVEL_SISBEN</label>
                            <select class="form-select" name="nivel_sisben" <?php echo $modo === 'ver' ? 'disabled' : ''; ?>>
                                <option value="">Seleccione...</option>
                                <option value="1" <?php echo ($registro && $registro['nivel_sisben'] === '1') ? 'selected' : ''; ?>>Nivel 1</option>
                                <option value="2" <?php echo ($registro && $registro['nivel_sisben'] === '2') ? 'selected' : ''; ?>>Nivel 2</option>
                                <option value="3" <?php echo ($registro && $registro['nivel_sisben'] === '3') ? 'selected' : ''; ?>>Nivel 3</option>
                                <option value="4" <?php echo ($registro && $registro['nivel_sisben'] === '4') ? 'selected' : ''; ?>>Nivel 4</option>
                                <option value="5" <?php echo ($registro && $registro['nivel_sisben'] === '5') ? 'selected' : ''; ?>>Nivel 5</option>
                                <option value="6" <?php echo ($registro && $registro['nivel_sisben'] === '6') ? 'selected' : ''; ?>>Nivel 6</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">GP (Grupo Poblacional)</label>
                            <input type="text" class="form-control" name="gp" 
                                   value="<?php echo $registro ? e($registro['gp']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">GP_SISBEN</label>
                            <input type="text" class="form-control" name="gp_sisben" 
                                   value="<?php echo $registro ? e($registro['gp_sisben']) : ''; ?>" 
                                   <?php echo $modo === 'ver' ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <!-- SECCIÓN: MENSAJE Y OBSERVACIONES -->
                    <h5 class="text-primary mb-3">📝 Observaciones</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Mensaje</label>
                            <textarea class="form-control" name="mensaje" rows="3" 
                                      <?php echo $modo === 'ver' ? 'readonly' : ''; ?>><?php echo $registro ? e($registro['mensaje']) : ''; ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observación</label>
                            <textarea class="form-control" name="observacion" rows="3" 
                                      <?php echo $modo === 'ver' ? 'readonly' : ''; ?>><?php echo $registro ? e($registro['observacion']) : ''; ?></textarea>
                        </div>
                    </div>

                    <?php if ($modo !== 'ver'): ?>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="reset" class="btn btn-outline-secondary btn-lg">🔄 Limpiar</button>
                        <button type="submit" class="btn btn-primary-custom btn-lg">💾 Guardar Registro</button>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <strong>Registrado por:</strong> Operador #<?php echo $registro['registrado_por']; ?> | 
                        <strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($registro['fecha_registro'])); ?>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn btn-outline-secondary btn-lg">↩️ Volver</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="footer-app">
    Sistema de Registro PPNA - Urgencias © <?php echo date('Y'); ?> - Alcaldía de Pasto | Secretaría de Salud Municipal
</div>

<?php include '../includes/footer.php'; ?>