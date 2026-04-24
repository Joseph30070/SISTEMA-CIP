<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR']); // Solo administradores pueden eliminar

require __DIR__ . '/../config/db.php';

// Verificar que venga el ID por POST
if (!isset($_POST['id'])) {
    header("Location: ../public/editar_registros.php?tab=pagos");
    exit;
}

$id = (int) $_POST['id'];

// Soft delete
$sql = "UPDATE pagos SET deleted_at = NOW() WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

header("Location: ../public/editar_registros.php?tab=pagos&msg=Pago+eliminado+correctamente");
exit;
