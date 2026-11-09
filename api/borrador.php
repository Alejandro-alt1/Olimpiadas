<?php
/* Estado del borrador compartido de Creatividad (lo consulta la página cada 8 s). */
require __DIR__ . '/../includes/bootstrap.php';
session_write_close();
$p = participante_actual();
if (!$p) json_out(['error' => 'sesion'], 401);
require_once __DIR__ . '/../includes/retos/creatividad.php';
$eq = (int)$p['equipo_id'];
$b = cr_borrador($eq);
$st = cr_estado($eq);
json_out(['version' => (int)$b['version'], 'editor' => $b['editor'], 'enviada' => cr_propuesta($eq) !== null,
          'listos' => $st['listos'], 'necesarios' => $st['necesarios'], 'activos' => $st['activos']]);
