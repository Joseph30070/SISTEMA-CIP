<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR', 'ASESOR']);

// Redirigir la petición con los mismos parámetros
$sale_id = isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0;
header("Location: ../process/process_get_next_cuota.php?sale_id={$sale_id}");
exit;