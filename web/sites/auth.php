<?php
$mensaje = "";
$host = "localhost";
$user = "adminweb";
$pass = "wwZ@Mkho[CW7qH[y";
$db = "miaweb";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Error de conexión");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    if (isset($_POST["register"])) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO usuarios (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hash);
        $mensaje = $stmt->execute() ? "Registro exitoso" : "Usuario ya existe";
        $stmt->close();
    }

    if (isset($_POST["login"])) {
        $stmt = $conn->prepare("SELECT password FROM usuarios WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($hash);
        $stmt->fetch();
        $stmt->close();
        $mensaje = ($hash && password_verify($password, $hash)) ? "Inicio de sesión exitoso" : "Datos incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login/Registro</title>
    <link rel="stylesheet" href="../style/global.css">
    <style>
        body {
            font-family: sans-serif;
            background: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .box {
            background: white;
            padding: 2em;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            width: 300px;
        }
        h2 {
            text-align: center;
        }
        input, button {
            width: 100%;
            margin: 0.5em 0;
            padding: 0.7em;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
        }
        .msg {
            text-align: center;
            margin-top: 1em;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Login / Registro</h2>
        <form method="post">
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit" name="login">Iniciar Sesión</button>
            <button type="submit" name="register">Registrarse</button>
        </form>
        <?php
        if ($hash && password_verify($password, $hash)) {
            header("Location: menu.php");
            exit;
        } else {
            $mensaje = "Datos incorrectos";
        }
        ?>
    </div>
</body>
</html>