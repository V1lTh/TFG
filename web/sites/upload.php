<?php
session_start();
// Verificar inicio de sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}
$userId = $_SESSION['user_id'];

// Define base directory and ensure it exists
$baseDir    = '/mnt/NAS_VAULT/uploads/' . $userId;

// Ensure the user's directory exists
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0755, true)) {
        // Handle error if directory creation fails
        die("Error creating base directory: " . $baseDir);
    }
}


$relPath    = $_GET['dir'] ?? '';

// Sanitize relative path to prevent directory traversal
$relPath = str_replace(['../', '..\\'], '', $relPath);
$relPath = trim($relPath, '/\\');

$currentDir = realpath($baseDir . ($relPath !== '' ? '/' . $relPath : ''));

// Check if the calculated directory is within the base directory
if ($currentDir === false || strpos($currentDir, $baseDir) !== 0) {
    // If not within the base directory or path is invalid, reset to base
    $currentDir = $baseDir;
    $relPath = ''; // Reset relative path
}


$message = '';

// Create folder
if (!empty($_POST['new_folder'])) {
    $name = preg_replace('/[^a-zA-Z0-9_\- ]+/', '', $_POST['new_folder']);
    if ($name) {
        $newFolderPath = $currentDir . '/' . $name;
        // Basic check to prevent creating in root or outside designated area
        if (strpos(realpath($newFolderPath), realpath($baseDir)) === 0) {
            if (!mkdir($newFolderPath, 0755)) {
                 $message = "<div class='message error'>Error creando carpeta '$name'.</div>";
            } else {
                $message = "<div class='message success'>Carpeta '$name' creada.</div>";
            }
        } else {
             $message = "<div class='message error'>Operación no permitida.</div>";
        }
    }
}

// Subir múltiples archivos
if (isset($_FILES['archivos'])) {
    $uploaded = [];
    foreach ($_FILES['archivos']['name'] as $i => $orig) {
        if ($_FILES['archivos']['error'][$i] === UPLOAD_ERR_OK) {
            // Sanitize filename
            $safe = preg_replace('/[^a-zA-Z0-9_\.\- ]+/', '', basename($orig));
            if ($safe === '') continue; // Skip empty filenames

            $tmp  = $_FILES['archivos']['tmp_name'][$i];
            $destinationPath = $currentDir . '/' . $safe;

            // Basic check to prevent uploading outside designated area
             if (strpos(realpath(dirname($destinationPath)), realpath($baseDir)) === 0) {
                if (move_uploaded_file($tmp, $destinationPath)) {
                    $uploaded[] = $safe;
                }
            }
        }
    }
    if ($uploaded) {
        $message = "<div class='message success'>Subidos: " . htmlspecialchars(implode(', ', $uploaded)) . "</div>";
    } elseif (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name']) && empty($uploaded)) {
         $message = "<div class='message error'>Error al subir archivos.</div>";
    }
}

// Eliminar elemento
if (isset($_GET['delete'])) {
    $item = basename($_GET['delete']);
    $pathItem = $currentDir . '/' . $item;

    // IMPORTANT: Sanitize and validate path to prevent directory traversal or deleting outside the user's directory
    $realPathItem = realpath($pathItem);
    $realBaseDir = realpath($baseDir);

    if ($realPathItem !== false && strpos($realPathItem, $realBaseDir) === 0 && $realPathItem !== $realBaseDir) { // Prevent deleting the base directory itself
        if (is_dir($realPathItem)) {
            // Function to delete a directory recursively
            $deleteDirRecursive = function($dir) use (&$deleteDirRecursive) {
                $files = array_diff(scandir($dir), ['.', '..']);
                foreach ($files as $file) {
                    (is_dir("$dir/$file")) ? $deleteDirRecursive("$dir/$file") : unlink("$dir/$file");
                }
                return rmdir($dir);
            };
            if ($deleteDirRecursive($realPathItem)) {
                 $message = "<div class='message success'>Elemento '$item' eliminado.</div>";
            } else {
                 $message = "<div class='message error'>Error al eliminar directorio '$item'. Asegúrese de que esté vacío.</div>";
            }
        } else {
            if (unlink($realPathItem)) {
                 $message = "<div class='message success'>Elemento '$item' eliminado.</div>";
            } else {
                 $message = "<div class='message error'>Error al eliminar archivo '$item'.</div>";
            }
        }
    } else {
         $message = "<div class='message error'>Operación de eliminación no permitida.</div>";
    }
    // Redirect to prevent resubmission on refresh
     header("Location: ?dir=" . urlencode($relPath) . "&msg=" . urlencode(strip_tags($message)));
     exit;
}

// Handle message from redirect
if (isset($_GET['msg'])) {
    $message = "<div class='message success'>" . htmlspecialchars($_GET['msg']) . "</div>";
     // You might want to clear the msg parameter from the URL here with JavaScript or another redirect
}


// Renombrar elemento
if (!empty($_POST['rename_old']) && !empty($_POST['rename_new'])) {
    $old = basename($_POST['rename_old']);
    $new = preg_replace('/[^a-zA-Z0-9_\- ]+/', '', $_POST['rename_new']);

    $oldPath = $currentDir . '/' . $old;
    $newPath = $currentDir . '/' . $new;

    // Sanitize and validate paths
     $realOldPath = realpath($oldPath);
     $realNewDir = realpath(dirname($newPath)); // Get the real path of the target directory
     $realBaseDir = realpath($baseDir);


    if ($new && $realOldPath !== false && strpos($realOldPath, $realBaseDir) === 0 && $realNewDir !== false && strpos($realNewDir, $realBaseDir) === 0) {
        // Ensure the new name doesn't point outside or conflict with existing important files/dirs
        if (rename($realOldPath, $newPath)) {
             $message = "<div class='message success'>Renombrado '" . htmlspecialchars($old) . "' a '" . htmlspecialchars($new) . "'.</div>";
        } else {
             $message = "<div class='message error'>Error al renombrar '" . htmlspecialchars($old) . "' a '" . htmlspecialchars($new) . "'.</div>";
        }
    } else {
         $message = "<div class='message error'>Operación de renombre no permitida o nombre inválido.</div>";
    }
}

// Listar contenido
$items = [];
if (is_dir($currentDir)) {
    $scanResults = scandir($currentDir);
    if ($scanResults !== false) {
         $items = array_diff($scanResults, ['.', '..']);
         // Optional: Sort directories first, then files
         usort($items, function($a, $b) use ($currentDir) {
             $pathA = $currentDir . '/' . $a;
             $pathB = $currentDir . '/' . $b;
             if (is_dir($pathA) && !is_dir($pathB)) return -1;
             if (!is_dir($pathA) && is_dir($pathB)) return 1;
             return strcasecmp($a, $b); // Case-insensitive alphabetical sort
         });
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestor de Archivos</title>
<p style="text-align:center;color:var(--fg-muted);">Bienvenido <?= htmlspecialchars($_SESSION['username']) ?></p>
  <style>
    :root {
      --bg-start: #0f1a2a; /* Dark blue-gray start */
      --bg-end: #1a2634; /* Slightly darker blue-gray end */
      --fg: #e0e0e0; /* Light gray */
      --fg-muted: #a0a0a0; /* Muted gray */
      --primary: #00d1b2; /* Teal */
      --primary-dark: #00b89c; /* Darker teal */
      --error: #e53e3e; /* Red for errors */
      --border-color: #334155; /* Subtle border */
      --container-bg: rgba(26, 38, 52, 0.9); /* Semi-transparent container */
      --input-bg: #1a2634; /* Input background */
      --trans: 0.3s ease;
    }

    /* Wavy lines pattern (SVG data URI) */
    /* Generated using a simple sine wave pattern, repeated */
    /* Credits: Based on patterns that can be generated or found online */
    @keyframes wave {
      0% { background-position: 0 0; }
      100% { background-position: 100px 0; } /* Adjust to match wave width */
    }

    body {
      font-family: 'Inter', sans-serif; /* Assuming Inter font is available or fallback */
      background: var(--bg-start);
      background: linear-gradient(to bottom right, var(--bg-start), var(--bg-end));
      color: var(--fg);
      min-height: 100vh;
      line-height: 1.6;
      position: relative; /* Needed for pseudo-element */
      overflow-x: hidden; /* Prevent horizontal scroll from wave effect */
    }

     body::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1; /* Place behind content */
        opacity: 0.1; /* Adjust opacity */
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='20' viewBox='0 0 100 20'%3E%3Cpath fill='none' stroke='%23ffffff' stroke-width='0.5' d='M0 10 Q 25 0 50 10 T 100 10'/%3E%3C/svg%3E");
        background-repeat: repeat;
        background-size: 100px 20px; /* Match SVG dimensions */
        animation: wave 10s linear infinite; /* Add subtle animation */
     }

    *{margin:0;padding:0;box-sizing:border-box;}

    .container {
      max-width: 900px;
      margin: 2rem auto;
      padding: 2rem; /* Increased padding */
      background: var(--container-bg);
      border-radius: 8px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3); /* Added shadow */
      position: relative; /* Ensure container is above wave effect */
      z-index: 1;
    }

    h2 {
      text-align: center;
      margin-bottom: 1.5rem; /* Increased margin */
      color: var(--primary);
    }

    .message {
      padding: .75rem 1.5rem; /* Increased padding */
      margin-bottom: 1.5rem; /* Increased margin */
      border-radius: 4px;
      text-align: center;
      opacity: 0.9;
    }
    .message.success {
      background: rgba(0, 209, 178, 0.2); /* Teal background */
      color: var(--primary);
      border: 1px solid rgba(0, 209, 178, 0.3);
    }
     .message.error {
        background: rgba(229, 62, 62, 0.2); /* Red background */
        color: var(--error);
        border: 1px solid rgba(229, 62, 62, 0.3);
     }

    .breadcrumbs {
      margin-bottom: 1.5rem; /* Increased margin */
      font-size: 0.9rem;
      color: var(--fg-muted);
    }
    .breadcrumbs a {
      color: var(--fg-muted);
      text-decoration: none;
      margin-right: .5rem;
      transition: color var(--trans);
    }
    .breadcrumbs a:hover {
      color: var(--fg);
    }

    form, .folder-form {
      display: flex;
      gap: .75rem; /* Increased gap */
      margin-bottom: 1.5rem; /* Increased margin */
      align-items: center; /* Align items vertically */
      flex-wrap: wrap; /* Allow wrapping on small screens */
    }
     .folder-form input[type=text],
     form:not(.actions) input[type=file] {
         flex-grow: 1; /* Allow input to take available space */
     }


    input[type=text],
    input[type=file] {
      background: var(--input-bg);
      border: 1px solid var(--border-color);
      color: var(--fg);
      padding: .6rem 1rem; /* Increased padding */
      border-radius: 4px;
      transition: border-color var(--trans), box-shadow var(--trans);
      font-size: 1rem;
    }
    input[type=text]:focus,
    input[type=file]:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 5px rgba(0, 209, 178, 0.4); /* Add focus glow */
    }

    button {
      background: var(--primary);
      color: var(--bg-start); /* Use a dark color for text on button */
      border: none;
      padding: .7rem 1.2rem; /* Increased padding */
      border-radius: 4px;
      cursor: pointer;
      transition: background var(--trans), opacity var(--trans);
      font-size: 1rem;
      font-weight: bold;
      text-transform: uppercase;
    }
    button:hover {
      background: var(--primary-dark);
      opacity: 0.95;
    }
     button:active {
         opacity: 0.8;
     }


    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1.5rem; /* Increased margin */
      border: 1px solid var(--border-color);
      border-radius: 4px;
      overflow: hidden; /* Ensures rounded corners apply to borders */
    }

    th, td {
      padding: 1rem; /* Increased padding */
      border: 1px solid var(--border-color);
      text-align: left;
      word-break: break-word; /* Prevent long names from breaking layout */
    }

    th {
      background: #1e2a3a; /* Slightly different header background */
      color: var(--fg);
      font-weight: bold;
    }

    td {
        background: #1a2634; /* Row background */
    }

    /* Alternating row color */
     tbody tr:nth-child(even) td {
         background: #15202b;
     }

    table a {
        color: var(--primary);
        text-decoration: none;
        transition: color var(--trans);
    }
    table a:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    .actions {
        white-space: nowrap; /* Keep action links on one line */
    }

    .actions form {
        display: inline-flex; /* Use inline-flex for form actions */
        margin: 0;
        gap: .5rem; /* Gap between rename input and button */
        align-items: center;
    }

     .actions form input[type="text"] {
        padding: .3rem .5rem; /* Smaller padding for inline input */
        font-size: 0.9rem;
        max-width: 120px; /* Limit rename input width */
     }

     .actions form button {
        padding: .3rem .8rem; /* Smaller padding for inline button */
        font-size: 0.8rem;
     }


    p {
      text-align: center;
      margin-top: 1.5rem; /* Increased margin */
    }
    p a {
      color: var(--fg-muted);
      text-decoration: none;
      transition: color var(--trans);
    }
    p a:hover {
      color: var(--fg);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .container {
            padding: 1.5rem;
        }
        form, .folder-form {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }
         .actions form {
             flex-direction: row; /* Keep inline actions horizontal if possible */
             flex-wrap: wrap; /* Allow wrapping if needed */
         }
         .actions form input[type="text"] {
             flex-grow: 1;
             max-width: none; /* Remove max-width on smaller screens */
         }
         th, td {
             padding: 0.75rem;
         }
         table, thead, tbody, th, td, tr {
             display: block; /* Stack table elements */
         }
         thead tr {
             position: absolute;
             top: -9999px;
             left: -9999px;
         }
         tr { border: 1px solid var(--border-color); margin-bottom: .5rem; }
         td {
             border: none;
             border-bottom: 1px solid var(--border-color);
             position: relative;
             padding-left: 50%; /* Make space for pseudo-element label */
             text-align: right;
         }
         td:before {
             position: absolute;
             top: 0;
             left: 6px;
             width: 45%;
             padding-right: 10px;
             white-space: nowrap;
             content: attr(data-label); /* Use data-label attribute for headers */
             font-weight: bold;
             color: var(--fg-muted);
             text-align: left;
         }
          .actions td:before {
              content: 'Acciones';
          }
           .actions {
               text-align: right; /* Align actions to the right */
           }
            .actions form {
                justify-content: flex-end; /* Align inline form to the right */
            }
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Gestor de Archivos</h2>
    <?php echo $message; ?>
    <div class="breadcrumbs">
      <a href="upload.php">/</a>
      <?php
      $pathParts = explode('/', $relPath);
      $currentPath = '';
      foreach ($pathParts as $i => $p) {
          if ($p === '') continue; // Skip empty parts
          $currentPath .= ($currentPath === '' ? '' : '/') . $p;
          ?>
        &gt; <a href="?dir=<?=urlencode($currentPath)?>"><?=htmlspecialchars($p)?></a>
      <?php } ?>
    </div>
    <form class="folder-form" method="post">
      <input type="text" name="new_folder" placeholder="Nombre de la nueva carpeta" required>
      <button type="submit">Crear carpeta</button>
    </form>
    <form method="post" enctype="multipart/form-data">
      <input type="file" name="archivos[]" multiple required>
      <button type="submit">Subir archivos</button>
    </form>
    <table>
      <thead>
        <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Tamaño</th>
            <th>Última mod.</th>
            <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($items)): ?>
          <tr><td colspan="5" style="text-align:center;">Directorio vacío</td></tr>
      <?php else: ?>
          <?php foreach ($items as $item):
            $fullPathItem = $currentDir . '/' . $item;
            // Basic check to ensure item is within the base directory before displaying
            $realFullPathItem = realpath($fullPathItem);
            $realBaseDir = realpath($baseDir);

            if ($realFullPathItem === false || strpos($realFullPathItem, $realBaseDir) !== 0) {
                continue; // Skip items that are not within the user's directory (shouldn't happen with sanitization, but as a safeguard)
            }
          ?>
          <tr>
            <td data-label="Nombre"><?php if(is_dir($realFullPathItem)): ?>
                <a href="?dir=<?=urlencode($relPath?"$relPath/$item":"$item")?>"><?=htmlspecialchars($item)?></a>
              <?php else: ?><?=htmlspecialchars($item)?><?php endif; ?></td>
            <td data-label="Tipo"><?=is_dir($realFullPathItem)?'Carpeta':'Archivo'?></td>
            <td data-label="Tamaño"><?=is_file($realFullPathItem)?round(filesize($realFullPathItem)/1024,2).' KB':'-'?></td>
            <td data-label="Última mod."><?=date('Y-m-d H:i',filemtime($realFullPathItem))?></td>
            <td class="actions" data-label="Acciones">
              <?php if(is_file($realFullPathItem)): ?>
                  <?php
                  // Construct URL path relative to webroot for download
                  $downloadPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $realFullPathItem); // Adjust if your NAS mount point isn't directly under DOCUMENT_ROOT
                   // A more robust approach might involve a separate download script that validates the path
                  ?>
                  <a href="<?=$downloadPath?>" download="<?=htmlspecialchars($item)?>">Descargar</a> |
              <?php endif; ?>
              <a href="?dir=<?=urlencode($relPath)?>&delete=<?=urlencode($item)?>" onclick="return confirm('¿Está seguro de eliminar <?=addslashes(htmlspecialchars($item))?>?')">Eliminar</a> |
              <form method="post" style="display: inline-flex; gap: 5px;">
                  <input type="hidden" name="rename_old" value="<?=htmlspecialchars($item)?>">
                  <input type="text" name="rename_new" placeholder="Renombrar a" required>
                  <button type="submit">OK</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
    <p style="text-align:center;margin-top:1rem;"><a href="/">← HOME</a></p>
  </div>
</body>
</html>