<?php
// 1. Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Conexión
require 'config/conexion.php';

// 3. Proteger la página: solo usuarios logueados
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 4. Variables para repoblar el formulario
$errores = [];
$titulo = $empresa = $ubicacion = $descripcion = $tipo_pago = '';
$salario = '';
$categoria_id = '';

// 5. Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo       = trim($_POST['titulo'] ?? '');
    $categoria_id = $_POST['categoria_id'] ?? '';
    $empresa      = trim($_POST['empresa'] ?? '');
    $ubicacion    = trim($_POST['ubicacion'] ?? '');
    $salario      = trim($_POST['salario'] ?? '');
    $tipo_pago    = trim($_POST['tipo_pago'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');

    // --- Validaciones ---
    if ($titulo === '') {
        $errores[] = "El título del empleo es obligatorio.";
    }

    if ($categoria_id === '') {
        $errores[] = "Debes seleccionar una categoría.";
    }

    if ($ubicacion === '') {
        $errores[] = "La ubicación es obligatoria.";
    }

    if ($salario === '' || !is_numeric($salario) || $salario < 0) {
        $errores[] = "El salario debe ser un número mayor o igual a 0.";
    }

    // Validar que la categoría exista en la BD
    if (empty($errores) && $categoria_id !== '') {
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ?");
        $stmt->execute([$categoria_id]);
        if (!$stmt->fetch()) {
            $errores[] = "La categoría seleccionada no es válida.";
        }
    }

    // --- Insertar ---
    if (empty($errores)) {
        $sql = "INSERT INTO empleos 
                (usuario_id, categoria_id, titulo, empresa, descripcion, ubicacion, salario, tipo_pago)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_SESSION['usuario_id'],
            $categoria_id,
            $titulo,
            $empresa,
            $descripcion,
            $ubicacion,
            $salario,
            $tipo_pago
        ]);

        // Redirigir al detalle del empleo recién creado
        $nuevo_id = $pdo->lastInsertId();
        header("Location: ver-detalles.php?id=$nuevo_id&creado=ok");
        exit;
    }
}

// 6. Consultar categorías para el <select>
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// 7. Incluir header
include 'includes/header.php';
?>

<!-- ================= FORMULARIO ================= -->
<section class="container my-5" style="max-width: 700px;">

    <div class="text-center mb-4">
        <h1 class="h3 fw-bold"><i class="bi bi-megaphone"></i> Publicar oferta de empleo</h1>
        <p class="text-muted">Completa los datos y tu oferta aparecerá en el portal</p>
    </div>

    <!-- Errores -->
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

        <!-- Título -->
        <div class="mb-3">
            <label for="titulo" class="form-label">Título del empleo *</label>
            <input type="text" id="titulo" name="titulo" class="form-control"
                   placeholder="Ej. Mecánico automotriz"
                   value="<?= htmlspecialchars($titulo) ?>" required>
        </div>

        <!-- Categoría -->
        <div class="mb-3">
            <label for="categoria_id" class="form-label">Categoría *</label>
            <select id="categoria_id" name="categoria_id" class="form-select" required>
                <option value="">-- Selecciona una categoría --</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($categoria_id == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Empresa -->
        <div class="mb-3">
            <label for="empresa" class="form-label">Empresa o taller</label>
            <input type="text" id="empresa" name="empresa" class="form-control"
                   placeholder="Ej. Taller El Puma"
                   value="<?= htmlspecialchars($empresa) ?>">
        </div>

        <!-- Ubicación -->
        <div class="mb-3">
            <label for="ubicacion" class="form-label">Ubicación *</label>
            <input type="text" id="ubicacion" name="ubicacion" class="form-control"
                   placeholder="Ej. Acambay, Estado de México"
                   value="<?= htmlspecialchars($ubicacion) ?>" required>
        </div>

        <!-- Salario + Tipo de pago -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="salario" class="form-label">Salario *</label>
                <input type="number" step="0.01" min="0" id="salario" name="salario"
                       class="form-control" placeholder="Ej. 1500"
                       value="<?= htmlspecialchars($salario) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="tipo_pago" class="form-label">Tipo de pago</label>
                <select id="tipo_pago" name="tipo_pago" class="form-select">
                    <option value="">-- Selecciona --</option>
                    <option value="Por servicio" <?= $tipo_pago === 'Por servicio' ? 'selected' : '' ?>>Por servicio</option>
                    <option value="Por hora"     <?= $tipo_pago === 'Por hora'     ? 'selected' : '' ?>>Por hora</option>
                    <option value="Diario"       <?= $tipo_pago === 'Diario'       ? 'selected' : '' ?>>Diario</option>
                    <option value="Semanal"      <?= $tipo_pago === 'Semanal'      ? 'selected' : '' ?>>Semanal</option>
                    <option value="Mensual"      <?= $tipo_pago === 'Mensual'      ? 'selected' : '' ?>>Mensual</option>
                </select>
            </div>
        </div>

        <!-- Descripción -->
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción del empleo</label>
            <textarea id="descripcion" name="descripcion" class="form-control" rows="4"
                      placeholder="Describe las actividades, requisitos, horario..."><?= htmlspecialchars($descripcion) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-send"></i> Publicar oferta
        </button>

    </form>
</section>

<?php include 'includes/footer.php'; ?>