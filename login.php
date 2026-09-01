<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir
if (!empty($_SESSION['usuario_id'])) {
    if ($_SESSION['usuario_rol'] === 'administrador') {
        redirect('admin/dashboard.php');
    } else {
        redirect('operador/formulario.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor ingresa usuario y contraseña.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, nombre_completo, username, password_hash, rol, activo FROM usuarios WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['activo']) {
                $error = 'Tu cuenta está desactivada. Contacta al administrador.';
            } else {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nombre'] = $user['nombre_completo'];
                $_SESSION['usuario_username'] = $user['username'];
                $_SESSION['usuario_rol'] = $user['rol'];

                flash('success', 'Bienvenido, ' . $user['nombre_completo']);

                if ($user['rol'] === 'administrador') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('operador/formulario.php');
                }
            }
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}

$titulo = 'Iniciar Sesión';
include 'includes/header.php';
?>

<div class="login-page">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="mb-3">
                <span style="font-size: 3rem;">
            <img src="includes/img/logoNuevoSMS.png" alt="" class="img-fluid" style="max-width: 250px;">        
            </span>
            </div>
            <h2>Sistema de Registro PPNA - Urgencias</h2>
            <p class="subtitle">Ingresa tus credenciales para continuar</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo e($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario</label>
                <input type="text" class="form-control form-control-lg" id="username" name="username" 
                       placeholder="Ingresa tu usuario" required autofocus>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control form-control-lg" id="password" name="password" 
                       placeholder="Ingresa tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary-custom w-100 btn-lg">
                🔐 Ingresar al Sistema
            </button>
        </form>        
    </div>
</div>

<?php include 'includes/footer.php'; ?>
