<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

checkRole(['ADMINISTRADOR']); // Solo admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $dni = trim($_POST['dni']);
    $celular = trim($_POST['celular']);
    $email = trim($_POST['email']);
    $nivel = trim($_POST['nivel']);
    $departamento = trim($_POST['departamento']);
    $provincia = trim($_POST['provincia']);
    $distrito = trim($_POST['distrito']);
    $especialidadesSeleccionadas = $_POST['especialidades'] ?? [];

    $uploadDir = __DIR__ . '/../public/uploads/dni/';
    $copiaDNIPath = null;

    /* ===================================================
       SUBIDA DE ARCHIVO
       =================================================== */
    if (isset($_FILES['copia_dni']) && $_FILES['copia_dni']['error'] === UPLOAD_ERR_OK) {

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = pathinfo($_FILES['copia_dni']['name'], PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($_FILES['copia_dni']['name'], PATHINFO_EXTENSION));

        $filename = uniqid('dni_', true) . '.' . $ext;

        if (!move_uploaded_file($_FILES['copia_dni']['tmp_name'], $uploadDir . $filename)) {
            $_SESSION['flash_message'] = "Error al subir la copia de DNI.";
            $_SESSION['flash_type'] = "error";
            header("Location: ../public/home.php");
            exit;
        }

        $copiaDNIPath = 'uploads/dni/' . $filename;
    }

    /* ===================================================
       BUSCAR DOCENTE POR DNI (incluye eliminados)
       =================================================== */
    $stmt = $pdo->prepare("
        SELECT id, deleted_at 
        FROM docentes 
        WHERE dni = ?
        LIMIT 1
    ");
    $stmt->execute([$dni]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    try {

        /* ===================================================
           CASO 1: EXISTE PERO ESTÁ ELIMINADO → RESTAURAR
           =================================================== */
        if ($existing && $existing['deleted_at'] !== null) {

            $pdo->beginTransaction();

            $teacherId = $existing['id'];

            $query = "
                UPDATE docentes
                SET nombres=?, apellidos=?, celular=?, email=?, 
                    departamento=?, provincia=?, distrito=?, nivel=?, 
                    deleted_at=NULL
            ";

            $params = [
                $nombres, $apellidos, $celular, $email,
                $departamento, $provincia, $distrito, $nivel
            ];

            if ($copiaDNIPath) {
                $query .= ", copia_dni_path=? ";
                $params[] = $copiaDNIPath;
            }

            $query .= "WHERE id=?";
            $params[] = $teacherId;

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            // Actualizar especialidades
            $pdo->prepare("DELETE FROM docente_especialidad WHERE teacher_id=?")
                ->execute([$teacherId]);

            $stmtEsp = $pdo->prepare("
                INSERT INTO docente_especialidad (teacher_id, specialty_id)
                VALUES (?, ?)
            ");

            foreach ($especialidadesSeleccionadas as $espId) {
                $stmtEsp->execute([$teacherId, $espId]);
            }

            $pdo->commit();

            $_SESSION['flash_message'] = "Docente restaurado correctamente.";
            $_SESSION['flash_type'] = "success";
            header("Location: ../public/home.php");
            exit;
        }

        /* ===================================================
           CASO 2: EXISTE Y ESTÁ ACTIVO → ERROR
           =================================================== */
        if ($existing && $existing['deleted_at'] === null) {
            $_SESSION['flash_message'] = "El DNI ya pertenece a un docente activo.";
            $_SESSION['flash_type'] = "error";
            header("Location: ../public/home.php");
            exit;
        }

        /* ===================================================
           CASO 3: NO EXISTE → INSERT NORMAL
           =================================================== */
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO docentes 
                (nombres, apellidos, dni, celular, email, 
                 departamento, provincia, distrito, nivel, copia_dni_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $nombres, $apellidos, $dni, $celular, $email,
            $departamento, $provincia, $distrito, $nivel,
            $copiaDNIPath
        ]);

        $teacherId = $pdo->lastInsertId();

        $stmtEsp = $pdo->prepare("
            INSERT INTO docente_especialidad (teacher_id, specialty_id)
            VALUES (?, ?)
        ");

        foreach ($especialidadesSeleccionadas as $espId) {
            $stmtEsp->execute([$teacherId, $espId]);
        }

        $pdo->commit();

        $_SESSION['flash_message'] = "Docente agregado correctamente.";
        $_SESSION['flash_type'] = "success";
        header("Location: ../public/home.php");
        exit;

    } catch (Exception $e) {

        if ($pdo->inTransaction()) $pdo->rollBack();

        $_SESSION['flash_message'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
        header("Location: ../public/home.php");
        exit;
    }
}
