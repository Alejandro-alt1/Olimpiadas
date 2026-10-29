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
function team_actual(): ?array {
    if (empty($_SESSION['equipo_id'])) return null;
    $s = db()->prepare('SELECT * FROM equipos WHERE id = ? AND activo = 1');
    $s->execute([$_SESSION['equipo_id']]);
    return $s->fetch() ?: null;
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

/** Marcador ordenado con la regla de desempate de la guía:
 *  1) total  2) puntos de Mentes Brillantes  3) puntos de Creatividad Sin Límites */
function ranking(): array {
    $sql = "SELECT e.id, e.nombre, e.color, e.miembros,
                   COALESCE(SUM(p.puntos),0) AS total,
                   COALESCE(SUM(CASE WHEN p.reto_id = 1 THEN p.puntos END),0) AS r1,
                   COALESCE(SUM(CASE WHEN p.reto_id = 4 THEN p.puntos END),0) AS r4
            FROM equipos e LEFT JOIN puntuaciones p ON p.equipo_id = e.id
            WHERE e.activo = 1
            GROUP BY e.id, e.nombre, e.color, e.miembros
            ORDER BY total DESC, r1 DESC, r4 DESC, e.nombre ASC";
    $filas = db()->query($sql)->fetchAll();
    $pos = 0; $prev = null;
    foreach ($filas as $i => &$f) {
        $clave = $f['total'] . '|' . $f['r1'] . '|' . $f['r4'];
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
/** Recalcula en el SERVIDOR los puntos de un equipo en un reto a partir de sus respuestas. */
function recalcular_puntos(int $equipo, int $reto): void {
    $pdo = db();
    if ($reto === 3) {
        $q = $pdo->prepare('SELECT COALESCE(SUM(puntos),0) FROM intentos_codigo WHERE equipo_id = ?');
    } elseif ($reto === 4) {
        $q = $pdo->prepare('SELECT COALESCE(ROUND(AVG(t)),0) FROM (
              SELECT ev.originalidad + ev.utilidad + ev.claridad AS t FROM evaluaciones ev
              JOIN propuestas p ON p.id = ev.propuesta_id WHERE p.equipo_id = ?) x');
    } else {
        $q = $pdo->prepare('SELECT COALESCE(SUM(puntos),0) FROM respuestas WHERE equipo_id = ? AND reto_id = ' . (int)$reto);
    }
    $q->execute([$equipo]);
    $max = (int)$pdo->query('SELECT puntos_max FROM retos WHERE id = ' . (int)$reto)->fetchColumn();
    $pts = min((int)$q->fetchColumn(), $max);
    $pdo->prepare('INSERT INTO puntuaciones (equipo_id, reto_id, puntos) VALUES (?,?,?)
                   ON DUPLICATE KEY UPDATE puntos = VALUES(puntos)')->execute([$equipo, $reto, $pts]);
}
