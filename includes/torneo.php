<?php
/* =====================================================================
   Torneo por enfrentamientos (sistema suizo) sobre los 5 retos.
   - Cada RONDA la decide un reto: se compara el puntaje de ambos equipos en ese reto.
   - Victoria 3 · Empate 1 · Derrota 0 (puntos de torneo). Los "puntos de pruebas" siguen aparte.
   - El facilitador puede fijar un resultado a mano antes de cerrar la ronda.
   ===================================================================== */

function torneo_activo(): bool { return config_get('torneo_activo', '0') === '1'; }
function torneo_num_rondas(): int { return max(1, min(9, (int)config_get('torneo_rondas', '5'))); }
function swiss_key(int $a, int $b): string { return min($a, $b) . '-' . max($a, $b); }

function torneo_rondas(): array {
    return db()->query('SELECT r.*, rt.nombre AS reto_nombre, rt.icono AS reto_icono, rt.estado AS reto_estado
                        FROM rondas r LEFT JOIN retos rt ON rt.id = r.reto_id ORDER BY r.numero')->fetchAll();
}
function torneo_ronda(int $id): ?array {
    $s = db()->prepare('SELECT r.*, rt.nombre AS reto_nombre, rt.estado AS reto_estado FROM rondas r LEFT JOIN retos rt ON rt.id = r.reto_id WHERE r.id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}
function torneo_partidos(int $rondaId): array {
    $s = db()->prepare('SELECT m.*, ea.nombre AS nombre_a, ea.color AS color_a, eb.nombre AS nombre_b, eb.color AS color_b
                        FROM enfrentamientos m JOIN equipos ea ON ea.id = m.equipo_a JOIN equipos eb ON eb.id = m.equipo_b
                        WHERE m.ronda_id = ? ORDER BY m.id');
    $s->execute([$rondaId]);
    return $s->fetchAll();
}
/** Puntos de un reto por equipo: [equipo_id => puntos]. */
function torneo_puntos_reto(?int $retoId): array {
    if (!$retoId) return [];
    $s = db()->prepare('SELECT equipo_id, puntos FROM puntuaciones WHERE reto_id = ?');
    $s->execute([$retoId]);
    $o = [];
    foreach ($s as $r) $o[(int)$r['equipo_id']] = (int)$r['puntos'];
    return $o;
}
function torneo_resultado_auto(int $pa, int $pb): string { return $pa > $pb ? 'a' : ($pb > $pa ? 'b' : 'empate'); }

/** Estadísticas por equipo: tp (puntos de torneo), pj, g, e, p. Solo cuentan los enfrentamientos ya jugados. */
function torneo_stats(): array {
    $out = [];
    foreach (db()->query('SELECT id FROM equipos')->fetchAll() as $e) $out[(int)$e['id']] = ['tp' => 0, 'pj' => 0, 'g' => 0, 'e' => 0, 'p' => 0];
    foreach (db()->query("SELECT equipo_a, equipo_b, ganador_id, empate FROM enfrentamientos WHERE estado = 'jugado'") as $m) {
        $a = (int)$m['equipo_a']; $b = (int)$m['equipo_b'];
        if (!isset($out[$a], $out[$b])) continue;
        $out[$a]['pj']++; $out[$b]['pj']++;
        if ((int)$m['empate'] === 1) {
            $out[$a]['e']++; $out[$b]['e']++;
            $out[$a]['tp'] += TORNEO_PTS_EMPATE; $out[$b]['tp'] += TORNEO_PTS_EMPATE;
        } else {
            $w = (int)$m['ganador_id']; $l = $w === $a ? $b : $a;
            $out[$w]['g']++; $out[$w]['tp'] += TORNEO_PTS_VICTORIA;
            $out[$l]['p']++; $out[$l]['tp'] += TORNEO_PTS_DERROTA;
        }
    }
    return $out;
}
/** Victorias directas entre dos equipos (desempate): >0 si gana más veces $a, <0 si gana $b. */
function torneo_h2h(int $a, int $b, array $hist): int {
    $d = 0;
    foreach ($hist as [$w, $l]) { if ($w === $a && $l === $b) $d++; elseif ($w === $b && $l === $a) $d--; }
    return $d;
}
function torneo_historial_victorias(): array {
    $h = [];
    foreach (db()->query("SELECT equipo_a, equipo_b, ganador_id FROM enfrentamientos WHERE estado = 'jugado' AND empate = 0 AND ganador_id IS NOT NULL") as $m) {
        $w = (int)$m['ganador_id']; $h[] = [$w, $w === (int)$m['equipo_a'] ? (int)$m['equipo_b'] : (int)$m['equipo_a']];
    }
    return $h;
}
function torneo_jugados(): array {
    $set = [];
    foreach (db()->query('SELECT equipo_a, equipo_b FROM enfrentamientos') as $m) $set[swiss_key((int)$m['equipo_a'], (int)$m['equipo_b'])] = true;
    return $set;
}

/* ---------------- Emparejamiento suizo ---------------- */

/** Método "similares" (suizo clásico): cada equipo con el rival de clasificación más cercana que aún no haya
 *  enfrentado. Retrocede (backtracking) si algún equipo se queda sin rival válido. */
function swiss_similares(array $orden, array $jug): ?array {
    if (!$orden) return [];
    $a = array_shift($orden);
    foreach ($orden as $k => $b) {
        if (isset($jug[swiss_key($a, $b)])) continue;
        $resto = $orden; unset($resto[$k]);
        $r = swiss_similares(array_values($resto), $jug);
        if ($r !== null) return array_merge([[$a, $b]], $r);
    }
    return null;
}
/** Método "mitades" (el de la documentación): mitad superior contra mitad inferior, priorizando la menor
 *  diferencia de puntos de torneo y evitando repeticiones. */
function swiss_mitades(array $up, array $low, array $tp, array $jug, int $i = 0, array $usados = [], array $pares = []): ?array {
    if ($i === count($up)) return $pares;
    $a = $up[$i];
    $cands = array_values(array_filter($low, fn($b) => !isset($usados[$b])));
    usort($cands, fn($x, $y) => abs(($tp[$a] ?? 0) - ($tp[$x] ?? 0)) <=> abs(($tp[$a] ?? 0) - ($tp[$y] ?? 0)));   // estable: respeta la tabla en empates
    foreach ($cands as $b) {
        if (isset($jug[swiss_key($a, $b)])) continue;
        $u2 = $usados; $u2[$b] = true;
        $r = swiss_mitades($up, $low, $tp, $jug, $i + 1, $u2, array_merge($pares, [[$a, $b]]));
        if ($r !== null) return $r;
    }
    return null;
}
function swiss_emparejar(array $orden, array $tp, array $jug, string $metodo, bool $permitir): ?array {
    $half = intdiv(count($orden), 2);
    $mit = fn($j) => swiss_mitades(array_slice($orden, 0, $half), array_slice($orden, $half), $tp, $j);
    $sim = fn($j) => swiss_similares($orden, $j);
    $r = $metodo === 'mitades' ? ($mit($jug) ?? $sim($jug)) : ($sim($jug) ?? $mit($jug));
    if ($r === null && $permitir) $r = $metodo === 'mitades' ? $mit([]) : $sim([]);   // ignora el historial
    return $r;
}

/* ---------------- Acciones del facilitador (devuelven un mensaje de error o null) ---------------- */

function torneo_generar_ronda(?int $retoId, string $metodo, bool $permitir): ?string {
    if (!torneo_activo()) return 'Activa primero el torneo.';
    $rondas = torneo_rondas();
    if (count($rondas) >= torneo_num_rondas()) return 'Ya se generaron todas las rondas configuradas.';
    foreach ($rondas as $r) if ($r['estado'] !== 'cerrada') return 'Cierra la ronda en curso antes de generar la siguiente.';
    $ids = array_map('intval', db()->query('SELECT id FROM equipos WHERE activo = 1 ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
    if (count($ids) < 2 || count($ids) % 2) return 'El torneo necesita un número par de equipos activos (ahora hay ' . count($ids) . ').';
    if ($retoId) {
        $s = db()->prepare('SELECT COUNT(*) FROM retos WHERE id = ?'); $s->execute([$retoId]);
        if (!(int)$s->fetchColumn()) return 'El reto elegido no existe.';
        $s = db()->prepare('SELECT COUNT(*) FROM rondas WHERE reto_id = ?'); $s->execute([$retoId]);
        if ((int)$s->fetchColumn() > 0) return 'Ese reto ya decidió otra ronda.';
    }
    $numero = count($rondas) + 1;
    if ($numero === 1) {
        if (config_get('torneo_primera', 'predefinido') === 'sorteo') {
            shuffle($ids);
            $pares = []; for ($i = 0; $i < count($ids); $i += 2) $pares[] = [$ids[$i], $ids[$i + 1]];   // sorteo
        } else {
            $half = intdiv(count($ids), 2);                                                              // 1-6, 2-7, 3-8 …
            $pares = swiss_mitades(array_slice($ids, 0, $half), array_slice($ids, $half), array_fill_keys($ids, 0), []);
        }
    } else {
        $orden = array_map('intval', array_column(ranking(), 'id'));
        $st = torneo_stats();
        $tp = []; foreach ($orden as $id) $tp[$id] = $st[$id]['tp'] ?? 0;
        $pares = swiss_emparejar($orden, $tp, torneo_jugados(), $metodo === 'mitades' ? 'mitades' : 'similares', $permitir);
    }
    if ($pares === null) return 'No existe una combinación sin repetir rivales. Marca “permitir repetir rivales” si quieres continuar.';

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO rondas (numero, reto_id, estado) VALUES (?,?,?)')->execute([$numero, $retoId ?: null, 'en_curso']);
        $rid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO enfrentamientos (ronda_id, equipo_a, equipo_b) VALUES (?,?,?)');
        foreach ($pares as [$a, $b]) $ins->execute([$rid, $a, $b]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); return 'No se pudo generar la ronda.'; }
    bitacora('sistema', "Torneo: ronda $numero generada", $retoId ? "Decide el reto $retoId" : 'Resultado manual', $retoId ?: null);
    return null;
}

/** Ajustes manuales antes de cerrar. $datos: [partido_id => ['res'=>auto|a|b|empate, 'nota'=>texto]] */
function torneo_guardar_manual(int $rondaId, array $datos): ?string {
    $r = torneo_ronda($rondaId);
    if (!$r || $r['estado'] !== 'en_curso') return 'Solo se puede ajustar una ronda en curso.';
    foreach (torneo_partidos($rondaId) as $m) {
        $d = $datos[$m['id']] ?? null;
        if (!$d) continue;
        $res = $d['res'] ?? 'auto';
        $nota = mb_substr(trim($d['nota'] ?? ''), 0, 255) ?: null;
        if ($res === 'auto') {
            db()->prepare('UPDATE enfrentamientos SET manual = 0, ganador_id = NULL, empate = 0, nota = ? WHERE id = ?')->execute([$nota, $m['id']]);
        } else {
            $gan = $res === 'a' ? (int)$m['equipo_a'] : ($res === 'b' ? (int)$m['equipo_b'] : null);
            db()->prepare('UPDATE enfrentamientos SET manual = 1, ganador_id = ?, empate = ?, nota = ? WHERE id = ?')
                ->execute([$gan, $res === 'empate' ? 1 : 0, $nota, $m['id']]);
        }
    }
    bitacora('puntos', "Torneo: ajustes manuales guardados en la ronda {$r['numero']}");
    return null;
}

function torneo_cerrar_ronda(int $rondaId): ?string {
    $r = torneo_ronda($rondaId);
    if (!$r || $r['estado'] !== 'en_curso') return 'La ronda no está en curso.';
    if ($r['reto_id'] && $r['reto_estado'] !== 'cerrado')
        return 'Cierra primero el reto “' . $r['reto_nombre'] . '” para que sus puntos estén completos.';
    $partidos = torneo_partidos($rondaId);
    if (!$r['reto_id']) foreach ($partidos as $m) if (!$m['manual']) return 'Esta ronda no tiene reto: fija a mano el resultado de cada enfrentamiento.';
    $pts = torneo_puntos_reto($r['reto_id'] ? (int)$r['reto_id'] : null);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $up = $pdo->prepare("UPDATE enfrentamientos SET puntos_a = ?, puntos_b = ?, ganador_id = ?, empate = ?, estado = 'jugado' WHERE id = ?");
        foreach ($partidos as $m) {
            $pa = $pts[(int)$m['equipo_a']] ?? 0; $pb = $pts[(int)$m['equipo_b']] ?? 0;
            if ($m['manual']) { $gan = $m['ganador_id'] ? (int)$m['ganador_id'] : null; $emp = (int)$m['empate']; }
            else { $x = torneo_resultado_auto($pa, $pb); $gan = $x === 'a' ? (int)$m['equipo_a'] : ($x === 'b' ? (int)$m['equipo_b'] : null); $emp = $x === 'empate' ? 1 : 0; }
            $up->execute([$r['reto_id'] ? $pa : null, $r['reto_id'] ? $pb : null, $gan, $emp, $m['id']]);
        }
        $pdo->prepare("UPDATE rondas SET estado = 'cerrada', cerrada_at = NOW() WHERE id = ?")->execute([$rondaId]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); return 'No se pudo cerrar la ronda.'; }
    bitacora('sistema', "Torneo: ronda {$r['numero']} cerrada y puntos de torneo asignados", null, $r['reto_id'] ? (int)$r['reto_id'] : null);
    return null;
}

function torneo_reabrir_ronda(int $rondaId): ?string {
    $r = torneo_ronda($rondaId);
    if (!$r || $r['estado'] !== 'cerrada') return 'La ronda no está cerrada.';
    $ult = (int)db()->query('SELECT MAX(numero) FROM rondas')->fetchColumn();
    if ((int)$r['numero'] !== $ult) return 'Solo se puede reabrir la última ronda.';
    $pdo = db();
    $pdo->prepare("UPDATE enfrentamientos SET estado = 'pendiente', puntos_a = NULL, puntos_b = NULL,
                   ganador_id = IF(manual = 1, ganador_id, NULL), empate = IF(manual = 1, empate, 0) WHERE ronda_id = ?")->execute([$rondaId]);
    $pdo->prepare("UPDATE rondas SET estado = 'en_curso', cerrada_at = NULL WHERE id = ?")->execute([$rondaId]);
    bitacora('sistema', "Torneo: ronda {$r['numero']} reabierta");
    return null;
}
function torneo_eliminar_ronda(int $rondaId): ?string {
    $r = torneo_ronda($rondaId);
    if (!$r || $r['estado'] !== 'en_curso') return 'Solo se puede eliminar una ronda en curso (reábrela si ya estaba cerrada).';
    db()->prepare('DELETE FROM rondas WHERE id = ?')->execute([$rondaId]);
    bitacora('sistema', "Torneo: ronda {$r['numero']} eliminada para volver a generarla");
    return null;
}
function torneo_reiniciar(): void {
    db()->exec('DELETE FROM enfrentamientos');
    db()->exec('DELETE FROM rondas');
    bitacora('sistema', 'Torneo reiniciado: se borraron todas las rondas y enfrentamientos');
}

/* ---------------- Consultas para las vistas ---------------- */

/** Último enfrentamiento de un equipo (o el de un reto concreto) con datos del rival y marcador. */
function torneo_partido_equipo(int $eq, ?int $retoId = null): ?array {
    $sql = 'SELECT m.*, r.numero, r.reto_id, r.estado AS ronda_estado FROM enfrentamientos m JOIN rondas r ON r.id = m.ronda_id
            WHERE (m.equipo_a = ? OR m.equipo_b = ?)' . ($retoId ? ' AND r.reto_id = ?' : '') . ' ORDER BY r.numero DESC LIMIT 1';
    $s = db()->prepare($sql);
    $s->execute($retoId ? [$eq, $eq, $retoId] : [$eq, $eq]);
    $m = $s->fetch();
    if (!$m) return null;
    $soyA = (int)$m['equipo_a'] === $eq;
    $riv = (int)($soyA ? $m['equipo_b'] : $m['equipo_a']);
    $q = db()->prepare('SELECT nombre, color FROM equipos WHERE id = ?'); $q->execute([$riv]);
    $e = $q->fetch() ?: ['nombre' => '—', 'color' => '#888'];
    if ($m['estado'] === 'jugado' || !$m['reto_id']) { $mi = $soyA ? $m['puntos_a'] : $m['puntos_b']; $su = $soyA ? $m['puntos_b'] : $m['puntos_a']; }
    else { $live = torneo_puntos_reto((int)$m['reto_id']); $mi = $live[$eq] ?? 0; $su = $live[$riv] ?? 0; }
    $m['rival'] = $e['nombre']; $m['rival_color'] = $e['color']; $m['mi_pts'] = $mi; $m['su_pts'] = $su; $m['resultado'] = null;
    if ($m['estado'] === 'jugado') $m['resultado'] = (int)$m['empate'] === 1 ? 'empate' : ((int)$m['ganador_id'] === $eq ? 'victoria' : 'derrota');
    return $m;
}
