<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Main Menu</title>
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #1f4037, #99f2c8);
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: white;
    }
    .info {
      text-align: center;
      margin-bottom: 20px;
      text-shadow: 1px 1px 2px #000;
    }
    .info h1 {
      font-size: 1.5em;
      margin: 0;
    }
    .info p {
      margin: 5px 0;
    }
    .container {
      width: 320px;
      height: 320px;
      display: flex;
      flex-wrap: wrap;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
      border-radius: 15px;
      overflow: hidden;
      background: #fff;
    }
    .section {
      width: 50%;
      height: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: bold;
      text-decoration: none;
      color: white;
      transition: transform 0.3s, filter 0.3s;
    }
    .section:hover {
      transform: scale(1.05);
      filter: brightness(1.1);
    }
    .top-left { background: #3498db; }
    .top-right { background: #e67e22; }
    .bottom-left { background: #9b59b6; }
    .bottom-right { background: #2ecc71; }
  </style>
</head>
<body>
  <div class="info">
    <h1>Host: <?= gethostname(); ?></h1>
    <p id="fecha">--</p>
    <p id="hora">--:--:--</p>
  </div>

  <div class="container">
    <a href="./filemanager.php" class="section top-left">File Manager</a>
    <a href="./status.php" class="section top-right">Estado</a>
    <a href="#" class="section bottom-left">Ajustes</a>
    <a href="../index.php" class="section bottom-right">Salir</a>
  </div>

  <script>
    function updateClock() {
      const now = new Date();
      document.getElementById("hora").textContent = now.toLocaleTimeString();
      document.getElementById("fecha").textContent = now.toLocaleDateString(undefined, {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
      });
    }
    setInterval(updateClock, 1000);
    updateClock();
  </script>
</body>
</html>