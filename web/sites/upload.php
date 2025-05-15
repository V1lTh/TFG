<?php
$uploadDir = '/mnt/NAS_VAULT/uploads/';
$message = '';

// Validar directorio
if (!is_dir($uploadDir)) {
    die("El directorio de subida no existe: " . $uploadDir);
}
if (!is_writable($uploadDir)) {
    die("El directorio de subida no es escribible: " . $uploadDir . ". Verifica los permisos.");
}

// Manejar subida POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['archivo_subido']) && $_FILES['archivo_subido']['error'] === UPLOAD_ERR_OK) {
        $originalFileName = basename($_FILES['archivo_subido']['name']);
        $safeFileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $originalFileName);

        if (empty($safeFileName) || $safeFileName !== $originalFileName) {
            $message = "<div style='color:red;'>Nombre de archivo inválido. Subida cancelada.</div>";
        } else {
            $uploadFile = $uploadDir . $safeFileName;

            if (move_uploaded_file($_FILES['archivo_subido']['tmp_name'], $uploadFile)) {
                $message = "<div style='color:green;'>Archivo <strong>" . htmlspecialchars($safeFileName) . "</strong> subido correctamente.</div>";
            } else {
                $message = "<div style='color:red;'>Error al mover el archivo. Revisa los permisos del directorio.</div>";
            }
        }
    } else {
        $message = "<div style='color:red;'>Error en la subida: " . $_FILES['archivo_subido']['error'] . "</div>";
    }
}

// Mostrar formulario + lista
?>
<!DOCTYPE html>
<html>
<head>
    <title>Subida de Archivos</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        form { margin-bottom: 30px; }
        table { border-collapse: collapse; width: 100%; max-width: 900px; margin: auto; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #eee; }
        h2, .message { text-align: center; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h2>Subir Archivo</h2>

<?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

<form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
    <p style="text-align: center;">
        Selecciona archivo: <input type="file" name="archivo_subido" required>
        <button type="submit">Subir</button>
    </p>
</form>

<h2>Archivos Subidos</h2>
<h2>Archivos Subidos</h2>
<table>
    <tr>
        <th>Nombre del Archivo</th>
        <th>Tamaño</th>
        <th>Fecha de Modificación</th>
        <th>Acciones</th>
    </tr>
<?php
$files = scandir($uploadDir);
$fileCount = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $filePath = $uploadDir . $file;
    if (is_file($filePath)) {
        $fileSize = filesize($filePath);
        $fileSizeFormatted = number_format($fileSize / 1024, 2) . ' KB';
        if ($fileSize > 1024 * 1024) {
            $fileSizeFormatted = number_format($fileSize / (1024 * 1024), 2) . ' MB';
        }
        $fileModTime = date("Y-m-d H:i:s", filemtime($filePath));
        $fileUrl = '/uploads/' . rawurlencode($file);

        echo "<tr>
                <td><a href=\"" . htmlspecialchars($fileUrl) . "\" target=\"_blank\">" . htmlspecialchars($file) . "</a></td>
                <td>$fileSizeFormatted</td>
                <td>$fileModTime</td>
                <td>
                    <a href=\"" . htmlspecialchars($fileUrl) . "\" download class=\"download-link\">Descargar</a>
                </td>
              </tr>";
        $fileCount++;
    }
}

if ($fileCount === 0) {
    echo "<tr><td colspan='4' style='text-align:center;'>No hay archivos subidos aún.</td></tr>";
}
?>
</table>

</body>
</html>
