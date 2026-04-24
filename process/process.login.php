<?php
require __DIR__ . '/../config/db.php';
session_start();

// Obtener datos del formulario
$correo   = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

// Buscar usuario en la base de datos
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$correo]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar usuario y contraseña (usando password_verify con hash)
if ($user && password_verify($password, $user['password'])) {
    // Guardar datos en la sesión
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['role']     = $user['role']; // <-- Guarda el rol aquí

    // Redirigir según rol
    if ($user['role'] === 'ADMINISTRADOR') {
        header("Location: ../public/home.php");
    } elseif ($user['role'] === 'ADMISION') {
        header("Location: ../public/home.php");
    } elseif ($user['role'] === 'ASESOR') {
        header("Location: ../public/home.php");
    } else {
        header("Location: ../public/home.php");
    }
    exit;
} else {
    // Login incorrecto
    header("Location: ../public/login.php?error=Correo o contraseña incorrectos");
    exit;
}
