<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: auth.php");
    exit;
}
$usuario = $_SESSION['username'];
$directorio = "uploads/$usuario";

if (!is_dir($directorio)) {
    mkdir($directorio, 0775, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["archivo"])) {
    $nombreArchivo = basename($_FILES["archivo"]["name"]);
    $destino = "$directorio/$nombreArchivo";
    move_uploaded_file($_FILES["archivo"]["tmp_name"], $destino);
}

if (isset($_GET["eliminar"])) {
    $archivo = basename($_GET["eliminar"]);
    $ruta = "$directorio/$archivo";
    if (file_exists($ruta)) unlink($ruta);
}

$archivos = scandir($directorio);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor de Archivos</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 2em; }
        .container { max-width: 700px; margin: auto; background: white; padding: 1em; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        th, td { border-bottom: 1px solid #ddd; padding: 0.5em; text-align: left; }
        th { background: #eee; }
        .actions a { margin-right: 10px; color: #007BFF; text-decoration: none; }
        .actions a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gestor de Archivos - <?= htmlspecialchars($usuario) ?></h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="archivo" required>
            <button type="submit">Subir</button>
        </form>
        <table>
            <tr><th>Nombre</th><th>Tamaño</th><th>Acciones</th></tr>
            <?php foreach ($archivos as $archivo):
                if ($archivo == "." || $archivo == "..") continue;
                $ruta = "$directorio/$archivo";
                ?>
                <tr>
                    <td><?= htmlspecialchars($archivo) ?></td>
                    <td><?= round(filesize($ruta) / 1024, 2) ?> KB</td>
                    <td class="actions">
                        <a href="<?= $ruta ?>" download>📥 Descargar</a>
                        <a href="?eliminar=<?= urlencode($archivo) ?>" onclick="return confirm('¿Eliminar <?= htmlspecialchars($archivo) ?>?')">🗑 Eliminar</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
    </div>
</body>
</html>