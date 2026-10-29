<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin(); csrf_check();

function pin_aleatorio(): string { return str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT); }
function color_valido(string $c): string { return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : '#3b82f6'; }

if (is_post()) {
    $a = $_POST['accion'] ?? '';
    try {
        if ($a === 'guardar') {
            foreach ($_POST['eq'] ?? [] as $id => $d) {
                $miembros = max(1, min(10, (int)($d['miembros'] ?? 5)));
                $pin = preg_replace('/\D/', '', $d['pin'] ?? '');
                if (strlen($pin) < 4) { flash('El código de "' . ($d['nombre'] ?? '') . '" debe tener al menos 4 dígitos.', 'err'); continue; }
                db()->prepare('UPDATE equipos SET nombre=?, color=?, capitan=?, miembros=?, pin=?, activo=? WHERE id=?')
                    ->execute([trim($d['nombre']), color_valido($d['color'] ?? ''), trim($d['capitan'] ?? ''), $miembros,
                               substr($pin, 0, 8), isset($d['activo']) ? 1 : 0, (int)$id]);
            }
            bitacora('sistema', 'Equipos actualizados');
            flash('Equipos guardados.');
        } elseif ($a === 'nuevo') {
            $n = trim($_POST['nombre'] ?? '');
            if ($n === '') throw new RuntimeException('Escribe un nombre.');
            db()->prepare('INSERT INTO equipos (nombre, color, miembros, pin) VALUES (?,?,5,?)')
                ->execute([$n, color_valido($_POST['color'] ?? ''), pin_aleatorio()]);
            $nid = (int)db()->lastInsertId();
            db()->prepare('INSERT INTO puntuaciones (equipo_id, reto_id, puntos) SELECT ?, id, 0 FROM retos')->execute([$nid]);
            bitacora('sistema', 'Equipo creado: ' . $n, null, null, $nid);
            flash('Equipo creado.');
        } elseif ($a === 'regenerar') {
            foreach (db()->query('SELECT id FROM equipos')->fetchAll() as $e)
                db()->prepare('UPDATE equipos SET pin=? WHERE id=?')->execute([pin_aleatorio(), $e['id']]);
            bitacora('sistema', 'Códigos de capitán regenerados');
            flash('Se generaron nuevos códigos para todos los equipos.');
        }
    } catch (PDOException $ex) {
        flash($ex->getCode() === '23000' ? 'Ya existe un equipo con ese nombre.' : 'Error al guardar.', 'err');
    } catch (RuntimeException $ex) { flash($ex->getMessage(), 'err'); }
    redirect('admin/equipos.php');
}

$equipos = db()->query('SELECT * FROM equipos ORDER BY id')->fetchAll();
$totalPers = array_sum(array_map(fn($e) => $e['activo'] ? (int)$e['miembros'] : 0, $equipos));
$titulo = 'Equipos'; $pagina = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= url('admin/index.php') ?>">← Panel</a></p>
<div class="page-head"><div class="eyebrow">Administración</div><h1>Equipos y capitanes</h1>
  <p>Recomendado: 10 equipos de 5 a 10 personas (50–100 participantes). Participantes actuales: <strong><?= $totalPers ?></strong>.
  Entrega a cada capitán su código de acceso.</p></div>

<form method="post">
  <?= csrf_field() ?><input type="hidden" name="accion" value="guardar">
  <div class="table-wrap"><table>
    <thead><tr><th>Equipo</th><th>Color</th><th>Capitán</th><th class="num">Integrantes</th><th class="num">Código</th><th class="num">Activo</th></tr></thead><tbody>
    <?php foreach ($equipos as $e): $i = (int)$e['id']; ?>
      <tr>
        <td><input name="eq[<?= $i ?>][nombre]" value="<?= e($e['nombre']) ?>" required maxlength="60" aria-label="Nombre"></td>
        <td><input type="color" name="eq[<?= $i ?>][color]" value="<?= e($e['color']) ?>" aria-label="Color"></td>
        <td><input name="eq[<?= $i ?>][capitan]" value="<?= e($e['capitan']) ?>" maxlength="80" placeholder="Nombre del capitán" aria-label="Capitán"></td>
        <td class="num"><input type="number" min="1" max="10" name="eq[<?= $i ?>][miembros]" value="<?= (int)$e['miembros'] ?>" style="width:80px" aria-label="Integrantes"></td>
        <td class="num"><input name="eq[<?= $i ?>][pin]" value="<?= e($e['pin']) ?>" inputmode="numeric" maxlength="8" style="width:100px;text-align:center;font-weight:800" aria-label="Código"></td>
        <td class="num"><input type="checkbox" name="eq[<?= $i ?>][activo]" <?= $e['activo'] ? 'checked' : '' ?> style="width:20px;height:20px" aria-label="Activo"></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <div class="form-inline" style="margin-top:16px"><button class="btn btn-primary">💾 Guardar cambios</button></div>
</form>

<div class="grid g2" style="margin-top:28px">
  <div class="card"><h3>➕ Agregar equipo</h3>
    <form method="post" class="form-inline"><?= csrf_field() ?><input type="hidden" name="accion" value="nuevo">
      <input name="nombre" placeholder="Nombre del equipo" required maxlength="60" style="flex:1">
      <input type="color" name="color" value="#3b82f6" aria-label="Color"><button class="btn btn-green">Agregar</button></form></div>
  <div class="card"><h3>🔑 Códigos de acceso</h3><p class="muted">Genera códigos nuevos de 4 dígitos para todos los equipos.</p>
    <form method="post" data-confirm="Esto invalida los códigos actuales. ¿Continuar?"><?= csrf_field() ?><input type="hidden" name="accion" value="regenerar">
      <button class="btn btn-gold">Regenerar códigos</button></form></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
