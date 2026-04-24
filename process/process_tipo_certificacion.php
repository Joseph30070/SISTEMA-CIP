<?php
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR']);

require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? '';

$msg   = '';
$error = '';

/* ================================
   AGREGAR → restaurar si existe eliminado
   ================================ */

if ($action === 'add') {

    $nombre = trim($_POST['nombre']);

    if ($nombre !== '') {

        $stmt = $pdo->prepare("SELECT id, deleted_at FROM tipo_certificacion WHERE nombre = :nombre");
        $stmt->execute(['nombre' => $nombre]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {

            if ($row['deleted_at'] !== null) {

                // Restaurar
                $stmt = $pdo->prepare("
                    UPDATE tipo_certificacion
                    SET deleted_at = NULL
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $row['id']]);

                $msg = "Tipo de certificación restaurado correctamente.";

            } else {
                $error = "El tipo de certificación ya existe y está activo.";
            }

        } else {

            $stmt = $pdo->prepare("INSERT INTO tipo_certificacion (nombre) VALUES (:nombre)");
            $stmt->execute(['nombre' => $nombre]);

            $msg = "Tipo de certificación agregado correctamente.";
        }

    } else {
        $error = "El nombre es obligatorio.";
    }


/* ================================
   EDITAR → renombrar eliminados para evitar duplicado
   ================================ */

} elseif ($action === 'edit') {

    $id = $_POST['id'] ?? 0;
    $nombre = trim($_POST['nombre']);

    if ($id && $nombre !== '') {

        // Buscar si existe otro con el mismo nombre
        $stmt = $pdo->prepare("
            SELECT id, deleted_at
            FROM tipo_certificacion
            WHERE nombre = :nombre AND id != :id
        ");
        $stmt->execute(['nombre' => $nombre, 'id' => $id]);
        $dup = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dup) {

            if ($dup['deleted_at'] !== null) {

                // Renombrar el eliminado para liberar el nombre
                $nuevoNombreEliminado = $nombre . "__deleted_" . time();

                $stmt = $pdo->prepare("
                    UPDATE tipo_certificacion
                    SET nombre = :nuevo
                    WHERE id = :id
                ");
                $stmt->execute([
                    'nuevo' => $nuevoNombreEliminado,
                    'id'    => $dup['id']
                ]);

                // Ahora actualizar el registro actual con el nombre deseado
                $stmt = $pdo->prepare("
                    UPDATE tipo_certificacion
                    SET nombre = :nombre
                    WHERE id = :id
                ");
                $stmt->execute(['nombre' => $nombre, 'id' => $id]);

                $msg = "Tipo de certificación actualizado correctamente.";

            } else {

                // Existe un activo → no permitido
                $error = "Ya existe un tipo de certificación activo con ese nombre.";
            }

        } else {

            // No hay duplicado → actualizar normal
            $stmt = $pdo->prepare("
                UPDATE tipo_certificacion
                SET nombre = :nombre
                WHERE id = :id
            ");
            $stmt->execute(['nombre' => $nombre, 'id' => $id]);

            $msg = "Tipo de certificación actualizado correctamente.";
        }

    } else {
        $error = "Datos inválidos para actualizar.";
    }


/* ================================
   ELIMINAR (soft delete)
   ================================ */

} elseif ($action === 'delete') {

    $id = $_POST['id'] ?? 0;

    if ($id) {

        $stmt = $pdo->prepare("UPDATE tipo_certificacion SET deleted_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $msg = "Tipo de certificación eliminado correctamente.";

    } else {
        $error = "ID inválido para eliminar.";
    }

} else {
    $error = "Acción no válida.";
}


/* ================================
   REDIRECCIÓN FINAL
   ================================ */

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
