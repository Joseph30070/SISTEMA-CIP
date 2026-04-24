<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['sale_id'])) {
    echo json_encode(['error' => 'Falta sale_id']);
    exit;
}

$sale_id = intval($_GET['sale_id']);

// Contar cuántos pagos existen para esta venta
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total 
    FROM pagos 
    WHERE sale_id = ? 
    AND deleted_at IS NULL
");
$stmt->execute([$sale_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$totalPagos = intval($row['total']);
$siguienteCuota = '';

switch ($totalPagos) {
    case 0:
        $siguienteCuota = 'CUOTA #1';
        break;
    case 1:
        $siguienteCuota = 'CUOTA #2';
        break;
    case 2:
        $siguienteCuota = 'CUOTA #3';
        break;
    case 3:
        $siguienteCuota = 'CUOTA #4';
        break;
    default:
        $siguienteCuota = 'PAGOS COMPLETOS';
        break;
}

echo json_encode(['cuota' => $siguienteCuota]);
