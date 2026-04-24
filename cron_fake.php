<?php
require_once __DIR__ . '/config/db.php';

// BORRAR DEFINITIVAMENTE docentes (soft delete) despues de 15 dias
$pdo->query("
    DELETE FROM docentes
    WHERE deleted_at IS NOT NULL
    AND deleted_at < (NOW() - INTERVAL 15 DAY)
");

// BORRAR DEFINITIVAMENTE usuarios (soft delete) despues de 15 dias
$pdo->query("
    DELETE FROM usuarios
    WHERE deleted_at IS NOT NULL
    AND deleted_at < (NOW() - INTERVAL  15 DAY)
");
// BORRADO DEFINITIVO de cursos despues de 15 dias
$pdo->query("
    DELETE FROM tipo_certificacion
    WHERE deleted_at IS NOT NULL
    AND deleted_at < (NOW() - INTERVAL 15 DAY)
");
// BORRADO DEFINITIVO de programas despues de 15 dias
$pdo->query("
    DELETE FROM programas
    WHERE deleted_at IS NOT NULL
    AND deleted_at < (NOW() - INTERVAL 15 DAY)
");
// Borrar ventas despues de 15 dias
$pdo->query("
    DELETE FROM ventas
    WHERE deleted_at IS NOT NULL
    AND deleted_at < (NOW() - INTERVAL 15 DAY)
");

// Borrar pagos despues de 15 dias
$pdo->query("
    DELETE FROM pagos
    WHERE deleted_at IS NOT NULL
    AND deleted_at < (NOW() - INTERVAL 15 DAY)
");