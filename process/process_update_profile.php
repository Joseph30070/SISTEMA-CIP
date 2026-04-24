<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

require __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'];

// Obtener datos actuales del usuario incluyendo el rol
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $userId]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: ../public/login.php");
    exit;
}

/* ============================================================
    1. ACTUALIZAR PERFIL (nombre, correo, imagen)
   ============================================================ */
if (isset($_POST['update_profile'])) {

    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($fullname === "" || $email === "") {
        $msg = urlencode("<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-4'>Todos los campos son obligatorios.</div>");
        header("Location: ../public/editar_perfil.php?perfil_msg={$msg}");
        exit;
    }

    // Mantener o reemplazar foto actual
    $profileImagePath = $usuario['profile_image']; // Ruta actual en DB

    if (!empty($_FILES['profile_image']['name'])) {
        $file = $_FILES['profile_image'];

        if ($file['error'] !== UPLOAD_ERR_OK && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $msg = urlencode("<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-4'>Error al subir la imagen.</div>");
            header("Location: ../public/editar_perfil.php?perfil_msg={$msg}");
            exit;
        }

        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg','jpeg','png','webp'];

            if (!in_array($ext, $permitidos)) {
                $msg = urlencode("<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-4'>Formato de imagen no permitido.</div>");
                header("Location: ../public/editar_perfil.php?perfil_msg={$msg}");
                exit;
            }

            $uploadsDir = __DIR__ . '/../public/uploads/perfiles/';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

            // Si ya existe una foto, la reemplazamos
            if (!empty($profileImagePath) && file_exists(__DIR__ . '/../public/' . $profileImagePath)) {
                $destino = __DIR__ . '/../public/' . $profileImagePath;
            } else {
                // Si no hay foto previa, creamos un nombre nuevo
                $newName = "user_" . $userId . "_" . time() . "." . $ext;
                $destino = $uploadsDir . $newName;
                $profileImagePath = "uploads/perfiles/" . $newName;
            }

            // Subir la nueva imagen (reemplaza la vieja si existe)
            if (!move_uploaded_file($file['tmp_name'], $destino)) {
                $msg = urlencode("<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-4'>No se pudo guardar la imagen.</div>");
                header("Location: ../public/editar_perfil.php?perfil_msg={$msg}");
                exit;
            }
        }
    }

    // Actualizar tabla usuarios
    $update = $pdo->prepare("
        UPDATE usuarios 
        SET fullname = :fullname,
            email = :email,
            profile_image = :img
        WHERE id = :id
    ");
    $update->execute([
        'fullname' => $fullname,
        'email'    => $email,
        'img'      => $profileImagePath,
        'id'       => $userId
    ]);

   // Actualizar tabla secundaria según el rol usando solo UPDATE
        switch ($usuario['role']) {
            case 'ADMINISTRADOR':
                $pdo->prepare("
                    UPDATE administradores 
                    SET nombre_completo = :nombre
                    WHERE user_id = :uid
                ")->execute(['uid' => $userId, 'nombre' => $fullname]);
                break;

            case 'ADMISION':
                $pdo->prepare("
                    UPDATE admisiones 
                    SET nombre_completo = :nombre
                    WHERE user_id = :uid
                ")->execute(['uid' => $userId, 'nombre' => $fullname]);
                break;

            case 'ASESOR':
                $pdo->prepare("
                    UPDATE asesores 
                    SET nombre_completo = :nombre
                    WHERE user_id = :uid
                ")->execute(['uid' => $userId, 'nombre' => $fullname]);
                break;
        }


    // Actualizar sesión
    $_SESSION['fullname'] = $fullname;

    $msg = urlencode("<div class='bg-green-100 text-green-700 px-4 py-2 rounded mb-4'>Perfil actualizado correctamente✅</div>");
    header("Location: ../public/editar_perfil.php?perfil_msg={$msg}");
    exit;
}

/* ============================================================
    2. ACTUALIZAR CONTRASEÑA
   ============================================================ */
if (isset($_POST['update_password'])) {

    $actual    = $_POST['password_actual'] ?? '';
    $nueva     = $_POST['password_nueva'] ?? '';
    $confirmar = $_POST['password_confirmar'] ?? '';

    if ($actual === "" || $nueva === "" || $confirmar === "") {
        $msg = urlencode("<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-4'>Completa todos los campos.</div>");
        header("Location: ../public/editar_perfil.php?password_msg={$msg}");
        exit;
    }

    if ($actual !== $usuario['password'] && !password_verify($actual, $usuario['password'])) {
        $msg = urlencode("<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-4'>Contraseña actual incorrecta.</div>");
        header("Location: ../public/editar_perfil.php?password_msg={$msg}");
        exit;
    }

    if ($nueva !== $confirmar) {
        $msg = urlencode("<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-4'>Las contraseñas no coinciden.</div>");
        header("Location: ../public/editar_perfil.php?password_msg={$msg}");
        exit;
    }

    if (strlen($nueva) < 4) {
        $msg = urlencode("<div class='bg-red-100 text-red-700 px-4 py-2 rounded mb-4'>La nueva contraseña debe tener al menos 4 caracteres.</div>");
        header("Location: ../public/editar_perfil.php?password_msg={$msg}");
        exit;
    }

    $hashed = password_hash($nueva, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE usuarios SET password = :pass WHERE id = :id");
    $upd->execute(['pass' => $hashed, 'id' => $userId]);

    $msg = urlencode("<div class='bg-green-100 text-green-700 px-4 py-2 rounded mb-4'>Contraseña actualizada correctamente🔒</div>");
    header("Location: ../public/editar_perfil.php?password_msg={$msg}");
    exit;
}

// Redirigir por defecto
header("Location: ../public/editar_perfil.php");
exit;
