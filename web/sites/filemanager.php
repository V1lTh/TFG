<?php
// filemanager.php
require __DIR__.'/config.php';
require __DIR__.'/utils.php';
requireLogin();
$usuario   = $_SESSION['username'];
$dir       = userDir($usuario);
if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_FILES['archivos']['name'])) {
    $uploaded=[]; $errors=[];
    foreach ($_FILES['archivos']['name'] as $i => $orig) {
        $err = $_FILES['archivos']['error'][$i];
        if ($err===UPLOAD_ERR_OK) {
            $safe = sanitizeFilename($orig);
            $dest = "$dir/$safe";
            if (move_uploaded_file($_FILES['archivos']['tmp_name'][$i], $dest)) {
                $uploaded[] = $safe;
            } else {
                $errors[] = "$orig: fallo al mover";
            }
        } else {
            $errors[] = "$orig: error código $err";
        }
    }
    if ($uploaded) flash('Subidos: '.implode(', ',$uploaded),'success');
    if ($errors)   flash('Errores:<br>'.implode('<br>',$errors),'error');
    header('Location: filemanager.php');
    exit;
}
if (isset($_GET['eliminar'])) {
    $f = sanitizeFilename($_GET['eliminar']);
    $p = "$dir/$f";
    if (is_file($p)) unlink($p);
    header('Location: filemanager.php');
    exit;
}
$files = listUserFiles($dir);
$pageTitle = 'Gestor de Archivos';
require __DIR__.'/header.php';
?>
<div class="container">
  <h2>Gestor de <?= htmlspecialchars($usuario) ?></h2>
  <form method="post" enctype="multipart/form-data">
    <input type="file" name="archivos[]" multiple required>
    <button type="submit">Subir</button>
  </form>
  <table>
    <tr><th>Nombre</th><th>Tamaño</th><th>Acciones</th></tr>
    <?php foreach($files as $f): ?>
    <tr>
      <td><?= htmlspecialchars($f) ?></td>
      <td><?= round(filesize("$dir/$f")/1024,2) ?> KB</td>
      <td>
        <a href="<?= "$dir/$f" ?>" download>📥</a>
        <a href="?eliminar=<?= urlencode($f) ?>" onclick="return confirm('Eliminar?')">🗑</a>
      </td>
    </tr>
    <?php endforeach; if(empty($files)): ?>
    <tr><td colspan="3" style="text-align:center;">Sin archivos</td></tr>
    <?php endif; ?>
  </table>
</div>
<?php require __DIR__.'/footer.php'; ?>