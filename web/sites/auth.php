<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

$conn = connectDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (isset($_POST['register'])) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO usuarios (username,password) VALUES (?,?)");
        $stmt->bind_param("ss", $username, $hash);
        if ($stmt->execute()) {
            $_SESSION['user_id']  = $stmt->insert_id;
            $_SESSION['username'] = $username;
            header('Location: /upload/');
            exit;
        }
        $error = 'Usuario ya existe';
        $stmt->close();
    }

    if (isset($_POST['login'])) {
        $stmt = $conn->prepare("SELECT id,password FROM usuarios WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($id, $hash);
        $stmt->fetch();
        $stmt->close();

        if ($hash && password_verify($password, $hash)) {
            $_SESSION['user_id']  = $id;
            $_SESSION['username'] = $username;
            header('Location: /upload/');
            exit;
        }
        $error = 'Datos incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login / Registro</title>
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      /* Antes: --bg: #0b0f14; --fg: #e0e0e0; --primary: #00d1b2; --primary-dark: #00b89c; */
      --bg: #001f3f;          /* azul oscuro de base */
      --fg: #e0e0e0;         /* texto claro */
      --fg-muted: #a0b0c0;   /* gris azulado */
      --primary: #0074D9;    /* azul intenso */
      --primary-dark: #005fa3;/* azul más oscuro */
      --trans: .3s;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    html, body {
      height:100%; font-family:'Inter',sans-serif;
      color:var(--fg); overflow:hidden;
      background:var(--bg);
    }
    a { color:inherit; text-decoration:none; }
    /* Fondo animado */
    .bg-animated {
      position:fixed; top:0; left:0;
      width:100vw; height:100vh; z-index:-2;
      background:linear-gradient(-45deg,
        var(--bg),
        #002b5a,  /* tono medio azulado */
        var(--bg)
      );
      background-size:400% 400%; animation:gradient 15s ease infinite;
    }
    @keyframes gradient {
      0%{background-position:0% 50%}
      50%{background-position:100% 50%}
      100%{background-position:0% 50%}
    }
    /* Partículas */
    .particles {
      position:fixed; top:0; left:0;
      width:100%; height:100%; z-index:-1; overflow:hidden;
    }
    .particle {
      position:absolute;
      background:rgba(0,116,217,0.3); /* cyan-azulado translúcido */
      border-radius:50%;
      animation:float linear infinite;
    }
    @keyframes float { to{transform:translateY(-100vh)} }
    /* Formulario */
    .form-container {
      position:relative; z-index:1;
      width:320px; margin:auto;
      top:50%; transform:translateY(-50%);
      background:rgba(0,31,63,0.85); /* contenedor azul oscuro translúcido */
      padding:2rem; border-radius:10px;
      box-shadow:0 0 20px rgba(0,0,0,0.5);
    }
    .form-container h2 {
      text-align:center;
      margin-bottom:1rem;
      color:var(--primary);
    }
    .form-container input {
      width:100%; padding:0.8rem;
      margin:0.5rem 0;
      border:1px solid var(--fg-muted);
      border-radius:5px;
      background:rgba(11,15,20,0.2);
      color:var(--fg);
    }
    .form-container input::placeholder { color:var(--fg-muted); }
    .form-container button {
      width:100%; padding:0.8rem;
      margin-top:0.5rem;
      background:var(--primary); color:var(--bg);
      border:none; border-radius:5px;
      font-weight:600; cursor:pointer;
      transition:var(--trans);
    }
    .form-container button:hover { background:var(--primary-dark); }
    .form-container .btn-home {
      display:block; text-align:center;
      margin:1rem 0 0; padding:0.6rem;
      background:var(--fg-muted); color:var(--bg);
      border-radius:5px; text-decoration:none;
      transition:var(--trans);
    }
    .form-container .btn-home:hover { background:var(--fg); }
    .msg {
      text-align:center; margin-top:1rem;
      color:#ff6666;
    }
  </style>
</head>
<body>
  <div class="bg-animated" aria-hidden="true"></div>
  <div class="particles" id="particles" aria-hidden="true"></div>

  <div class="form-container">
    <h2>Login / Registro</h2>
    <?php if ($error): ?>
      <div class="msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="text" name="username" placeholder="Usuario" required>
      <input type="password" name="password" placeholder="Contraseña" required>
      <button type="submit" name="login">Iniciar Sesión</button>
      <button type="submit" name="register">Registrarse</button>
    </form>
    <a href="/" class="btn-home">HOME</a>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const container = document.getElementById('particles');
      const frag = document.createDocumentFragment();
      const count = Math.floor(window.innerWidth / 8);
      for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = Math.random() * 8 + 4;
        p.style.width = `${size}px`;
        p.style.height = `${size}px`;
        p.style.left = `${Math.random() * 100}vw`;
        p.style.bottom = '-10px';
        p.style.animationDuration = `${Math.random() * 20 + 10}s`;
        p.style.animationDelay    = `${Math.random() * 20}s`;
        p.style.opacity           = Math.random() * 0.5 + 0.1;
        frag.appendChild(p);
      }
      container.appendChild(frag);
    });
  </script>
</body>
</html>