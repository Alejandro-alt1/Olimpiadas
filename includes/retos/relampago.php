<?php
require_once __DIR__ . '/engine.php';
function relampago_post(array $reto, array $equipo): void { quiz_post($reto, $equipo); }
function relampago_render(array $reto, array $equipo): void { quiz_render($reto, $equipo); }
