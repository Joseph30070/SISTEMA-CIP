<?php
require_once __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';
checkRole(['ADMINISTRADOR']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header("Location: ../public/registrar_usuario.php?tab=gestionar&error=Solicitud+inválida");
    exit;
}

$id = (int) $_POST['id'];

// Evitar eliminarse a sí mismo
if ($_SESSION['user_id'] == $id) {
    header("Location: ../public/registrar_usuario.php?tab=gestionar&error=No+puedes+eliminar+tu+propia+cuenta");
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../public/registrar_usuario.php?tab=gestionar&msg=Usuario+eliminado+correctamente");
    exit;
} catch (PDOException $e) {
    header("Location: ../public/registrar_usuario.php?tab=gestionar&error=Error+al+eliminar:+".$e->getMessage());
    exit;
}
