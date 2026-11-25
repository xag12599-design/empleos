<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config/conexion.php';

// Variables para mostrar errores y repoblar el formulario
$errores = [];
$nombre   = '';
$email    = '';
$telefono = '';
$rol      = 'buscador';

// ¿Se envió el formulario?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Recoger y limpiar datos
    $nombre   = trim($_POST['nombre']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol      = $_POST['rol']           ?? 'buscador';
    $password = $_POST['password']      ?? '';
    $confirmar = $_POST['confirmar']    ?? '';

    // 2. Validaciones
    if ($nombre === '') {
        $errores[] = "El nombre es obligatorio.";
    } elseif (mb_strlen($nombre) < 3) {
        $errores[] = "El nombre debe tener al menos 3 caracteres.";
    }

    if ($email === '') {
        $errores[] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido.";
    }

    if ($password === '') {
        $errores[] = "La contraseña es obligatoria.";
    } elseif (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if ($password !== $confirmar) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    if (!in_array($rol, ['buscador', 'empleador'])) {
        $errores[] = "Rol inválido.";
    }

    // 3. Verificar que el email no exista
    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errores[] = "Ya existe una cuenta con ese email.";
        }
    }

    // 4. Insertar en la BD si todo está bien
    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre, email, password, telefono, rol)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $email, $hash, $telefono, $rol]);

        // Redirigir al login con mensaje de éxito
        header("Location: login.php?registro=ok");
        exit;
    }
}

include 'includes/header.php';
?>

<!-- ================= FORMULARIO DE REGISTRO ================= -->
<section class="container my-5" style="max-width: 600px;">

    <div class="text-center mb-4">
        <h1 class="h3 fw-bold">Crear una cuenta</h1>
        <p class="text-muted">Únete a Cecy-Jobs y encuentra oportunidades</p>
    </div>

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
            <label for="nombre" class="form-label">Nombre completo *</label>
            <input type="text" id="nombre" name="nombre" class="form-control"
                   value="<?= htmlspecialchars($nombre) ?>" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico *</label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="mb-3">
            <label for="telefono" class="form-label">Teléfono</label>
            <input type="text" id="telefono" name="telefono" class="form-control"
                   value="<?= htmlspecialchars($telefono) ?>" placeholder="Opcional">
        </div>

        <div class="mb-3">
            <label for="rol" class="form-label">Quiero registrarme como *</label>
            <select id="rol" name="rol" class="form-select" required>
                <option value="buscador" <?= $rol === 'buscador' ? 'selected' : '' ?>>
                    Busco empleo
                </option>
                <option value="empleador" <?= $rol === 'empleador' ? 'selected' : '' ?>>
                    Ofrezco empleo
                </option>
            </select>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Contraseña *</label>
            <input type="password" id="password" name="password" class="form-control"
                   minlength="6" required>
            <div class="form-text">Mínimo 6 caracteres.</div>
        </div>

        <div class="mb-3">
            <label for="confirmar" class="form-label">Confirmar contraseña *</label>
            <input type="password" id="confirmar" name="confirmar" class="form-control"
                   minlength="6" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-person-plus"></i> Registrarme
        </button>

        <p class="text-center text-muted mt-3 mb-0 small">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
        </p>
    </form>
</section>

<?php include 'includes/footer.php'; ?>