<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR']);

require __DIR__ . '/../config/db.php';

// =========================
// VALIDAR ID DE PAGO
// =========================
if (!isset($_POST['id'])) {
    die("ID de pago no proporcionado.");
}

$id = (int) $_POST['id'];

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT * FROM pagos WHERE id = ?");
$stmt->execute([$id]);
$pagoActual = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pagoActual) {
    die("Pago no encontrado.");
}

// =========================
// VARIABLES DEL FORMULARIO
// =========================
$tipo_pago       = trim($_POST['tipo_pago']);
$monto_total     = trim($_POST['monto_total']);
$fecha_pago      = trim($_POST['fecha_pago']);
$forma_pago      = trim($_POST['forma_pago']);
$banco           = trim($_POST['banco']);
$codigo_operacion = trim($_POST['codigo_operacion']);
$titular_pago    = trim($_POST['titular_pago']);

$voucher_path = $pagoActual['voucher_path']; // mantener el anterior si no se cambia

// =========================
// SUBIDA DEL NUEVO VOUCHER
// =========================
if (isset($_FILES['voucher']) && $_FILES['voucher']['error'] === UPLOAD_ERR_OK) {

    $fileTmpPath = $_FILES['voucher']['tmp_name'];
    $fileName = $_FILES['voucher']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($fileExt, $allowedExts)) {
        die("Formato de archivo no permitido. Solo PDF, JPG, PNG o WEBP.");
    }

    //  Directorio público de subida (accesible desde el navegador)
    $uploadDir = __DIR__ . '/../public/uploads/vouchers/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Nombre único del archivo
    $newFileName = uniqid('voucher_', true) . '.' . $fileExt;
    $destPath = $uploadDir . $newFileName;

    // Mover archivo
    if (move_uploaded_file($fileTmpPath, $destPath)) {

        //  Eliminar voucher anterior si existía
        $oldPath = __DIR__ . '/../public/' . $voucher_path;
        if (!empty($voucher_path) && file_exists($oldPath)) {
            unlink($oldPath);
        }

        // Guardar nueva ruta relativa (desde public/)
        $voucher_path = 'uploads/vouchers/' . $newFileName;

    } else {
        die("Error al subir el archivo al servidor.");
    }
}

// =========================
// ACTUALIZAR EN BD
// =========================
$sql = "
    UPDATE pagos 
    SET tipo_pago = ?, 
        monto_total = ?, 
        fecha_pago = ?, 
        forma_pago = ?, 
        banco = ?, 
        codigo_operacion = ?, 
        titular_pago = ?, 
        voucher_path = ?
    WHERE id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    $tipo_pago,
    $monto_total,
    $fecha_pago,
    $forma_pago,
    $banco,
    $codigo_operacion,
    $titular_pago,
    $voucher_path,
    $id
]);

// =========================
// REDIRECCIÓN SEGÚN TEACHER
// =========================
$sqlTeacher = "
    SELECT s.teacher_id 
    FROM pagos p
    INNER JOIN ventas s ON p.sale_id = s.id
    WHERE p.id = ?
";
$stmt = $pdo->prepare($sqlTeacher);
$stmt->execute([$id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

$redirect = '../public/editar_registros.php?tab=pagos';
if ($teacher) {
    $redirect .= '&teacher_id=' . $teacher['teacher_id'];
}
$redirect .= '&updated=1';

header("Location: $redirect");
exit;
