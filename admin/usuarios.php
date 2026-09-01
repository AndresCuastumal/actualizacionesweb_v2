<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requiereAdmin();

$db = getDB();
$mensaje = '';
$modo = $_GET['modo'] ?? 'listar';
$editId = $_GET['id'] ?? null;

// ========== PROCESAR FORMULARIOS ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    $accion = $_POST['accion'] ?? '';

    // CREAR USUARIO
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = $_POST['rol'] ?? 'operador';

        if (empty($nombre) || empty($username) || empty($password)) {
            flash('danger', 'Nombre, usuario y contraseña son obligatorios.');
        } elseif (strlen($password) < 6) {
            flash('danger', 'La contraseña debe tener al menos 6 caracteres.');
        } else {
            // Verificar si el usuario ya existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                flash('danger', 'El nombre de usuario ya existe.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO usuarios (nombre_completo, username, email, password_hash, rol, creado_por, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$nombre, $username, $email, $hash, $rol, $_SESSION['usuario_id']]);
                flash('success', 'Usuario creado exitosamente.');
                redirect('admin/usuarios.php');
            }
        }
    }

    // ACTUALIZAR USUARIO
    elseif ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = $_POST['rol'] ?? 'operador';
        $activo = isset($_POST['activo']) ? 1 : 0;
        $nuevaPassword = $_POST['nueva_password'] ?? '';

        if (empty($nombre)) {
            flash('danger', 'El nombre completo es obligatorio.');
        } else {
            if (!empty($nuevaPassword)) {
                if (strlen($nuevaPassword) < 6) {
                    flash('danger', 'La nueva contraseña debe tener al menos 6 caracteres.');
                    redirect('admin/usuarios.php?modo=editar&id=' . $id);
                }
                $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, rol = ?, activo = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $rol, $activo, $hash, $id]);
            } else {
                $stmt = $db->prepare("UPDATE usuarios SET nombre_completo = ?, email = ?, rol = ?, activo = ? WHERE id = ?");
                $stmt->execute([$nombre, $email, $rol, $activo, $id]);
            }
            flash('success', 'Usuario actualizado exitosamente.');
            redirect('admin/usuarios.php');
        }
    }

    // ELIMINAR USUARIO
    elseif ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        // No permitir eliminar el propio admin
        if ($id === $_SESSION['usuario_id']) {
            flash('danger', 'No puedes eliminar tu propia cuenta.');
        } else {
            $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            flash('success', 'Usuario eliminado exitosamente.');
        }
        redirect('admin/usuarios.php');
    }
}

// ========== OBTENER DATOS ==========
$usuarios = $db->query("
    SELECT u.*, c.nombre_completo as creado_por_nombre 
    FROM usuarios u 
    LEFT JOIN usuarios c ON u.creado_por = c.id 
    ORDER BY u.fecha_creacion DESC
")->fetchAll();

$usuarioEdit = null;
if ($modo === 'editar' && $editId) {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$editId]);
    $usuarioEdit = $stmt->fetch();
}

$titulo = 'Gestión de Usuarios';
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
                <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="usuarios.php">👥 Gestión de Usuarios</a></li>
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
    <?php mostrarFlash(); ?>

    <div class="row">
        <!-- FORMULARIO -->
        <div class="col-lg-4 mb-4">
            <div class="form-section">
                <h4><?php echo $modo === 'editar' ? '✏️ Editar Usuario' : '➕ Crear Nuevo Usuario'; ?></h4>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                    <input type="hidden" name="accion" value="<?php echo $modo === 'editar' ? 'editar' : 'crear'; ?>">
                    <?php if ($modo === 'editar' && $usuarioEdit): ?>
                        <input type="hidden" name="id" value="<?php echo $usuarioEdit['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" name="nombre_completo" 
                               value="<?php echo $modo === 'editar' && $usuarioEdit ? e($usuarioEdit['nombre_completo']) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Usuario *</label>
                        <input type="text" class="form-control" name="username" 
                               value="<?php echo $modo === 'editar' && $usuarioEdit ? e($usuarioEdit['username']) : ''; ?>" 
                               <?php echo $modo === 'editar' ? 'readonly' : 'required'; ?>>
                        <?php if ($modo === 'editar'): ?>
                            <small class="text-muted">El usuario no se puede modificar</small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo $modo === 'editar' && $usuarioEdit ? e($usuarioEdit['email']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select class="form-select" name="rol" required>
                            <option value="operador" <?php echo ($modo === 'editar' && $usuarioEdit && $usuarioEdit['rol'] === 'operador') ? 'selected' : ''; ?>>👷 Operador</option>
                            <option value="administrador" <?php echo ($modo === 'editar' && $usuarioEdit && $usuarioEdit['rol'] === 'administrador') ? 'selected' : ''; ?>>🔧 Administrador</option>
                        </select>
                    </div>

                    <?php if ($modo === 'editar' && $usuarioEdit): ?>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña (dejar en blanco para no cambiar)</label>
                            <input type="password" class="form-control" name="nueva_password" placeholder="Mínimo 6 caracteres">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="activo" id="activo" 
                                   <?php echo $usuarioEdit['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">Usuario activo</label>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" name="password" required placeholder="Mínimo 6 caracteres">
                        </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary-custom">
                            <?php echo $modo === 'editar' ? '💾 Guardar Cambios' : '➕ Crear Usuario'; ?>
                        </button>
                        <?php if ($modo === 'editar'): ?>
                            <a href="usuarios.php" class="btn btn-outline-secondary">↩️ Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- LISTADO -->
        <div class="col-lg-8">
            <div class="form-section">
                <h4>👥 Usuarios del Sistema</h4>
                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Creado por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                            <tr class="<?php echo !$u['activo'] ? 'table-secondary' : ''; ?>">
                                <td><?php echo $u['id']; ?></td>
                                <td><strong><?php echo e($u['nombre_completo']); ?></strong></td>
                                <td><?php echo e($u['username']); ?></td>
                                <td>
                                    <?php if ($u['rol'] === 'administrador'): ?>
                                        <span class="badge-rol-admin">🔧 Admin</span>
                                    <?php else: ?>
                                        <span class="badge-rol-operador">👷 Operador</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['activo']): ?>
                                        <span class="badge bg-success">✅ Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">⛔ Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($u['creado_por_nombre'] ?? 'Sistema'); ?></td>
                                <td>
                                    <a href="?modo=editar&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning">✏️</a>
                                    <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('¿Eliminar este usuario?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($usuarios)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No hay usuarios registrados</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="footer-app">
    Sistema de Registro © <?php echo date('Y'); ?> - Alcaldía
</div>

<?php include '../includes/footer.php'; ?>
