<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$visible = config_get('marcador_visible', '1') === '1' || admin_actual();
if (!$visible) { echo json_encode(['visible' => false]); exit; }

$retos  = array_column(retos_all(), 'id');
$matriz = matriz_puntos();
$out = [];
foreach (ranking() as $f) {
    $out[] = [
        'pos' => (int)$f['pos'], 'medalla' => medalla((int)$f['pos']), 'empate' => (bool)$f['empate'],
        'nombre' => $f['nombre'], 'color' => $f['color'], 'total' => (int)$f['total'],
        'puntos' => $matriz[$f['id']] ?? new stdClass(),
    ];
}
echo json_encode(['visible' => true, 'retos' => array_map('intval', $retos), 'equipos' => $out, 'hora' => date('H:i:s')],
    JSON_UNESCAPED_UNICODE);
