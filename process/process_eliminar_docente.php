<?php

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

// Solo ADMIN puede eliminar
if (function_exists('checkRole')) {
    checkRole(['ADMINISTRADOR']);
} else {
    $role = strtoupper($_SESSION['role'] ?? '');
    if ($role !== 'ADMINISTRADOR') {
        http_response_code(403);
        exit('No autorizado');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Solicitud inválida');
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Verificar dependencias: ventas asociadas
$check = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE teacher_id = ? AND deleted_at IS NULL");
$check->execute([$id]);
$tieneVentas = (int)$check->fetchColumn() > 0;

if ($tieneVentas) {
    $_SESSION['flash_message'] = 'No se puede eliminar: el docente tiene ventas registradas.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ../public/home.php');
    exit;
}

// Soft delete: marcar como eliminado
$stmt = $pdo->prepare("UPDATE docentes SET deleted_at = NOW() WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['flash_message'] = 'Docente eliminado correctamente.';
$_SESSION['flash_type'] = 'success';

header('Location: ../public/home.php');
exit;
