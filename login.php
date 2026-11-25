<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require 'config/conexion.php';

// Si el usuario YA está logueado, lo redirigimos al inicio
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$errores = [];
$email = '';

// ¿Se envió el formulario?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validaciones básicas
    if ($email === '' || $password === '') {
        $errores[] = "Por favor, ingresa tu correo y contraseña.";
    } else {
        // Buscar al usuario en la BD
        $stmt = $pdo->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si existe y si la contraseña coincide
        if ($usuario && password_verify($password, $usuario['password'])) {
            // ¡Credenciales correctas! Creamos la sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['rol']        = $usuario['rol'];

            // Redirigir según el rol
            if ($usuario['rol'] === 'empleador') {
                header("Location: publicar-empleo.php"); // Si ofrece empleo, va a publicar
            } else {
                header("Location: empleos.php");         // Si busca, va a ver empleos
            }
            exit;
        } else {
            // Mensaje genérico para no revelar si el correo existe o no (Seguridad)
            $errores[] = "Correo o contraseña incorrectos.";
        }
    }
}

include 'includes/header.php';
?>

<!-- ================= FORMULARIO DE LOGIN ================= -->
<section class="container my-5" style="max-width: 500px;">
    <div class="text-center mb-4">
        <h1 class="h3 fw-bold">Iniciar Sesión</h1>
        <p class="text-muted">Ingresa a tu cuenta de Cecy-Jobs</p>
    </div>

    <!-- Mensaje de éxito tras registrarse -->
    <?php if (isset($_GET['registro']) && $_GET['registro'] === 'ok'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> ¡Cuenta creada con éxito! Ya puedes iniciar sesión.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Mostrar errores -->
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="card shadow-sm p-4" novalidate>
        
        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" id="email" name="email" class="form-control" 
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right"></i> Ingresar
        </button>

        <p class="text-center text-muted mt-3 mb-0 small">
            ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
        </p>
    </form>
</section>

<?php include 'includes/footer.php'; ?>