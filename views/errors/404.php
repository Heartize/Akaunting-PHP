<?php
// Vista errors/404.php
$pageTitle = 'Página no encontrada';
include __DIR__ . '/../layouts/header.php';
?>

<div class='card'>
  <h2>Error 404</h2>
  <p>La página que estás buscando no existe.</p>
  <a href='<?php echo url('/'); ?>' class='btn btn-primary'>Volver al inicio</a>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
