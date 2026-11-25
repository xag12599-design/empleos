<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'config/conexion.php';
include 'includes/header.php';

?>
<!-- ###sección hero### -->
<section class="hero text-black py-5">
    <div class="container py-4 text-center">
        <h1  class="display-5 fw-bold mb-3">Encuentre su primer empleo</h1>
        <p  class="fs-5 mb-4">Mas de <strong>mil ofertas</strong> de empleo </p>
        <form class="row g-2 justify-content-center" action="empleos.php" method="GET">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control form-control-lg" placeholder="Ingrese su busqueda">
            </div>
            <div class="col-md-3">
                <input type="text" name="ubicacion" class="form-control form-control-lg" placeholder="Ubicación">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-warning btn-lg w-100">
                     <i class="bi bi-search"></i> Buscar
                </button>
            </div>
        </form>
    </div>
</section>
<!-- ##sección categoria### -->
<section class="container my-5">
    <h2 class="h3 mb-4 text-center">Explora por categoria</h2>
    <div class="row g-3 text-center">
        <?php
            $categorias = $pdo->query("SELECT * FROM categorias ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($categorias as $c):
        ?>
        <div class="col-6 col-md-2">
            <a href="empleos.html?cat=<?= $c['id'] ?>" class="text-decoration-none">
                <div class="card card-empleo shadow-sm p-3">
                    <i class="<?=htmlspecialchars($c['icono']) ?> fs-2 text-danger"></i>
                    <small class="mt-2 fw-semibold"><?=htmlspecialchars($c['nombre']) ?></small>
                </div>
            </a>
        </div>
          <?php endforeach; ?> 
    </div>
</section>
<!-- ###sección ultimas ofertas### -->
<section class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0"><i class="bi bi-fire text-danger"></i> Últimas ofertas de empleo</h2>
        <a href="empleos.php" class="btn btn-outline-primary btn-sm">Mostrar todas</a>
    </div>
    <div class="row g-4">

        <?php
        $sql = "SELECT e.id, e.titulo, e.empresa, e.ubicacion, e.salario,
                       c.nombre AS categoria
                FROM empleos e
                JOIN categorias c ON c.id = e.categoria_id
                WHERE e.estatus = 'activo'
                ORDER BY e.creado_en DESC
                LIMIT 6";
        $empleos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        if (count($empleos) === 0): ?>
            <div class="col-12 text-center text-muted">
                <p>Aún no hay ofertas publicadas.</p>
            </div>
        <?php else:
            foreach ($empleos as $e): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-empleo shadow-sm h-100">
                        <div class="card-body">
                            <span class="badge bg-primary mb-2"><?= htmlspecialchars($e['categoria']) ?></span>
                            <h5 class="card-title"><?= htmlspecialchars($e['titulo']) ?></h5>
                            <p class="text-muted mb-1">
                                <i class="bi bi-building"></i> <?= htmlspecialchars($e['empresa'] ?? 'Sin empresa') ?>
                            </p>
                            <p class="text-muted mb-1">
                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($e['ubicacion']) ?>
                            </p>
                            <p class="fw-bold text-success mb-2">
                                $<?= number_format($e['salario'], 2) ?>
                            </p>
                            <a href="ver-detalles.php?id=<?= $e['id'] ?>" class="btn btn-outline-primary w-100">
                                Ver detalles
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach;
        endif; ?>

    </div>
</section>

<!-- ###publicar oficio### -->
<section class="bg-light py-5">
    <div class="container text-center">
        <h2 class="h3 mb-3">Sabes un Oficio</h2>
        <p class="text-muted mb-4">Publica tu oficio y llega a miles de personas</p>
        <a href="publicar-empleo.php" class="btn btn-primary btn-lg" ><i class="bi bi-megaphone"></i>Publica tu Oficio</a>
    </div>
</section>

<?php
    include 'includes/footer.php';
?>