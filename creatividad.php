<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Creatividad Sin Límites';
$pagina = 'retos';
require __DIR__ . '/includes/header.php';
?>

<div class="section">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
      <span style="font-size: 2.5rem;">💡</span>
      <div>
        <div class="eyebrow">Reto 4 de 5</div>
        <h1 style="margin: 0; font-size: 2rem;">Creatividad Sin Límites</h1>
      </div>
    </div>

    <div class="card" style="margin-bottom: 20px; border-left: 5px solid #f59e0b;">
      <p style="font-size: 1.1rem; margin-bottom: 10px;">Propuesta de un producto innovador evaluada con una rúbrica pública.</p>
      <p class="muted" style="margin: 0;"><b>Reglas:</b> Envía la propuesta de tu producto. 100 puntos máximo.</p>
    </div>

    <div class="card">
        <h3>Formulario de Propuesta Innovadora</h3>
        <form id="form-creatividad" onsubmit="evaluarCreatividad(event)">
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <label style="display:block; margin-bottom: 8px;"><b>1. Nombre del Producto o Solución Innovadora:</b></label>
            <input type="text" id="c-nombre" class="form-control" placeholder="Ej. EcoAsistente IA" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 6px;" required>
          </div>
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <label style="display:block; margin-bottom: 8px;"><b>2. Descripción del Problema que Resuelve:</b></label>
            <textarea id="c-problema" rows="3" class="form-control" placeholder="Explique brevemente..." style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 6px;" required></textarea>
          </div>
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <label style="display:block; margin-bottom: 8px;"><b>3. Propuesta de Valor / Innovación:</b></label>
            <textarea id="c-propuesta" rows="3" class="form-control" placeholder="Detalle la originalidad..." style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 6px;" required></textarea>
          </div>
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <label style="display:block; margin-bottom: 8px;"><b>4. Viabilidad y Claridad de Implementación:</b></label>
            <select id="c-viabilidad" class="form-control" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 6px;" required>
              <option value="">Seleccione el nivel de factibilidad...</option>
              <option value="alto">Alto impacto y ejecutable (25 pts)</option>
              <option value="medio">Impacto moderado (15 pts)</option>
              <option value="bajo">Idea inicial (5 pts)</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px; padding: 14px;">Enviar propuesta a evaluación</button>
        </form>

        <div id="resultado-creatividad" style="margin-top: 25px; display: none; padding: 20px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; text-align: center;">
          <h3 style="color: #38bdf8; margin-bottom: 15px;">Evaluación de Rúbrica Pública</h3>
          <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
            <div style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; padding: 10px 20px; border-radius: 6px;">
              <span style="display:block; font-size: 0.85rem; color: #fde68a;">Puntuación de Rúbrica</span>
              <b id="crea-puntos" style="font-size: 1.5rem; color: #fbbf24;">0 / 100 pts</b>
            </div>
          </div>
          <p id="crea-mensaje" class="muted" style="margin: 0;"></p>
        </div>

        <script>
        function evaluarCreatividad(e) {
            e.preventDefault();
            const nombre = document.getElementById('c-nombre').value.trim();
            const problema = document.getElementById('c-problema').value.trim();
            const propuesta = document.getElementById('c-propuesta').value.trim();
            const viabilidad = document.getElementById('c-viabilidad').value;

            let puntaje = 25;
            if (problema.length > 15) puntaje += 25;
            if (propuesta.length > 15) puntaje += 25;
            if (viabilidad === 'alto') puntaje += 25;
            else if (viabilidad === 'medio') puntaje += 15;
            else puntaje += 5;

            let aciertos = puntaje >= 75 ? 1 : 0;
            let errores = aciertos === 0 ? 1 : 0;

            document.getElementById('crea-puntos').innerText = `${puntaje} / 100 pts`;
            document.getElementById('crea-mensaje').innerText = `¡Propuesta "${nombre}" evaluada correctamente!`;

            const resBox = document.getElementById('resultado-creatividad');
            resBox.style.display = 'block';
            resBox.scrollIntoView({ behavior: 'smooth' });

            // Guardar en Base de Datos
            const formData = new FormData();
            formData.append('reto_slug', 'creatividad-sin-limites');
            formData.append('aciertos', aciertos);
            formData.append('errores', errores);
            formData.append('puntos', puntaje);
            formData.append('equipo_id', 1);

            fetch('guardar_resultado.php', { method: 'POST', body: formData })
            .then(res => res.json()).then(data => console.log('Guardado:', data));
        }
        </script>
    </div>

    <div style="margin-top: 20px; text-align: center;">
      <a href="<?= url('juegos.php') ?>" class="btn btn-ghost">← Volver a la lista de retos</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>