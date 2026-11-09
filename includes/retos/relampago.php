<?php
require_once __DIR__ . '/engine.php';
function relampago_post(array $reto, array $equipo, array $part): void { quiz_post($reto, $equipo, $part); }
function relampago_render(array $reto, array $equipo, array $part): void { quiz_render($reto, $equipo, $part); }
