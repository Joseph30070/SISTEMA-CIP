<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $id = $_POST['id'];
    $nombres = $_POST['nombres'];
    $apellidos = $_POST['apellidos'];
    $dni = $_POST['dni'];
    $celular = $_POST['celular'];
    $email = $_POST['email'];
    $nivel = $_POST['nivel'];
    $departamento = $_POST['departamento'];
    $provincia = $_POST['provincia'];
    $distrito = $_POST['distrito'];
    $especialidadesSeleccionadas = $_POST['especialidades'] ?? [];

    /* =====================================================
       VALIDAR DNI — PERMITIR SI PERTENECE A DOCENTE ELIMINADO
       ===================================================== */
    $stmtDNI = $pdo->prepare("
        SELECT id, deleted_at 
        FROM docentes
        WHERE dni = ? AND id != ?
    ");
    $stmtDNI->execute([$dni, $id]);
    $existe = $stmtDNI->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        if ($existe['deleted_at'] !== null) {

            // RENOMBRAR DNI DE DOCENTE ELIMINADO
            $nuevoDNIEliminado = $dni . "__deleted_" . time();

            $stmtRenombre = $pdo->prepare("
                UPDATE docentes 
                SET dni = ?
                WHERE id = ?
            ");
            $stmtRenombre->execute([$nuevoDNIEliminado, $existe['id']]);

        } else {
            $_SESSION['flash_message'] = "El DNI pertenece a otro docente activo.";
            $_SESSION['flash_type'] = "error";

            header("Location: ../public/editar_docente.php?id=$id");
            exit;
        }
    }

    /* =====================================================
       SUBIDA DE COPIA DE DNI
       ===================================================== */
    $stmtCheck = $pdo->prepare("SELECT copia_dni_path FROM docentes WHERE id=?");
    $stmtCheck->execute([$id]);
    $current = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    $currentDNIPath = $current['copia_dni_path'] ?? null;

    $copiaDNIPath = $currentDNIPath;

    if (isset($_FILES['copia_dni']) && $_FILES['copia_dni']['error'] === UPLOAD_ERR_OK) {

        $fileTmpPath = $_FILES['copia_dni']['tmp_name'];
        $fileName = $_FILES['copia_dni']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (!in_array($fileExt, $allowedExts)) {

            $_SESSION['flash_message'] = "Formato de archivo no permitido.";
            $_SESSION['flash_type'] = "error";

            header("Location: ../public/editar_docente.php?id=$id");
            exit;
        }

        $uploadDir = __DIR__ . '/../public/uploads/dni/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $newFileName = uniqid('dni_', true) . '.' . $fileExt;
        $destPath = $uploadDir . $newFileName;

        if (!empty($currentDNIPath) && file_exists(__DIR__ . '/../public/' . $currentDNIPath)) {
            unlink(__DIR__ . '/../public/' . $currentDNIPath);
        }

        if (!move_uploaded_file($fileTmpPath, $destPath)) {

            $_SESSION['flash_message'] = "No se pudo guardar la copia del DNI.";
            $_SESSION['flash_type'] = "error";

            header("Location: ../public/editar_docente.php?id=$id");
            exit;
        }

        $copiaDNIPath = 'uploads/dni/' . $newFileName;
    }

    /* =====================================================
       ACTUALIZAR DOCENTE
       ===================================================== */
    try {

        $stmt = $pdo->prepare("
            UPDATE docentes 
            SET nombres=?, apellidos=?, dni=?, celular=?, email=?, 
                departamento=?, provincia=?, distrito=?, nivel=?, copia_dni_path=? 
            WHERE id=?
        ");
        $stmt->execute([
            $nombres, $apellidos, $dni, $celular, $email,
            $departamento, $provincia, $distrito, $nivel, $copiaDNIPath,
            $id
        ]);

        /* =====================================================
           ACTUALIZAR ESPECIALIDADES
           ===================================================== */
        $pdo->prepare("DELETE FROM docente_especialidad WHERE teacher_id=?")->execute([$id]);

        $stmtEsp = $pdo->prepare("
            INSERT INTO docente_especialidad (teacher_id, specialty_id)
            VALUES (?, ?)
        ");

        foreach ($especialidadesSeleccionadas as $espId) {
            $stmtEsp->execute([$id, $espId]);
        }

        $_SESSION['flash_message'] = 'Docente actualizado correctamente.';
        $_SESSION['flash_type'] = 'success';

        header('Location: ../public/home.php');
        exit;

    } catch (PDOException $e) {

        $_SESSION['flash_message'] = "Error al actualizar: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";

        header("Location: ../public/editar_docente.php?id=$id");
        exit;
    }
}
