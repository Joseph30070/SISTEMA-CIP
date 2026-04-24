<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

// Solo declarar la función si no existe
if (!function_exists('checkRole')) {
    function checkRole(array $roles)
    {
        if (!isset($_SESSION['role'])) {
            header("Location: ../public/login.php");
            exit;
        }

        if (!in_array($_SESSION['role'], $roles)) {
            echo "<div style='font-family: Arial; background:#fee; color:#b00020; padding:20px; border:1px solid #b00020; border-radius:8px; margin:40px; text-align:center'>
                <strong>Acceso denegado</strong><br> No tienes permiso para acceder a esta sección.
                <br><br><a href='../public/home.php'>Volver al inicio</a>
              </div>";
            exit;
        }
    }
}
