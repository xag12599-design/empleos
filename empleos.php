<?php
// 1. Sesión y conexión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config/conexion.php';

// 2. Recoger filtros desde la URL (GET)
$q          = trim($_GET['q'] ?? '');
$ubicacion  = trim($_GET['ubicacion'] ?? '');
$categoria  = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;

// 3. Construir la consulta dinámicamente
$sql = "SELECT e.id, e.titulo, e.empresa, e.ubicacion, e.salario, e.tipo_pago, e.creado_en,
               c.nombre AS categoria
        FROM empleos e
        JOIN categorias c ON c.id = e.categoria_id
        WHERE e.estatus = 'activo'";

$params = [];

if ($q !== '') {
    $sql .= " AND (e.titulo LIKE ? OR e.descripcion LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($ubicacion !== '') {
    $sql .= " AND e.ubicacion LIKE ?";
    $params[] = "%$ubicacion%";
}

if ($categoria > 0) {
    $sql .= " AND e.categoria_id = ?";
    $params[] = $categoria;
}

$sql .= " ORDER BY e.creado_en DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$empleos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Traer categorías para el filtro lateral
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// 5. Incluir header
include 'includes/header.php';
?>

<!-- ================= BUSCADOR SUPERIOR ================= -->
<section class="bg-primary text-white py-4">
    <div class="container">
        <h1 class="h3 fw-bold mb-3">Encuentra tu próximo empleo</h1>
        <form class="row g-2" method="GET" action="empleos.php">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control"
                       placeholder="¿Qué buscas? (Ej. mecánico, plomero...)"
                       value="<?= htmlspecialchars($q) ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="ubicacion" class="form-control"
                       placeholder="Ubicación"
                       value="<?= htmlspecialchars($ubicacion) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-warning w-100">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
        </form>
    </div>
</section>

<!-- ================= LISTADO ================= -->
<section class="container my-5">
    <div class="row g-4">

        <!-- Filtros laterales -->
        <div class="col-lg-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-funnel"></i> Categorías</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-1">
                            <a href="empleos.php<?= $q ? '?q='.urlencode($q) : '' ?>"
                               class="text-decoration-none <?= $categoria === 0 ? 'fw-bold text-primary' : 'text-dark' ?>">
                                Todas
                            </a>
                        </li>
                        <?php foreach ($categorias as $c): ?>
                            <li class="mb-1">
                                <a href="empleos.php?cat=<?= $c['id'] ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $ubicacion ? '&ubicacion='.urlencode($ubicacion) : '' ?>"
                                   class="text-decoration-none <?= $categoria === (int)$c['id'] ? 'fw-bold text-primary' : 'text-dark' ?>">
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <?php if ($q || $ubicacion || $categoria): ?>
                <a href="empleos.php" class="btn btn-outline-secondary btn-sm w-100 mt-3">
                    <i class="bi bi-x-circle"></i> Limpiar filtros
                </a>
            <?php endif; ?>
        </div>

        <!-- Resultados -->
        <div class="col-lg-9">
            <p class="text-muted">
                <strong><?= count($empleos) ?></strong> oferta(s) encontrada(s)
                <?php if ($q): ?>para "<em><?= htmlspecialchars($q) ?></em>"<?php endif; ?>
                <?php if ($ubicacion): ?>en "<em><?= htmlspecialchars($ubicacion) ?></em>"<?php endif; ?>
            </p>

            <?php if (empty($empleos)): ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-emoji-frown fs-3 d-block mb-2"></i>
                    No encontramos ofertas con esos filtros.
                    <br>
                    <a href="empleos.php" class="btn btn-sm btn-primary mt-2">Ver todas las ofertas</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($empleos as $e): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-empleo shadow-sm h-100">
                                <div class="card-body">
                                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($e['categoria']) ?></span>
                                    <h5 class="card-title"><?= htmlspecialchars($e['titulo']) ?></h5>
                                    <p class="text-muted mb-1 small">
                                        <i class="bi bi-building"></i> <?= htmlspecialchars($e['empresa'] ?: 'Sin empresa') ?>
                                    </p>
                                    <p class="text-muted mb-1 small">
                                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($e['ubicacion']) ?>
                                    </p>
                                    <p class="fw-bold text-success mb-2">
                                        $<?= number_format($e['salario'], 2) ?>
                                        <small class="text-muted fs-6"><?= htmlspecialchars($e['tipo_pago']) ?></small>
                                    </p>
                                    <a href="ver-detalles.php?id=<?= $e['id'] ?>" class="btn btn-outline-primary w-100">
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php include 'includes/footer.php'; ?>