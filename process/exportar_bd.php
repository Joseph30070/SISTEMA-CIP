<?php
// Cargar config.php para obtener los datos reales de conexión
$config = require __DIR__ . '/../config/config.php';

// Extraer credenciales desde config.php
$host = $config['db']['host'] ?? '127.0.0.1';
$dbname = $config['db']['name'] ?? '2_colegio_db';
$user = $config['db']['user'] ?? 'root';
$pass = $config['db']['pass'] ?? '';

// Ruta del mysqldump de XAMPP
$mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

// Nombre del archivo generado
$nombreArchivo = "backup_" . $dbname . "_" . date("Y-m-d_H-i-s") . ".sql";

// Comando para exportar
$comando = "\"$mysqldump\" --host=$host --user=$user --password=$pass --databases $dbname > \"$nombreArchivo\"";

// Ejecutar el comando
exec($comando);

// Enviar el archivo al navegador
header("Content-Disposition: attachment; filename=$nombreArchivo");
header("Content-Type: application/sql");
readfile($nombreArchivo);

// Eliminar el archivo temporal
unlink($nombreArchivo);

exit;
?>
