<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Código Secreto';
$pagina = 'retos';
require __DIR__ . '/includes/header.php';
?>

<div class="section">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
      <span style="font-size: 2.5rem;">🔐</span>
      <div>
        <div class="eyebrow">Reto 3 de 5</div>
        <h1 style="margin: 0; font-size: 2rem;">Código Secreto</h1>
      </div>
    </div>

    <div class="card" style="margin-bottom: 20px; border-left: 5px solid #a855f7;">
      <p style="font-size: 1.1rem; margin-bottom: 10px;">Tres acertijos que abren cerraduras sucesivas hasta el código final.</p>
      <p class="muted" style="margin: 0;"><b>Reglas:</b> 30 puntos por acertijo sin pista; 20 si usa pista; 0 si no se resuelve. Máximo 90.</p>
    </div>

    <div class="card">
        <form id="form-codigo" onsubmit="validarCodigoSecreto(event)">
          <div class="card" style="margin-bottom: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Cerradura 1:</b> "Tengo cuello pero no cabeza, y llevo puesta una gorra." ¿Qué objeto soy?</p>
            <input type="text" id="resp1" class="form-control" placeholder="Escribe tu respuesta..." style="margin: 10px 0; padding: 10px; width: 100%; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 6px;" required>
            <button type="button" class="btn btn-ghost" onclick="mostrarPista(1)" style="font-size: 0.85rem; padding: 5px 10px;">💡 Ver pista (-10 pts)</button>
            <div id="pista-1" style="display: none; margin-top: 8px; color: #fde047; font-size: 0.9rem;"><b>Pista:</b> Una botella.</div>
          </div>

          <div class="card" style="margin-bottom: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Cerradura 2:</b> "Cuanto más le quitas, más grande se vuelve." ¿Qué es?</p>
            <input type="text" id="resp2" class="form-control" placeholder="Escribe tu respuesta..." style="margin: 10px 0; padding: 10px; width: 100%; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 6px;" required>
            <button type="button" class="btn btn-ghost" onclick="mostrarPista(2)" style="font-size: 0.85rem; padding: 5px 10px;">💡 Ver pista (-10 pts)</button>
            <div id="pista-2" style="display: none; margin-top: 8px; color: #fde047; font-size: 0.9rem;"><b>Pista:</b> Un hoyo / agujero.</div>
          </div>

          <div class="card" style="margin-bottom: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>Cerradura 3:</b> "Tiene 4 dedos y un pulgar, pero no tiene carne ni huesos." ¿Qué es?</p>
            <input type="text" id="resp3" class="form-control" placeholder="Escribe tu respuesta..." style="margin: 10px 0; padding: 10px; width: 100%; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 6px;" required>
            <button type="button" class="btn btn-ghost" onclick="mostrarPista(3)" style="font-size: 0.85rem; padding: 5px 10px;">💡 Ver pista (-10 pts)</button>
            <div id="pista-3" style="display: none; margin-top: 8px; color: #fde047; font-size: 0.9rem;"><b>Pista:</b> Un guante.</div>
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px; padding: 14px;">Abrir cerraduras y calcular puntos</button>
        </form>

        <div id="resultado-codigo" style="margin-top: 25px; display: none; padding: 20px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; text-align: center;">
          <h3 style="color: #38bdf8; margin-bottom: 15px;">Resultado de Código Secreto</h3>
          <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
            <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; padding: 10px 20px; border-radius: 6px;">
              <span style="display:block; font-size: 0.85rem; color: #a7f3d0;">Aciertos Totales</span>
              <b id="cod-aciertos" style="font-size: 1.5rem; color: #34d399;">0 / 3</b>
            </div>
            <div style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; padding: 10px 20px; border-radius: 6px;">
              <span style="display:block; font-size: 0.85rem; color: #fde68a;">Puntos Obtenidos</span>
              <b id="cod-puntos" style="font-size: 1.5rem; color: #fbbf24;">0 / 90</b>
            </div>
          </div>
          <p id="mensaje-feedback" class="muted" style="margin: 0;"></p>
        </div>

        <script>
        const pistasUsadas = { 1: false, 2: false, 3: false };
        function mostrarPista(num) {
            document.getElementById(`pista-${num}`).style.display = 'block';
            pistasUsadas[num] = true;
        }

        function validarCodigoSecreto(e) {
            e.preventDefault();
            const r1 = document.getElementById('resp1').value.trim().toLowerCase();
            const r2 = document.getElementById('resp2').value.trim().toLowerCase();
            const r3 = document.getElementById('resp3').value.trim().toLowerCase();

            let puntosTotales = 0, aciertos = 0, errores = 0;

            if (['botella', 'una botella'].includes(r1)) { aciertos++; puntosTotales += pistasUsadas[1] ? 20 : 30; } else { errores++; }
            if (['hoyo', 'agujero', 'un hoyo', 'un agujero'].includes(r2)) { aciertos++; puntosTotales += pistasUsadas[2] ? 20 : 30; } else { errores++; }
            if (['guante', 'un guante'].includes(r3)) { aciertos++; puntosTotales += pistasUsadas[3] ? 20 : 30; } else { errores++; }

            document.getElementById('cod-aciertos').innerText = `${aciertos} / 3`;
            document.getElementById('cod-puntos').innerText = `${puntosTotales} / 90 pts`;
            document.getElementById('mensaje-feedback').innerText = aciertos === 3 ? "¡Increíble! Han descifrado todas las cerraduras." : "Revisen las pistas y vuelvan a intentar.";
            
            const resBox = document.getElementById('resultado-codigo');
            resBox.style.display = 'block';
            resBox.scrollIntoView({ behavior: 'smooth' });

            // Guardar en Base de Datos
            const formData = new FormData();
            formData.append('reto_slug', 'codigo-secreto');
            formData.append('aciertos', aciertos);
            formData.append('errores', errores);
            formData.append('puntos', puntosTotales);
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