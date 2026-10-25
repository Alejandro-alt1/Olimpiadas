<?php
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipo_id = $_POST['equipo_id'] ?? 1; // ID del equipo (puedes ajustarlo según tu sesión)
    $reto_slug = $_POST['reto_slug'] ?? '';
    $aciertos  = (int)($_POST['aciertos'] ?? 0);
    $errores   = (int)($_POST['errores'] ?? 0);
    $puntos    = (int)($_POST['puntos'] ?? 0);

    if ($reto_slug !== '') {
        try {
            $stmt = db()->prepare("INSERT INTO resultados (equipo_id, reto_slug, aciertos, errores, puntos) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$equipo_id, $reto_slug, $aciertos, $errores, $puntos]);
            
            echo json_encode(['status' => 'success', 'message' => 'Resultado guardado correctamente']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);