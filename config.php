<?php
// ===== Configuración general =====
if (is_file(__DIR__ . '/config.local.php')) require __DIR__ . '/config.local.php';

defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_NAME') || define('DB_NAME', 'olimpiadas');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');

defined('BASE_URL') || define('BASE_URL', 'servicio/olimpiadas/');

define('APP_NAME', 'Olimpiadas Virtuales Corporativas');
define('APP_ORG',  'Horizonte Abierto');
define('MAX_INTENTOS_LOGIN', 8);   // intentos fallidos antes de bloquear
define('BLOQUEO_SEGUNDOS', 45);
define('MAX_PARTICIPANTES_EQUIPO', 20);

// Puntos de torneo por enfrentamiento (sistema suizo)
defined('TORNEO_PTS_VICTORIA') || define('TORNEO_PTS_VICTORIA', 3);
defined('TORNEO_PTS_EMPATE')   || define('TORNEO_PTS_EMPATE', 1);
defined('TORNEO_PTS_DERROTA')  || define('TORNEO_PTS_DERROTA', 0);

date_default_timezone_set('America/Mexico_City');
