<?php
// menu.php
require __DIR__.'/config.php';
require __DIR__.'/utils.php';
requireLogin();
$pageTitle = 'Menú Principal';
require __DIR__.'/header.php';
?>
<div class="info">
  <h1>Host: <?= gethostname() ?></h1>
  <p id="fecha">--</p>
  <p id="hora">--:--:--</p>
</div>
<div class="container">
  <a href="filemanager.php" class="section top-left">File Manager</a>
  <a href="status.php"     class="section top-right">Estado</a>
  <a href="#"               class="section bottom-left">Ajustes</a>
  <a href="auth.php?logout" class="section bottom-right">Salir</a>
</div>
<script>
function updateClock() {
  const now = new Date();
  document.getElementById("hora").textContent = now.toLocaleTimeString();
  document.getElementById("fecha").textContent = now.toLocaleDateString(undefined, {
    weekday:'long',year:'numeric',month:'long',day:'numeric'});
}
setInterval(updateClock,1000);
updateClock();
</script>
<?php require __DIR__.'/footer.php'; ?>