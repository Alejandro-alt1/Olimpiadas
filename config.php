<?php
// ===== Configuración general =====
define('DB_HOST', '172.17.99.231');
define('DB_NAME', 'olimpiadas');
define('DB_USER', 'olimpiadas');
define('DB_PASS', '123');            // XAMPP por defecto: sin contraseña

// Carpeta del proyecto dentro de htdocs (sin barra final). Si lo pones en la raíz, deja ''.
define('BASE_URL', '/olimpiadas3');

define('APP_NAME', 'Olimpiadas Virtuales Corporativas');
define('APP_ORG',  'Horizonte Abierto');
define('MAX_INTENTOS_LOGIN', 5);   // intentos fallidos antes de bloquear
define('BLOQUEO_SEGUNDOS', 60);

date_default_timezone_set('America/Mexico_City');
