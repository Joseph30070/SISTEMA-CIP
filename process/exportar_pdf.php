<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR', 'ADMISION']);

use Dompdf\Dompdf;
use Dompdf\Options;


// ===============================
// CAPTURA DE PARÁMETROS
// ===============================
$filtro = $_GET['filtro'] ?? 'todo';
$busqueda = trim($_GET['busqueda'] ?? '');
$vista = $_GET['vista'] ?? 'resumido'; // resúmido por defecto

$paramsVentas = [];
$paramsCuotas = [];
$paramsResumen = [];

// ===============================
// CONSULTA DE VENTAS
// ===============================
$sqlVentas = "
SELECT 
    CONCAT(t.nombres, ' ', t.apellidos) AS docente,
    s.tipo_transaccion,
    c.nombre AS tipo_de_certificacion,
    pgr.nombre_programa AS programa,
    s.certificado,
    s.precio_programa,
    s.proceso_certificacion, 
    s.mencion,
    s.modalidad,
    s.inicio_programa,
    s.obs_programa,
    a.nombre_completo AS asesor,
    s.created_at AS fecha_registro_venta
FROM ventas s
INNER JOIN docentes t ON s.teacher_id = t.id
LEFT JOIN asesores a ON s.advisor_id = a.id
LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
LEFT JOIN programas pgr ON s.programa_id = pgr.id
WHERE 1=1
AND s.deleted_at IS NULL
AND t.deleted_at IS NULL
AND c.deleted_at IS NULL
AND pgr.deleted_at IS NULL
";

if ($busqueda !== '') {
    $sqlVentas .= " AND (
        LOWER(t.nombres) LIKE LOWER(:busqueda) OR
        LOWER(t.apellidos) LIKE LOWER(:busqueda) OR
        LOWER(CONCAT(t.nombres,' ',t.apellidos)) LIKE LOWER(:busqueda) OR
        LOWER(pgr.nombre_programa) LIKE LOWER(:busqueda) OR
        LOWER(c.nombre) LIKE LOWER(:busqueda) OR
        LOWER(a.nombre_completo) LIKE LOWER(:busqueda) OR
        LOWER(s.proceso_certificacion) LIKE LOWER(:busqueda) OR
        LOWER(s.mencion) LIKE LOWER(:busqueda) OR
        LOWER(s.modalidad) LIKE LOWER(:busqueda) OR
        LOWER(s.certificado) LIKE LOWER(:busqueda) OR
        LOWER(s.obs_programa) LIKE LOWER(:busqueda) OR
        CAST(s.precio_programa AS CHAR) LIKE :busqueda OR
        DATE_FORMAT(s.inicio_programa, '%Y-%m-%d') LIKE :busqueda OR
        DATE_FORMAT(s.created_at, '%Y-%m-%d %H:%i:%s') LIKE :busqueda
    )";

    $paramsVentas[':busqueda'] = "%$busqueda%";
}

$sqlVentas .= " ORDER BY s.created_at DESC";
$stmtVentas = $pdo->prepare($sqlVentas);
$stmtVentas->execute($paramsVentas);
$ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// CONSULTA DE CUOTAS / PAGOS
// ===============================
$sqlCuotas = "
SELECT 
    CONCAT(t.nombres, ' ', t.apellidos) AS docente,
    p.tipo_pago,
    p.monto_total AS monto_pagado,
    p.fecha_pago,
    p.codigo_operacion,
    p.titular_pago,
    p.voucher_path AS voucher,
    p.forma_pago,
    p.banco,
    p.created_at AS fecha_registro_pago
FROM pagos p
INNER JOIN ventas s ON p.sale_id = s.id
INNER JOIN docentes t ON s.teacher_id = t.id
WHERE 1=1
AND p.deleted_at IS NULL
AND s.deleted_at IS NULL
AND t.deleted_at IS NULL
";

if ($busqueda !== '') {
    $sqlCuotas .= " AND (
        LOWER(t.nombres) LIKE :busqueda_cuotas OR
        LOWER(t.apellidos) LIKE :busqueda_cuotas OR
        LOWER(CONCAT(t.nombres, ' ', t.apellidos)) LIKE :busqueda_cuotas OR
        LOWER(p.tipo_pago) LIKE :busqueda_cuotas OR
        LOWER(p.titular_pago) LIKE :busqueda_cuotas OR
        LOWER(p.codigo_operacion) LIKE :busqueda_cuotas OR
        LOWER(p.forma_pago) LIKE :busqueda_cuotas OR
        LOWER(p.banco) LIKE :busqueda_cuotas OR
        CAST(p.monto_total AS CHAR) LIKE :busqueda_cuotas OR
        DATE_FORMAT(p.fecha_pago, '%Y-%m-%d') LIKE :busqueda_cuotas OR
        DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') LIKE :busqueda_cuotas OR
        LOWER(p.voucher_path) LIKE :busqueda_cuotas
    )";

    $paramsCuotas[':busqueda_cuotas'] = "%$busqueda%";
}


$sqlCuotas .= " ORDER BY p.fecha_pago DESC";
$stmtCuotas = $pdo->prepare($sqlCuotas);
$stmtCuotas->execute($paramsCuotas);
$cuotas = $stmtCuotas->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// CONSULTA RESUMEN DE CUOTAS POR DOCENTE
// ===============================
$condiciones = [
    "p.tipo_pago != 'CONTADO'",
    "p.deleted_at IS NULL",
    "s.deleted_at IS NULL",
    "t.deleted_at IS NULL",
    "c.deleted_at IS NULL",
    "pgr.deleted_at IS NULL"
];

if (!empty($busqueda)) {
    $condiciones[] = "(
        LOWER(CONCAT(t.nombres, ' ', t.apellidos)) LIKE LOWER(:busqueda)
        OR LOWER(pgr.nombre_programa) LIKE LOWER(:busqueda)
        OR LOWER(c.nombre) LIKE LOWER(:busqueda) 
        OR LOWER(p.tipo_pago) LIKE LOWER(:busqueda)
    )";
    $paramsResumen[':busqueda'] = "%$busqueda%";
}

// Detectar cuota específica
$columnaCuotaBuscada = null;
if (!empty($busqueda)) {
    $busq = strtolower($busqueda);
    if (strpos($busq, 'cuota #1') !== false || strpos($busq, 'cuota 1') !== false) $columnaCuotaBuscada = 'cuota1';
    elseif (strpos($busq, 'cuota #2') !== false || strpos($busq, 'cuota 2') !== false) $columnaCuotaBuscada = 'cuota2';
    elseif (strpos($busq, 'cuota #3') !== false || strpos($busq, 'cuota 3') !== false) $columnaCuotaBuscada = 'cuota3';
    elseif (strpos($busq, 'cuota #4') !== false || strpos($busq, 'cuota 4') !== false) $columnaCuotaBuscada = 'cuota4';
}

// Columnas dinámicas
$columnasResumen = "
      CONCAT(t.nombres, ' ', t.apellidos) AS docente, 
      c.nombre AS tipo_de_certificacion,
      pgr.nombre_programa AS programa
      ";

if ($columnaCuotaBuscada !== null) {
    $columnasResumen .= ", 
    SUM(CASE WHEN p.tipo_pago LIKE '%".substr($columnaCuotaBuscada,-1)."%' THEN p.monto_total ELSE 0 END) AS $columnaCuotaBuscada";
} else {
    $columnasResumen .= ",
        SUM(CASE WHEN p.tipo_pago LIKE '%1%' THEN p.monto_total ELSE 0 END) AS cuota1,
        SUM(CASE WHEN p.tipo_pago LIKE '%2%' THEN p.monto_total ELSE 0 END) AS cuota2,
        SUM(CASE WHEN p.tipo_pago LIKE '%3%' THEN p.monto_total ELSE 0 END) AS cuota3,
        SUM(CASE WHEN p.tipo_pago LIKE '%4%' THEN p.monto_total ELSE 0 END) AS cuota4,
        COALESCE(s.precio_programa, 0) AS total_a_pagar,
        SUM(p.monto_total) AS total_pagado,
        COALESCE(s.precio_programa, 0) - SUM(p.monto_total) AS resta
    ";
}

$sqlResumen = "
    SELECT $columnasResumen
    FROM pagos p
    INNER JOIN ventas s ON p.sale_id = s.id
    INNER JOIN docentes t ON s.teacher_id = t.id
    LEFT JOIN programas pgr ON s.programa_id = pgr.id
    LEFT JOIN tipo_certificacion c ON s.curso_id = c.id
    WHERE ".implode(' AND ', $condiciones)."
    GROUP BY s.id, t.id, t.nombres, t.apellidos, pgr.nombre_programa, s.precio_programa,c.nombre,s.precio_programa
    ORDER BY t.apellidos ASC, pgr.nombre_programa ASC
";

$stmtResumen = $pdo->prepare($sqlResumen);
$stmtResumen->execute($paramsResumen);
$resumen = $stmtResumen->fetchAll(PDO::FETCH_ASSOC);
/* ============================================================
   GENERAR PDF
============================================================ */
require_once __DIR__ . '/../vendor/autoload.php';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$html = '<h2 style="text-align:center; font-family:Arial;">Reporte de ' . strtoupper($filtro) . '</h2>';

/* ------------------------------------------------------------
   MOSTRAR TABLAS SEGÚN FILTRO
------------------------------------------------------------ */

$html = '<h2 style="text-align:center; font-family:Arial;">Reporte de ' . strtoupper($filtro) . '</h2>';

if ($filtro === 'ventas') {

    if (!empty($ventas)) {
        $html .= '<h3 style="font-family:Arial;">Resultados de Ventas</h3>';
        $html .= generarTablaPDF($ventas);
    } else {
        $html .= '<p style="font-family:Arial;">No hay resultados para mostrar.</p>';
    }

} elseif ($filtro === 'cuotas') {

    $algoMostrado = false;

    if (!empty($cuotas)) {
        $html .= '<h3 style="font-family:Arial;">Resultados de Cuotas y Pagos</h3>';
        $html .= generarTablaPDF($cuotas);
        $algoMostrado = true;
    }

    if (!empty($resumen)) {
        $html .= '<h3 style="font-family:Arial; margin-top:20px;">Resumen de Cuotas por Docente</h3>';
        $html .= generarTablaPDF($resumen);
        $algoMostrado = true;
    }

    if (!$algoMostrado) {
        $html .= '<p style="font-family:Arial;">No hay resultados para mostrar.</p>';
    }

/* -----------------------------------------------
   NUEVO: FILTRO = TODO (ventas + cuotas + resumen)
----------------------------------------------- */
} elseif ($filtro === 'todo') {

    $html = '<h2 style="text-align:center; font-family:Arial;">Reporte General - TODO</h2>';

    /* 1. VENTAS */
    if (!empty($ventas)) {
        $html .= '<h3 style="font-family:Arial; margin-top:20px;">Resultados de Ventas</h3>';
        $html .= generarTablaPDF($ventas);
    } else {
        $html .= '<p style="font-family:Arial;">No hay datos de ventas.</p>';
    }

    /* 2. CUOTAS */
    if (!empty($cuotas)) {
        $html .= '<h3 style="font-family:Arial; margin-top:25px;">Resultados de Cuotas y Pagos</h3>';
        $html .= generarTablaPDF($cuotas);
    } else {
        $html .= '<p style="font-family:Arial;">No hay datos de cuotas.</p>';
    }

    /* 3. RESUMEN */
    if (!empty($resumen)) {
        $html .= '<h3 style="font-family:Arial; margin-top:25px;">Resumen de Cuotas por Docente</h3>';
        $html .= generarTablaPDF($resumen);
    } else {
        $html .= '<p style="font-family:Arial;">No hay resumen disponible.</p>';
    }
}

/* ============================================================
   FUNCIÓN PARA CREAR TABLA PDF
============================================================ */
function generarTablaPDF($data) {
    $html = '<table width="100%" border="1" cellspacing="0" cellpadding="5"
             style="border-collapse:collapse; font-size:11px; font-family:Arial;">';

    $html .= '<thead><tr style="background-color:#009688; color:#fff;">';
    foreach (array_keys($data[0]) as $col) {
        $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $col))) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($data as $fila) {
        $html .= '<tr>';
        foreach ($fila as $valor) {
            $html .= '<td style="text-align:center;">' . htmlspecialchars($valor) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$nombreArchivo = ($filtro === 'todo')
    ? 'Reporte_Todo_' . date('Ymd_His') . '.pdf'
    : 'Reporte_' . ucfirst($filtro) . '_' . date('Ymd_His') . '.pdf';
$dompdf->stream($nombreArchivo, ["Attachment" => true]);
exit;
?>
