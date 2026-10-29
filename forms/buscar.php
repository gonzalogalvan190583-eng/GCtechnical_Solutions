<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$busqueda = $_GET['q'] ?? '';
$busqueda = trim($busqueda);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Búsqueda</title>
</head>
<body>

<h2>Resultados de búsqueda</h2>

<?php if ($busqueda === ''): ?>
  <p>No ingresaste ningún término.</p>
<?php else: ?>

  <p>Buscando: <strong><?php echo htmlspecialchars($busqueda); ?></strong></p>

  <?php
  $servicios = [
    "Diseño Web",
    "Desarrollo Web",
    "Automatización de Procesos",
    "Asesorías Contables"
  ];

  $resultados = [];

  foreach ($servicios as $servicio) {
    if (stripos($servicio, $busqueda) !== false) {
      $resultados[] = $servicio;
    }
  }

  if (!empty($resultados)) {
    echo "<ul>";
    foreach ($resultados as $r) {
      echo "<li>$r</li>";
    }
    echo "</ul>";
  } else {
    echo "<p>No se encontraron resultados.</p>";
  }
  ?>

<?php endif; ?>

</body>
</html>
