<?php
require_once __DIR__ . '/../config/auth.php'; // inicia sesión y valida usuario
checkRole(['ADMINISTRADOR', 'ASESOR']); // Para más seguridad

require __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? '';

if ($action === 'pago') {
    try {
        $pdo->beginTransaction();

        // Si viene sale_id desde la pestaña "Registro de Pago", solo se registra el pago.
        if (!empty($_POST['sale_id'])) {

            // ==============================
            // SUBIDA DE VOUCHER (solo pago)
            // ==============================
            $voucherPath = null;
            if (!empty($_FILES['voucher']['name']) && $_FILES['voucher']['error'] === UPLOAD_ERR_OK) {
                $dir = __DIR__ . '/../public/uploads/vouchers/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);

                $ext = strtolower(pathinfo($_FILES['voucher']['name'], PATHINFO_EXTENSION));

                // Si ya existe un voucher para este pago, reemplazarlo
                if (!empty($_POST['existing_voucher']) && file_exists(__DIR__ . '/../public/' . $_POST['existing_voucher'])) {
                    $destino = __DIR__ . '/../public/' . $_POST['existing_voucher'];
                    $voucherPath = $_POST['existing_voucher'];
                } else {
                    // Nuevo archivo
                    $originalName = pathinfo($_FILES['voucher']['name'], PATHINFO_FILENAME);
                    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName);
                    $filename = uniqid() . "_" . $safeName . '.' . $ext;
                    $destino = $dir . $filename;
                    $voucherPath = 'uploads/vouchers/' . $filename;
                }

                if (!move_uploaded_file($_FILES['voucher']['tmp_name'], $destino)) {
                    throw new Exception("No se pudo guardar el voucher");
                }
            }

            // Insertar solo en payments
            $stmtPago = $pdo->prepare("
                INSERT INTO pagos (
                    sale_id, monto_total, tipo_pago, forma_pago, fecha_pago,
                    banco, codigo_operacion, titular_pago, voucher_path
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtPago->execute([
                $_POST['sale_id'],
                $_POST['monto_total'],
                $_POST['tipo_pago'],
                $_POST['forma_pago'],
                $_POST['fecha_pago'],
                $_POST['banco'],
                $_POST['codigo_operacion'],
                $_POST['titular_pago'],
                $voucherPath
            ]);

        } else {
            // ==================================
            // REGISTRO NUEVO DOCENTE + VENTA + PAGO
            // ==================================
            $stmt = $pdo->prepare("SELECT id FROM docentes WHERE dni = ?");
            $stmt->execute([$_POST['dni']]);
            $docente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($docente) {
                $teacher_id = $docente['id'];
            } else {
                // ==============================
                // SUBIDA DE COPIA DE DNI
                // ==============================
                $copiaDniPath = null;
                if (!empty($_FILES['copia_dni']['name']) && $_FILES['copia_dni']['error'] === UPLOAD_ERR_OK) {
                    $dir = __DIR__ . '/../public/uploads/dni/';
                    if (!is_dir($dir)) mkdir($dir, 0777, true);

                    $ext = strtolower(pathinfo($_FILES['copia_dni']['name'], PATHINFO_EXTENSION));

                    if (!empty($_POST['existing_dni']) && file_exists(__DIR__ . '/../public/' . $_POST['existing_dni'])) {
                        $destino = __DIR__ . '/../public/' . $_POST['existing_dni'];
                        $copiaDniPath = $_POST['existing_dni'];
                    } else {
                        $originalName = pathinfo($_FILES['copia_dni']['name'], PATHINFO_FILENAME);
                        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName);
                        $filename = uniqid() . "_" . $safeName . '.' . $ext;
                        $destino = $dir . $filename;
                        $copiaDniPath = 'uploads/dni/' . $filename;
                    }

                    if (!move_uploaded_file($_FILES['copia_dni']['tmp_name'], $destino)) {
                        throw new Exception("No se pudo guardar la copia de DNI");
                    }
                }
                // Insertar nuevo docente
                $stmt = $pdo->prepare("
                    INSERT INTO docentes (nombres, apellidos, dni, celular, email, nivel, departamento, provincia, distrito, copia_dni_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['nombres'],
                    $_POST['apellidos'],
                    $_POST['dni'],
                    $_POST['celular'],
                    $_POST['email'],
                    $_POST['nivel'],
                    $_POST['departamento'],
                    $_POST['provincia'],
                    $_POST['distrito'],
                    $copiaDniPath
                ]);
                $teacher_id = $pdo->lastInsertId();
            }

            // ==============================
            // ACTUALIZAR O GUARDAR ESPECIALIDADES
            // ==============================
            if (!empty($_POST['especialidades'])) {
                $especialidades = array_unique($_POST['especialidades']);

                // Borrar anteriores
                $pdo->prepare("DELETE FROM docente_especialidad WHERE teacher_id = ?")->execute([$teacher_id]);

                $check = $pdo->prepare("SELECT COUNT(*) FROM especialidades WHERE id = ?");
                $insert = $pdo->prepare("INSERT INTO docente_especialidad (teacher_id, specialty_id) VALUES (?, ?)");

                foreach ($especialidades as $esp) {
                    if (!is_numeric($esp)) continue;
                    $check->execute([$esp]);
                    if ($check->fetchColumn() == 0) continue;
                    $insert->execute([$teacher_id, $esp]);
                }
            }

            // ==============================
            // REGISTRO DE VENTA
            // ==============================

            // Ajustar proceso_certificacion según certificado
            $certificado = $_POST['certificado'];

            if ($certificado === 'SI') {
                $proceso_certificacion = 'En Proceso';
            } else {
                $proceso_certificacion = 'No tiene';
            }

            $stmtSale = $pdo->prepare("
                INSERT INTO ventas (
                    teacher_id, advisor_id, tipo_transaccion, curso_id, programa_id, modalidad, 
                    precio_programa, proceso_certificacion, mencion, inicio_programa, obs_programa, certificado
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
           $stmtSale->execute([
            $teacher_id,
            $_POST['asesor_id'],
            $_POST['tipo_transaccion'],
            $_POST['tipo_programa'],
            $_POST['programa'],
            $_POST['modalidad'],
            $_POST['precio_programa'],
            $proceso_certificacion,  // <-- ahora SI funciona
            $_POST['mencion'],
            $_POST['inicio_programa'],
            $_POST['obs_programa'] ?? null,
            $certificado
        ]);

            $sale_id = $pdo->lastInsertId();

            // ==============================
            // SUBIDA DE VOUCHER (nuevo pago)
            // ==============================
            $voucherPath = null;
            if (!empty($_FILES['voucher']['name']) && $_FILES['voucher']['error'] === UPLOAD_ERR_OK) {
                $dir = __DIR__ . '/../public/uploads/vouchers/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true); // ✅ crea carpetas
                }

                $originalName = pathinfo($_FILES['voucher']['name'], PATHINFO_FILENAME);
                $ext = strtolower(pathinfo($_FILES['voucher']['name'], PATHINFO_EXTENSION));
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName);
                $filename = uniqid() . "_" . $safeName . '.' . $ext;

                move_uploaded_file($_FILES['voucher']['tmp_name'], $dir . $filename);
                $voucherPath = 'uploads/vouchers/' . $filename;
            }

            // Insertar pago asociado
            $stmtPago = $pdo->prepare("
                INSERT INTO pagos (sale_id, monto_total, tipo_pago, forma_pago, fecha_pago, banco, codigo_operacion, titular_pago, voucher_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtPago->execute([
                $sale_id,
                $_POST['monto_total'],
                $_POST['tipo_pago'],
                $_POST['forma_pago'],
                $_POST['fecha_pago'],
                $_POST['banco'],
                $_POST['codigo_operacion'],
                $_POST['titular_pago'],
                $voucherPath
            ]);
        }

        $pdo->commit();
        header("Location: registro.php?success_pago=1");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al registrar pago: " . $e->getMessage());
    }
}
