<?php
// public/process_runner.php

// Inicia sesión
session_start();

// Incluir auth si quieres seguridad
require_once __DIR__ . '/../config/auth.php';
checkRole(['ADMINISTRADOR', 'ASESOR']);

// Incluir process.php desde la carpeta segura
require_once __DIR__ . '/../process/process.php';
