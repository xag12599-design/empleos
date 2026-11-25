<?php
// 1. Sesión y conexión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config/conexion.php';

// 2. Validar que venga un ID válido por GET
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: empleos.php");
    exit;
}

// 3. Buscar el empleo en la BD (JOIN con categorías y usuarios)
$sql = "SELECT e.*, 
               c.nombre AS categoria,
               u.nombre AS empleador_nombre,
               u.telefono AS empleador_telefono,
               u.email AS empleador_email
        FROM empleos e
        JOIN categorias c ON c.id = e.categoria_id
        JOIN usuarios u ON u.id = e.usuario_id
        WHERE e.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$empleo = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. Si no existe, mostrar 404 amigable
if (!$empleo) {
    include 'includes/header.php';
    ?>
    <section class="container my-5 text-center">
        <h1 class="display-5 text-danger">404</h1>
        <p class="fs-5">La oferta que buscas no existe o fue eliminada.</p>
        <a href="empleos.php" class="btn btn-primary">Ver todas las ofertas</a>
    </section>
    <?php
    include 'includes/footer.php';
    exit;
}

// 5. Variables para el proceso de postulación
$mensaje_exito = null;
$mensaje_error = null;

// ¿El usuario ya se postuló?
$ya_postulado = false;
if (isset($_SESSION['usuario_id'])) {
    $stmt2 = $pdo->prepare("SELECT id FROM postulaciones WHERE empleo_id = ? AND usuario_id = ?");
    $stmt2->execute([$id, $_SESSION['usuario_id']]);
    $ya_postulado = (bool) $stmt2->fetch();
}

// 6. Procesar postulación (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['postularme'])) {

    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }

    $mensaje = trim($_POST['mensaje'] ?? '');

    if ($ya_postulado) {
        $mensaje_error = "Ya te has postulado a esta oferta anteriormente.";
    } else {
        try {
            $stmt3 = $pdo->prepare("INSERT INTO postulaciones (empleo_id, usuario_id, mensaje)
                                    VALUES (?, ?, ?)");
            $stmt3->execute([$id, $_SESSION['usuario_id'], $mensaje]);
            $mensaje_exito = "¡Tu postulación se envió con éxito!";
            $ya_postulado = true;
        } catch (PDOException $e) {
            // Si falla por UNIQUE (duplicado)
            if ($e->getCode() === '23000') {
                $mensaje_error = "Ya te postulaste a esta oferta.";
            } else {
                $mensaje_error = "Ocurrió un error al postularte. Intenta de nuevo.";
            }
        }
    }
}

// 7. Incluir header
include 'includes/header.php';
?>

<!-- ================= DETALLE DEL EMPLEO ================= -->
<section class="container my-5">

    <!-- Mensaje si viene de publicar-empleo.php -->
    <?php if (isset($_GET['creado']) && $_GET['creado'] === 'ok'): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> ¡Tu oferta se publicó correctamente!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Mensajes de postulación -->
    <?php if ($mensaje_exito): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($mensaje_exito) ?>
        </div>
    <?php endif; ?>
    <?php if ($mensaje_error): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($mensaje_error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- Columna principal -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($empleo['categoria']) ?></span>
                    <h1 class="h3 fw-bold"><?= htmlspecialchars($empleo['titulo']) ?></h1>

                    <p class="text-muted mb-1">
                        <i class="bi bi-building"></i>
                        <?= htmlspecialchars($empleo['empresa'] ?: 'Sin empresa registrada') ?>
                    </p>
                    <p class="text-muted mb-1">
                        <i class="bi bi-geo-alt"></i>
                        <?= htmlspecialchars($empleo['ubicacion']) ?>
                    </p>
                    <p class="text-muted mb-3">
                        <i class="bi bi-calendar"></i>
                        Publicado el <?= date('d/m/Y', strtotime($empleo['creado_en'])) ?>
                    </p>

                    <hr>

                    <h5 class="fw-bold">Descripción</h5>
                    <p><?= nl2br(htmlspecialchars($empleo['descripcion'] ?: 'Sin descripción.')) ?></p>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small">Salario</span>
                            <div class="h4 text-success mb-0">
                                $<?= number_format($empleo['salario'], 2) ?>
                                <small class="text-muted fs-6"><?= htmlspecialchars($empleo['tipo_pago']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna lateral: contacto + postulación -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-person-badge"></i> Sobre el empleador</h6>
                    <p class="mb-1"><strong><?= htmlspecialchars($empleo['empleador_nombre']) ?></strong></p>
                    <?php if ($empleo['empleador_telefono']): ?>
                        <p class="mb-1 text-muted small">
                            <i class="bi bi-telephone"></i> <?= htmlspecialchars($empleo['empleador_telefono']) ?>
                        </p>
                    <?php endif; ?>
                    <p class="mb-0 text-muted small">
                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($empleo['empleador_email']) ?>
                    </p>
                </div>
            </div>

            <!-- Formulario de postulación -->
            <?php if (!isset($_SESSION['usuario_id'])): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <a href="login.php">Inicia sesión</a> para postularte a esta oferta.
                </div>
            <?php elseif ($ya_postulado): ?>
                <div class="alert alert-success text-center">
                    <i class="bi bi-check2-circle fs-3 d-block mb-2"></i>
                    Ya te has postulado a esta oferta.
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-send"></i> Postularme</h6>
                        <form method="POST">
                            <div class="mb-2">
                                <label for="mensaje" class="form-label small">Mensaje (opcional)</label>
                                <textarea id="mensaje" name="mensaje" class="form-control" rows="3"
                                          placeholder="Cuéntale al empleador por qué eres ideal..."></textarea>
                            </div>
                            <button type="submit" name="postularme" value="1" class="btn btn-primary w-100">
                                <i class="bi bi-send"></i> Enviar postulación
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <a href="empleos.php" class="btn btn-outline-secondary w-100 mt-3">
                <i class="bi bi-arrow-left"></i> Volver a las ofertas
            </a>
        </div>

    </div>
</section>

<?php include 'includes/footer.php'; ?>