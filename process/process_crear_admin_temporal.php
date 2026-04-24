<?php
require __DIR__ . '/../config/db.php';

// Datos del nuevo administrador
$fullname = 'Administrador Principal';
$email    = 'admin@gmail.com';
$password = 'admin123';
$role     = 'ADMINISTRADOR';

// Hashear la contraseña
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    // Insertar en usuarios
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (fullname, email, password, role)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$fullname, $email, $hashed_password, $role]);

    $user_id = $pdo->lastInsertId();

    // Insertar en administradores
    $stmt = $pdo->prepare("
        INSERT INTO administradores (user_id, nombre_completo)
        VALUES (?, ?)
    ");
    $stmt->execute([$user_id, $fullname]);

    $pdo->commit();
    echo "Administrador creado correctamente. ID: $user_id";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error al crear administrador: " . $e->getMessage();
}
