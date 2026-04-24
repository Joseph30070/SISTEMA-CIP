<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR', 'ADMISION']);
require __DIR__ . '/../config/db.php';

// Validar ID
if (!isset($_POST['id'])) {
    header("Location: ../public/consulta.php?filtro=ventas");
    exit;
}

$id = (int) $_POST['id'];

// Único campo editable
$proceso_certificacion = $_POST['proceso_certificacion'] ?? 'No tiene';

// Capturar parámetros para regresar correctamente
$vista    = isset($_POST['vista']) ? trim($_POST['vista']) : 'resumido';
$filtro   = isset($_POST['filtro']) ? trim($_POST['filtro']) : 'ventas';
$busqueda = isset($_POST['busqueda']) ? trim($_POST['busqueda']) : '';

// Actualizar SOLO el proceso de certificación
$sql = "UPDATE ventas 
        SET proceso_certificacion = ?
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$success = $stmt->execute([$proceso_certificacion, $id]);

// Redirigir con parámetros limpios
$redirect = "../public/consulta.php?filtro=" . urlencode($filtro) .
            "&busqueda=" . urlencode($busqueda) .
            "&vista=" . urlencode($vista);

if ($success) {
    $redirect .= "&msg=" . urlencode("Venta actualizada correctamente");
} else {
    $redirect .= "&error=" . urlencode("No se pudo actualizar la venta");
}

header("Location: $redirect");
exit;
