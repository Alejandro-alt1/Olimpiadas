<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Reglas'; $pagina = 'reglas';
$retos = retos_all();
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="eyebrow">Guía de facilitación</div><h1>Reglas, roles y puntuación</h1>
  <p>Reglas transparentes y resultados verificables para que todos los equipos compitan en igualdad de condiciones.</p></div>

<div class="grid g2">
  <div class="card"><h3>🎙 Facilitador</h3><ul class="check">
    <li>Da la bienvenida, explica el objetivo y presenta las reglas.</li>
    <li>Controla el reloj y anuncia el inicio y cierre de cada reto.</li>
    <li>Verifica que las respuestas se registren y revisa incidencias.</li>
    <li>Mantiene un tono neutral: mismas reglas para todos los equipos.</li>
    <li>Presenta el marcador final y dirige la reflexión.</li></ul></div>
  <div class="card"><h3>🧭 Capitán de equipo</h3><ul class="check">
    <li>Confirma que todos puedan participar y escucha sus propuestas.</li>
    <li>Organiza la discusión y registra la respuesta final.</li>
    <li>Envía la respuesta antes del límite de tiempo.</li>
    <li>Evita respuestas contradictorias: pide consenso antes de confirmar.</li></ul></div>
  <div class="card"><h3>🙌 Participantes</h3><ul class="check">
    <li>Aportan ideas, observan detalles y comparten información con respeto.</li>
    <li>Respetan tiempos, reglas y decisiones del facilitador.</li>
    <li>No consultan respuestas externas si el evento lo prohíbe.</li></ul></div>
  <div class="card"><h3>⚖️ Transparencia</h3><ul class="check">
    <li>Los valores de puntos se publican antes de comenzar.</li>
    <li>La rúbrica no cambia una vez enviadas las respuestas.</li>
    <li>Las respuestas ambiguas se resuelven con un criterio documentado y aplicado por igual.</li>
    <li>Ante un error técnico se usa el método de respaldo y se registra la incidencia.</li></ul></div>
</div>

<section class="section"><h2>Puntuación por reto</h2>
<div class="table-wrap"><table>
  <thead><tr><th>Reto</th><th>Regla</th><th class="num">Máximo</th></tr></thead><tbody>
  <?php foreach ($retos as $r): ?>
    <tr><td><span class="team"><?= $r['icono'] ?> <?= e($r['nombre']) ?></span></td><td><?= e($r['regla_puntos']) ?></td><td class="num total"><?= (int)$r['puntos_max'] ?></td></tr>
  <?php endforeach; ?>
  <tr><td colspan="2"><strong>Total</strong></td><td class="num total"><?= puntos_max_total() ?></td></tr>
  </tbody></table></div></section>

<div class="grid g2">
  <section><h2>Agenda sugerida (60 min)</h2>
  <div class="table-wrap"><table class="agenda"><thead><tr><th>Tiempo</th><th>Actividad</th><th class="num">Min</th></tr></thead><tbody>
    <?php foreach (agenda() as $a): ?><tr><td><?= e($a[0]) ?></td><td><?= e($a[1]) ?></td><td class="num"><?= (int)$a[2] ?></td></tr><?php endforeach; ?>
  </tbody></table></div></section>
  <section><h2>Rúbrica de Creatividad</h2>
  <div class="table-wrap"><table style="min-width:0"><thead><tr><th>Criterio</th><th class="num">Pts</th><th>Qué se evalúa</th></tr></thead><tbody>
    <tr><td>Originalidad</td><td class="num">40</td><td>Enfoque novedoso o combinación distinta.</td></tr>
    <tr><td>Utilidad</td><td class="num">35</td><td>Resuelve un problema real con beneficio comprensible.</td></tr>
    <tr><td>Claridad</td><td class="num">25</td><td>Propuesta organizada y fácil de explicar.</td></tr>
  </tbody></table></div>
  <div class="card" style="margin-top:18px"><h3>🥇 Desempate</h3><ol class="muted" style="margin:0;padding-left:20px">
    <li>Mayor puntaje en Mentes Brillantes.</li><li>Mayor puntaje en Creatividad Sin Límites.</li>
    <li>Pregunta final igual para los equipos empatados, con tiempo y regla anunciados.</li></ol></div></section>
</div>

<section class="section"><div class="card"><h3>♿ Accesibilidad y convivencia</h3><ul class="check">
  <li>Las instrucciones se leen en voz alta y se mantienen visibles.</li>
  <li>No se depende solo del color para comunicar aciertos, errores o estados.</li>
  <li>Se ofrece una alternativa verbal o textual si una persona no puede usar una función visual o auditiva.</li>
  <li>Competencia respetuosa: no se ridiculizan errores ni se publica información personal.</li></ul></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
