<?php
// Vista products/form.php
$isEditing = isset($products);
$pageTitle = $isEditing ? 'Editar Product' : 'Nuevo Product';
include __DIR__ . '/../layouts/header.php';
?>

<div class='card'>
  <h2><?php echo $pageTitle; ?></h2>
  <form method='post'>
    <!-- Formulario pendiente -->
  </form>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
