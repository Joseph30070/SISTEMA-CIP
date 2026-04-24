<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR']); // Solo administradores pueden eliminar

require __DIR__ . '/../config/db.php';

// Verificar que venga el ID por POST
if (!isset($_POST['id'])) {
    header("Location: ../public/editar_registros.php?tab=ventas");
    exit;
}

$id = (int) $_POST['id'];

// Soft delete (marcar fecha)
$sql = "UPDATE ventas SET deleted_at = NOW() WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

header("Location: ../public/editar_registros.php?tab=ventas&msg=Venta+eliminada+correctamente");
exit;
