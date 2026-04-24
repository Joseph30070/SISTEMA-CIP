<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo ADMIN puede ejecutar este proceso
checkRole(['ADMINISTRADOR']);

$error = '';
$success = '';

// Obtener datos del formulario
$fullname          = trim($_POST['fullname'] ?? '');
$email             = trim($_POST['email'] ?? '');
$password          = trim($_POST['password'] ?? '');
$confirm_password  = trim($_POST['confirm_password'] ?? '');
$role              = $_POST['role'] ?? 'ADMISION';
$team              = trim($_POST['team'] ?? 'General'); // Para ASESOR

// Validar campos obligatorios
if (!empty($_POST)) {

    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Todos los campos son obligatorios";

    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";

    } else {

        // ======================================
        // Buscar email incluyendo eliminados
        // ======================================
        $stmt = $pdo->prepare("
            SELECT id, deleted_at 
            FROM usuarios 
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        // Hashear contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Validar rol permitido
        $valid_roles = ['ADMINISTRADOR','ADMISION','ASESOR'];
        if (!in_array($role, $valid_roles)) {
            $role = 'ADMISION';
        }

        // ======================================
        // CASO 1: El correo existe pero está eliminado → RESTAURAR
        // ======================================
        if ($existing && $existing['deleted_at'] !== null) {

            try {
                $pdo->beginTransaction();

                $user_id = $existing['id'];

                // Restaurar usuario
                $stmt = $pdo->prepare("
                    UPDATE usuarios
                    SET fullname = ?, password = ?, role = ?, deleted_at = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$fullname, $hashed_password, $role, $user_id]);

                // Restaurar/actualizar tablas por rol
                if ($role === 'ASESOR') {
                    $stmt = $pdo->prepare("
                        UPDATE asesores
                        SET nombre_completo = ?, team = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$fullname, $team, $user_id]);
                }

                if ($role === 'ADMISION') {
                    $stmt = $pdo->prepare("
                        UPDATE admisiones
                        SET nombre_completo = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$fullname, $user_id]);
                }

                if ($role === 'ADMINISTRADOR') {
                    $stmt = $pdo->prepare("
                        UPDATE administradores
                        SET nombre_completo = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$fullname, $user_id]);
                }

                $pdo->commit();
                $success = "Usuario restaurado correctamente";

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = "Error al restaurar el usuario: " . $e->getMessage();
            }

        }
        // ======================================
        // CASO 2: El correo existe y está activo → ERROR
        // ======================================
        elseif ($existing && $existing['deleted_at'] === null) {
            $error = "El correo ya pertenece a un usuario activo";

        }
        // ======================================
        // CASO 3: El correo NO existe → INSERT NORMAL
        // ======================================
        else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (fullname, email, password, role)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$fullname, $email, $hashed_password, $role]);

                $user_id = $pdo->lastInsertId();

                // Insertar según rol
                if ($role === 'ASESOR') {
                    $stmt = $pdo->prepare("
                        INSERT INTO asesores (nombre_completo, team, user_id)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$fullname, $team, $user_id]);
                }

                if ($role === 'ADMISION') {
                    $stmt = $pdo->prepare("
                        INSERT INTO admisiones (user_id, nombre_completo)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$user_id, $fullname]);
                }

                if ($role === 'ADMINISTRADOR') {
                    $stmt = $pdo->prepare("
                        INSERT INTO administradores (user_id, nombre_completo)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$user_id, $fullname]);
                }

                $pdo->commit();
                $success = "Usuario creado correctamente";

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = "Error al crear usuario: " . $e->getMessage();
            }
        }
    }

    // ===============================
    // REDIRECCIÓN CON MENSAJE 
    // ===============================
    if (!empty($success)) {
        header('Location: ../public/registrar_usuario.php?msg=' . urlencode($success) . '&tab=registrar');
        exit;
    }

    if (!empty($error)) {
        header('Location: ../public/registrar_usuario.php?error=' . urlencode($error) . '&tab=registrar');
        exit;
    }
}
include __DIR__ . '/../public/registrar_usuario.php';
?>
