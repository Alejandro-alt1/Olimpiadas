<?php
/* ---------- Utilidades básicas ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path) { header('Location: ' . url($path)); exit; }
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

function flash(?string $msg = null, string $tipo = 'ok') {
    if ($msg !== null) { $_SESSION['flash'][] = ['m' => $msg, 't' => $tipo]; return null; }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!is_post()) return;
    $t = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('Sesión expirada. Vuelve atrás y recarga la página.');
    }
}

/* ---------- Configuración del evento ---------- */
function config_get(string $clave, string $default = ''): string {
    $s = db()->prepare('SELECT valor FROM configuracion WHERE clave = ?');
    $s->execute([$clave]);
    $v = $s->fetchColumn();
    return $v === false ? $default : $v;
}
function config_set(string $clave, string $valor): void {
    db()->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE valor = VALUES(valor)')->execute([$clave, $valor]);
}

/* ---------- Autenticación ---------- */
function admin_actual(): ?array { return $_SESSION['admin'] ?? null; }
function participante_actual(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    if (empty($_SESSION['participante_id']) || empty($_SESSION['participante_token'])) return null;
    $s = db()->prepare('SELECT p.*, e.nombre AS equipo, e.color, e.miembros FROM participantes p
                        JOIN equipos e ON e.id = p.equipo_id WHERE p.id = ? AND e.activo = 1');
    $s->execute([$_SESSION['participante_id']]);
    $p = $s->fetch();
    if ($p && hash_equals($p['token'], (string)$_SESSION['participante_token'])) $cache = $p;
    return $cache;
}
/** Equipo de la persona que ha entrado (ya no existe un "capitán"). */
function team_actual(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    $p = participante_actual();
    if (!$p) return null;
    $s = db()->prepare('SELECT * FROM equipos WHERE id = ? AND activo = 1');
    $s->execute([$p['equipo_id']]);
    return $cache = ($s->fetch() ?: null);
}
function require_admin(): void {
    if (!admin_actual()) { flash('Acceso restringido al facilitador.', 'err'); redirect('login.php?rol=facilitador'); }
}
function require_team(): array {
    $t = team_actual();
    if (!$t) { flash('Ingresa con tu equipo para continuar.', 'err'); redirect('login.php'); }
    return $t;
}
function login_bloqueado(): int {
    $hasta = $_SESSION['login_bloqueo'] ?? 0;
    return $hasta > time() ? $hasta - time() : 0;
}
function login_fallo(): void {
    $_SESSION['login_fallos'] = ($_SESSION['login_fallos'] ?? 0) + 1;
    if ($_SESSION['login_fallos'] >= MAX_INTENTOS_LOGIN) {
        $_SESSION['login_bloqueo'] = time() + BLOQUEO_SEGUNDOS;
        $_SESSION['login_fallos'] = 0;
    }
}

/* ---------- Bitácora ---------- */
function bitacora(string $tipo, string $desc, ?string $decision = null, ?int $reto = null, ?int $equipo = null): void {
    $quien = admin_actual()['nombre'] ?? (team_actual()['nombre'] ?? 'Sistema');
    db()->prepare('INSERT INTO bitacora (tipo, reto_id, equipo_id, descripcion, decision, autorizado_por)
                   VALUES (?,?,?,?,?,?)')->execute([$tipo, $reto, $equipo, $desc, $decision, $quien]);
}

/* ---------- Datos del juego ---------- */
function retos_all(): array {
    return db()->query('SELECT *, IF(inicio_at IS NULL, NULL, TIMESTAMPDIFF(SECOND, inicio_at, NOW())) AS transcurrido
                        FROM retos ORDER BY orden')->fetchAll();
}
function reto_por_slug(string $slug): ?array {
    $s = db()->prepare('SELECT *, IF(inicio_at IS NULL, NULL, TIMESTAMPDIFF(SECOND, inicio_at, NOW())) AS transcurrido
                        FROM retos WHERE slug = ?');
    $s->execute([$slug]);
    return $s->fetch() ?: null;
}
function reto_restante(array $r): ?int {
    if ($r['estado'] !== 'abierto' || $r['transcurrido'] === null) return null;
    return max(0, $r['duracion_min'] * 60 - (int)$r['transcurrido']);
}
function puntos_max_total(): int { return (int)db()->query('SELECT SUM(puntos_max) FROM retos')->fetchColumn(); }

/** Marcador ordenado.
 *  Sin torneo: 1) total de pruebas  2) puntos de Mentes Brillantes  3) puntos de Creatividad.
 *  Con torneo: 1) puntos de torneo  2) puntos de pruebas  3) victorias  4) enfrentamiento directo (si el empate es entre dos)
 *              5) Mentes Brillantes  6) Creatividad. */
function ranking(): array {
    $filas = db()->query("SELECT e.id, e.nombre, e.color, e.miembros,
                   COALESCE(SUM(p.puntos),0) AS total,
                   COALESCE(SUM(CASE WHEN p.reto_id = 1 THEN p.puntos END),0) AS r1,
                   COALESCE(SUM(CASE WHEN p.reto_id = 4 THEN p.puntos END),0) AS r4
            FROM equipos e LEFT JOIN puntuaciones p ON p.equipo_id = e.id
            WHERE e.activo = 1
            GROUP BY e.id, e.nombre, e.color, e.miembros")->fetchAll();
    $torneo = torneo_activo();
    $st = $torneo ? torneo_stats() : [];
    foreach ($filas as &$f) {
        $s = $st[(int)$f['id']] ?? ['tp' => 0, 'pj' => 0, 'g' => 0, 'e' => 0, 'p' => 0];
        foreach (['total', 'r1', 'r4'] as $k) $f[$k] = (int)$f[$k];
        foreach (['tp', 'pj', 'g', 'e', 'p'] as $k) $f[$k] = (int)$s[$k];
        $f['id'] = (int)$f['id']; $f['h2h'] = 0;
    }
    unset($f);
    usort($filas, function ($a, $b) use ($torneo) {
        foreach ($torneo ? ['tp', 'total', 'g', 'r1', 'r4'] : ['total', 'r1', 'r4'] as $k) if ($a[$k] !== $b[$k]) return $b[$k] <=> $a[$k];
        return $a['id'] <=> $b['id'];
    });
    if ($torneo && count($filas) > 1) {   // enfrentamiento directo: solo si el empate es entre exactamente dos equipos
        $hist = torneo_historial_victorias();
        $n = count($filas); $i = 0;
        while ($i < $n) {
            $j = $i;
            while ($j + 1 < $n && $filas[$j + 1]['tp'] === $filas[$i]['tp'] && $filas[$j + 1]['total'] === $filas[$i]['total'] && $filas[$j + 1]['g'] === $filas[$i]['g']) $j++;
            if ($j - $i === 1) {
                $d = torneo_h2h($filas[$i]['id'], $filas[$j]['id'], $hist);
                if ($d !== 0) {
                    if ($d < 0) { $t = $filas[$i]; $filas[$i] = $filas[$j]; $filas[$j] = $t; }
                    $filas[$i]['h2h'] = 1; $filas[$j]['h2h'] = 0;
                }
            }
            $i = $j + 1;
        }
    }
    $pos = 0; $prev = null;
    foreach ($filas as $i => &$f) {
        $clave = ($torneo ? $f['tp'] . '|' : '') . $f['total'] . '|' . ($torneo ? $f['g'] . '|' . $f['h2h'] . '|' : '') . $f['r1'] . '|' . $f['r4'];
        if ($clave !== $prev) $pos = $i + 1;
        $f['pos'] = $pos;
        $f['empate'] = ($clave === $prev);
        $prev = $clave;
    }
    unset($f);
    for ($i = 0; $i < count($filas) - 1; $i++) if ($filas[$i + 1]['empate']) $filas[$i]['empate'] = true;
    return $filas;
}
/** Matriz [equipo_id][reto_id] => puntos */
function matriz_puntos(): array {
    $m = [];
    foreach (db()->query('SELECT equipo_id, reto_id, puntos FROM puntuaciones') as $r)
        $m[$r['equipo_id']][$r['reto_id']] = (int)$r['puntos'];
    return $m;
}

function agenda(): array {
    return [
        ['00–05', 'Bienvenida, objetivo, equipos y reglas', 5],
        ['05–14', 'Reto 1 · Mentes Brillantes', 9],
        ['14–22', 'Reto 2 · Memoria Extrema', 8],
        ['22–31', 'Reto 3 · Código Secreto', 9],
        ['31–42', 'Reto 4 · Creatividad Sin Límites', 11],
        ['42–51', 'Reto 5 · Desafío Relámpago', 9],
        ['51–56', 'Revisión del marcador y desempate si aplica', 5],
        ['56–60', 'Reconocimiento y reflexión final', 4],
    ];
}
function estado_texto(string $e): string {
    return ['pendiente' => 'Pendiente', 'abierto' => 'En curso', 'cerrado' => 'Finalizado'][$e] ?? $e;
}
function medalla(int $pos): string { return [1 => '🥇', 2 => '🥈', 3 => '🥉'][$pos] ?? (string)$pos; }

/* ================= FASE 2: utilidades de los retos ================= */
/** Un reto acepta respuestas solo si está en curso y no se agotó su tiempo total. */
function reto_activo(array $r): bool {
    if ($r['estado'] !== 'abierto') return false;
    $rest = reto_restante($r);
    return $rest !== null && $rest > 0;
}
/** Normaliza texto para comparar acertijos: sin mayúsculas, acentos, signos ni artículos iniciales. */
function norm_texto(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
    $s = preg_replace('/[^a-z0-9\s]/', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = preg_replace('/^(el|la|los|las|un|una)\s+/', '', $s);
    return trim($s);
}
/* ---- Utilidades de sesión / respuestas JSON ---- */
function json_out(array $d, int $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}
/** Latido de conexión (solo escribe si pasaron 8 s, para no saturar la BD). */
function participante_tocar(array $p): void {
    db()->prepare('UPDATE participantes SET last_seen = NOW() WHERE id = ? AND last_seen < NOW() - INTERVAL 8 SECOND')->execute([$p['id']]);
}

/* ---- Puntuación del equipo = PROMEDIO de los puntajes individuales ---- */
/** Entre cuántas personas se promedia. 'declarados': el mayor entre integrantes declarados y registrados.
 *  'registrados': solo quienes entraron. Las personas ausentes cuentan con 0 en el primer modo. */
function base_equipo(int $equipo): int {
    $s = db()->prepare('SELECT miembros, (SELECT COUNT(*) FROM participantes WHERE equipo_id = equipos.id) AS reg FROM equipos WHERE id = ?');
    $s->execute([$equipo]);
    $e = $s->fetch();
    if (!$e) return 1;
    $reg = (int)$e['reg']; $dec = (int)$e['miembros'];
    $n = config_get('promedio_base', 'declarados') === 'registrados' ? $reg : max($dec, $reg);
    return max(1, $n);
}
/** Recalcula en el SERVIDOR el puntaje del equipo en un reto. Retos 1, 2, 3 y 5: suma de puntos individuales ÷ base.
 *  Reto 4 (Creatividad): promedio de los evaluadores sobre la propuesta del equipo. */
function recalcular_puntos(int $equipo, int $reto): void {
    $pdo = db();
    $max = (int)$pdo->query('SELECT puntos_max FROM retos WHERE id = ' . (int)$reto)->fetchColumn();
    if ($reto === 4) {
        $q = $pdo->prepare('SELECT COALESCE(ROUND(AVG(t)),0) FROM (
              SELECT ev.originalidad + ev.utilidad + ev.claridad AS t FROM evaluaciones ev
              JOIN propuestas p ON p.id = ev.propuesta_id WHERE p.equipo_id = ?) x');
        $q->execute([$equipo]);
        $pts = min((int)$q->fetchColumn(), $max);
        $pdo->prepare('INSERT INTO puntuaciones (equipo_id, reto_id, puntos) VALUES (?,?,?) ON DUPLICATE KEY UPDATE puntos = VALUES(puntos)')
            ->execute([$equipo, 4, $pts]);
        return;
    }
    $base = base_equipo($equipo);
    $suma = $reto === 3
        ? 'SELECT COALESCE(SUM(puntos),0) AS s FROM intentos_codigo WHERE equipo_id = ?'
        : 'SELECT COALESCE(SUM(puntos),0) AS s FROM respuestas WHERE equipo_id = ? AND reto_id = ' . (int)$reto;
    // Una sola sentencia: lee la suma y escribe el promedio (evita que dos envíos simultáneos dejen un valor viejo)
    $pdo->prepare("INSERT INTO puntuaciones (equipo_id, reto_id, puntos)
                   SELECT ?, ?, LEAST(?, ROUND(t.s / ?)) FROM ($suma) t
                   ON DUPLICATE KEY UPDATE puntos = VALUES(puntos)")
        ->execute([$equipo, $reto, $max, $base, $equipo]);
}
function recalcular_equipo(int $equipo): void { foreach ([1, 2, 3, 4, 5] as $r) recalcular_puntos($equipo, $r); }
function recalcular_todos(): void {
    foreach (db()->query('SELECT id FROM equipos')->fetchAll() as $e) recalcular_equipo((int)$e['id']);
}

/** Puntaje individual de una persona por reto (reto 4 devuelve el del equipo, que es compartido). */
function puntos_individuales_todos(int $participante, int $equipo): array {
    $out = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $s = db()->prepare('SELECT reto_id, COALESCE(SUM(puntos),0) p FROM respuestas WHERE participante_id = ? GROUP BY reto_id');
    $s->execute([$participante]);
    foreach ($s as $r) $out[(int)$r['reto_id']] = (int)$r['p'];
    $s = db()->prepare('SELECT COALESCE(SUM(puntos),0) FROM intentos_codigo WHERE participante_id = ?');
    $s->execute([$participante]);
    $out[3] = (int)$s->fetchColumn();
    $s = db()->prepare('SELECT puntos FROM puntuaciones WHERE equipo_id = ? AND reto_id = 4');
    $s->execute([$equipo]);
    $out[4] = (int)$s->fetchColumn();
    return $out;
}
