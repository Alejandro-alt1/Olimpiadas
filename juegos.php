<?php
require __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['r'] ?? '';
$reto = null;

if ($slug !== '') {
    $stmt = db()->prepare('SELECT * FROM retos WHERE slug = ?');
    $stmt->execute([$slug]);
    $reto = $stmt->fetch();
}

$titulo = $reto ? $reto['nombre'] : 'Retos';
$pagina = 'retos';
require __DIR__ . '/includes/header.php';
?>

<div class="section">
  <?php if ($reto): ?>
    <!-- Cabecera del reto actual -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
      <span style="font-size: 2.5rem;"><?= e($reto['icono']) ?></span>
      <div>
        <div class="eyebrow">Reto <?= (int)$reto['orden'] ?> de 5</div>
        <h1 style="margin: 0; font-size: 2rem;"><?= e($reto['nombre']) ?></h1>
      </div>
    </div>

    <div class="card" style="margin-bottom: 20px; border-left: 5px solid <?= e($reto['color']) ?>;">
      <p style="font-size: 1.1rem; margin-bottom: 10px;"><?= e($reto['resumen']) ?></p>
      <p class="muted" style="margin: 0;"><b>Propósito:</b> <?= e($reto['proposito']) ?></p>
    </div>

    <div class="card">
      <?php if ($reto['slug'] === 'mentes-brillantes'): ?>
        <h3>Mentes Brillantes — 8 Preguntas de Opción Múltiple</h3>
        <p class="muted">Discutan en equipo y respondan las 8 preguntas. Al finalizar, la plataforma calculará aciertos, errores y puntos acumulados.</p>
        
        <form id="form-mentes" style="margin-top: 20px;" onsubmit="validarMentesBrillantes(event)">
          <!-- Pregunta 1 -->
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Pregunta 1:</b> ¿Cuál de estas prácticas ayuda más a un equipo a tomar una decisión informada?</p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p1" value="A" required> A) Escuchar evidencia y comparar alternativas.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p1" value="B"> B) Elegir sin debatir.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p1" value="C"> C) Ignorar los riesgos.</label>
          </div>
          <!-- Pregunta 2 -->
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Pregunta 2:</b> En una lluvia de ideas, ¿cuál es el objetivo principal en la fase inicial?</p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p2" value="A" required> A) Criticar cada propuesta al instante.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p2" value="B"> B) Generar la mayor cantidad de ideas sin juzgar.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p2" value="C"> C) Elegir una sola persona para que decida todo.</label>
          </div>
          <!-- Pregunta 3 -->
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Pregunta 3:</b> Si un proyecto sufre un retraso imprevisto, ¿qué acción demuestra mejor liderazgo?</p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p3" value="A" required> A) Ocultar el error hasta el final.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p3" value="B"> B) Culpar a los compañeros de equipo.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p3" value="C"> C) Comunicar el riesgo con anticipación y proponer alternativas.</label>
          </div>
          <!-- Pregunta 4 -->
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Pregunta 4:</b> ¿Qué habilidad práctica se busca ejercitar principalmente en el Reto de Memoria Extrema?</p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p4" value="A" required> A) Concentración y memoria visual de trabajo.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p4" value="B"> B) Fuerza física y resistencia.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p4" value="C"> C) Velocidad de mecanografía.</label>
          </div>
          <!-- Pregunta 5 -->
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Pregunta 5:</b> Para resolver acertijos lógicos en equipo de manera eficaz, lo más recomendable es:</p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p5" value="A" required> A) Trabajar cada quien por su cuenta sin hablar.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p5" value="B"> B) Dividir tareas, escuchar propuestas y debatir en conjunto.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p5" value="C"> C) Adivinar al azar sin analizar pistas.</label>
          </div>
          <!-- Pregunta 6 -->
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Pregunta 6:</b> En la evaluación de Creatividad Sin Límites, ¿qué criterios se consideran además de la originalidad?</p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p6" value="A" required> A) Utilidad y claridad.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p6" value="B"> B) Color y peso del archivo.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p6" value="C"> C) Extensión en páginas.</label>
          </div>
          <!-- Pregunta 7 -->
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Pregunta 7:</b> Durante una situación de presión con tiempo límite, la prioridad del capitán es:</p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p7" value="A" required> A) Enviar múltiples respuestas contradictorias.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p7" value="B"> B) Escuchar el consenso del equipo y registrar la respuesta final a tiempo.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p7" value="C"> C) Abandonar la sesión.</label>
          </div>
          <!-- Pregunta 8 -->
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Pregunta 8:</b> ¿Cuál es el objetivo principal de las Olimpiadas Virtuales Corporativas?</p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p8" value="A" required> A) Fomentar la integración, comunicación y estrategia en equipo.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p8" value="B"> B) Competir individualmente sin importar los demás.</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="p8" value="C"> C) Evaluar exámenes teóricos escolares.</label>
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px; padding: 14px; font-size: 1rem;">Enviar y evaluar respuestas</button>
        </form>

        <div id="resultado-mentes" style="margin-top: 25px; display: none; padding: 20px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; text-align: center;">
          <h3 style="color: #38bdf8; margin-bottom: 15px;">Resultados del Reto</h3>
          <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
            <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; padding: 10px 20px; border-radius: 6px;">
              <span style="display:block; font-size: 0.85rem; color: #a7f3d0;">Aciertos</span>
              <b id="cant-aciertos" style="font-size: 1.5rem; color: #34d399;">0</b>
            </div>
            <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; padding: 10px 20px; border-radius: 6px;">
              <span style="display:block; font-size: 0.85rem; color: #fca5a5;">Errores</span>
              <b id="cant-errores" style="font-size: 1.5rem; color: #f87171;">0</b>
            </div>
            <div style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; padding: 10px 20px; border-radius: 6px;">
              <span style="display:block; font-size: 0.85rem; color: #fde68a;">Puntos Obtenidos</span>
              <b id="cant-puntos" style="font-size: 1.5rem; color: #fbbf24;">0 / 120</b>
            </div>
          </div>
        </div>

        <script>
        function validarMentesBrillantes(e) {
            e.preventDefault();
            const respuestasCorrectas = { p1: 'A', p2: 'B', p3: 'C', p4: 'A', p5: 'B', p6: 'A', p7: 'B', p8: 'A' };
            let aciertos = 0, errores = 0;
            for (let p in respuestasCorrectas) {
                const sel = document.querySelector(`input[name="${p}"]:checked`);
                if (sel && sel.value === respuestasCorrectas[p]) aciertos++;
                else errores++;
            }
            document.getElementById('cant-aciertos').innerText = aciertos;
            document.getElementById('cant-errores').innerText = errores;
            document.getElementById('cant-puntos').innerText = `${aciertos * 15} / 120 pts`;
            const box = document.getElementById('resultado-mentes');
            box.style.display = 'block';
            box.scrollIntoView({ behavior: 'smooth' });
        }
        </script>
      <?php endif; ?>
    </div>

    <div style="margin-top: 20px; text-align: center;">
      <a href="<?= url('juegos.php') ?>" class="btn btn-ghost">← Volver a la lista de retos</a>
    </div>

  <?php else: ?>
    <!-- Vista general (Lista de retos con enlaces independientes) -->
    <div class="page-head center">
      <div class="eyebrow">Actividades</div>
      <h1 style="font-size:2rem">Lista de Juegos y Retos</h1>
    </div>

    <div class="grid g3" style="margin-top: 20px;">
      <?php foreach (retos_all() as $r): ?>
        <?php 
            // Direccionar cada reto a su archivo independiente correspondiente
            if ($r['slug'] === 'memoria-extrema') {
                $enlaceReto = url('memoria.php');
            } elseif ($r['slug'] === 'codigo-secreto') {
                $enlaceReto = url('codigo.php');
            } elseif ($r['slug'] === 'creatividad-sin-limites') {
                $enlaceReto = url('creatividad.php');
            } else {
                $enlaceReto = url('reto.php?r=' . e($r['slug']));
            }
        ?>
        <a class="reto-card" style="--c:<?= e($r['color']) ?>; text-decoration: none; color: inherit; display: block;" href="<?= $enlaceReto ?>">
          <div class="reto-head">
            <span class="reto-num"><?= (int)$r['orden'] ?></span>
            <h3><?= e($r['nombre']) ?></h3>
            <span class="reto-ico"><?= e($r['icono']) ?></span>
          </div>
          <p><?= e($r['resumen']) ?></p>
          <div class="reto-meta">
            <span><?= e($r['regla_puntos']) ?></span>
            <span class="badge b-abierto">Disponible</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>