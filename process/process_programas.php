<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR']); // Solo administradores

require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? '';

$msg   = '';
$error = '';

/* ================================
   AGREGAR: restaurar si existe eliminado
   ================================ */

if ($action === 'add') {

    $nombre = trim($_POST['nombre_programa']);

    if ($nombre !== '') {

        // Verificar si existe un programa con ese nombre
        $stmt = $pdo->prepare("SELECT id, deleted_at FROM programas WHERE nombre_programa = :nombre");
        $stmt->execute(['nombre' => $nombre]);
        $programa = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($programa) {
            // Si existe eliminado → restaurar
            if ($programa['deleted_at'] !== null) {
                $stmt = $pdo->prepare("
                    UPDATE programas 
                    SET deleted_at = NULL 
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $programa['id']]);
                $msg = "Programa restaurado correctamente";

            } else {
                // Existe activo
                $error = "El programa ya existe.";
            }

        } else {
            // Insertar nuevo
            $stmt = $pdo->prepare("INSERT INTO programas (nombre_programa) VALUES (:nombre_programa)");
            $stmt->execute(['nombre_programa' => $nombre]);
            $msg = "Programa agregado correctamente";
        }

    } else {
        $error = "El nombre del programa es obligatorio.";
    }


/* ================================
   EDITAR: NO restaurar nunca
   ================================ */

} elseif ($action === 'edit') {

    $id = $_POST['id'] ?? 0;
    $nombre = trim($_POST['nombre_programa']);

    if ($id && $nombre !== '') {

        // Buscar si existe otro programa con ese mismo nombre (activo o eliminado)
        $stmt = $pdo->prepare("
            SELECT id, deleted_at 
            FROM programas 
            WHERE nombre_programa = :nombre AND id != :id
        ");
        $stmt->execute(['nombre' => $nombre, 'id' => $id]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existe) {

            if ($existe['deleted_at'] !== null) {

                // Renombrar el eliminado para evitar conflicto UNIQUE
                $nombreTemporal = $nombre . "__deleted_" . time();

                $stmt = $pdo->prepare("
                    UPDATE programas 
                    SET nombre_programa = :nuevo_nombre 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'nuevo_nombre' => $nombreTemporal,
                    'id' => $existe['id']
                ]);

                // Ahora actualizamos el actual sin conflicto
                $stmt = $pdo->prepare("
                    UPDATE programas 
                    SET nombre_programa = :nombre_programa
                    WHERE id = :id
                ");
                $stmt->execute(['nombre_programa' => $nombre, 'id' => $id]);

                $msg = "Programa actualizado correctamente.";

            } else {
                // Existe ACTIVO → no permitir
                $error = "Ya existe un programa activo con ese nombre.";
            }

        } else {

            // No existe duplicado -> actualizar normal
            $stmt = $pdo->prepare("
                UPDATE programas 
                SET nombre_programa = :nombre_programa
                WHERE id = :id
            ");
            $stmt->execute(['nombre_programa' => $nombre, 'id' => $id]);

            $msg = "Programa actualizado correctamente.";
        }

    } else {
        $error = "Datos inválidos para actualizar el programa.";
    }

/* ================================
   ELIMINAR (soft-delete)
   ================================ */

} elseif ($action === 'delete') {

    $id = $_POST['id'] ?? 0;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE programas SET deleted_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $msg = "Programa eliminado correctamente";
    } else {
        $error = "ID inválido para eliminar el programa.";
    }

} else {
    $error = "Acción no válida.";
}


// Redirección
if ($msg !== '') {
    header("Location: ../public/tipo_certificacion_programas.php?msg=" . urlencode($msg));
    exit;
}

if ($error !== '') {
    header("Location: ../public/tipo_certificacion_programas.php?error=" . urlencode($error));
    exit;
}

header("Location: ../public/tipo_certificacion_programas.php");
exit;
