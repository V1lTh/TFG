<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Mi Aplicación') ?></title>
  <link rel="stylesheet" href="/global.css">
</head>
<body>
<?php if ($f = getFlash()): ?>
  <div class="msg <?= $f['type'] ?>"><?= htmlspecialchars($f['msg']) ?></div>
<?php endif; ?>