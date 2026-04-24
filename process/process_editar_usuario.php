<?php
require_once __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/db.php';
checkRole(['ADMINISTRADOR']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header("Location: ../public/registrar_usuario.php?tab=gestionar&error=Solicitud+inválida");
    exit;
}

$id = (int) $_POST['id'];
$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$role = trim($_POST['role']);
$team = trim($_POST['team'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($fullname) || empty($email) || empty($role)) {
    header("Location: ../public/editar_usuario.php?id=$id&error=Campos+incompletos");
    exit;
}

try {
    // 1️⃣ Actualizar tabla usuarios
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET fullname = ?, email = ?, role = ?, password = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullname, $email, $role, $hashed_password, $id]);
    } else {
        $sql = "UPDATE usuarios SET fullname = ?, email = ?, role = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullname, $email, $role, $id]);
    }

    // 2️⃣ Actualizar o insertar en tabla secundaria según el rol
    switch (strtoupper($role)) {
        case 'ASESOR':
        case 'ADVISOR':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM asesores WHERE user_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                // Actualizar si existe
                $upd = $pdo->prepare("UPDATE asesores SET nombre_completo = ?, team = ? WHERE user_id = ?");
                $upd->execute([$fullname, $team, $id]);
            } else {
                // Insertar si no existe
                $ins = $pdo->prepare("INSERT INTO asesores (user_id, nombre_completo, team) VALUES (?, ?, ?)");
                $ins->execute([$id, $fullname, $team]);
            }
            break;
        case 'ADMINISTRADOR':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM administradores WHERE user_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $upd = $pdo->prepare("UPDATE administradores SET nombre_completo = ? WHERE user_id = ?");
                $upd->execute([$fullname, $id]);
            } else {
                $ins = $pdo->prepare("INSERT INTO administradores (user_id, nombre_completo) VALUES (?, ?)");
                $ins->execute([$id, $fullname]);
            }
            break;
        case 'ADMISION':
        case 'ADMISIÓN':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admisiones WHERE user_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $upd = $pdo->prepare("UPDATE admisiones SET nombre_completo = ? WHERE user_id = ?");
                $upd->execute([$fullname, $id]);
            } else {
                $ins = $pdo->prepare("INSERT INTO admisiones (user_id, nombre_completo) VALUES (?, ?)");
                $ins->execute([$id, $fullname]);
            }
            break;
    }

    // 3️⃣ Redirigir con mensaje de éxito
    header("Location: ../public/registrar_usuario.php?tab=gestionar&msg=Usuario+actualizado+correctamente");
    exit;

} catch (PDOException $e) {
    header("Location: ../public/editar_usuario.php?id=$id&error=" . urlencode("Error al actualizar: " . $e->getMessage()));
    exit;
}
