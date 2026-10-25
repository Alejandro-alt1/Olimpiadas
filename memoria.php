<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Memoria Extrema';
$pagina = 'retos';
require __DIR__ . '/includes/header.php';
?>

<div class="section">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
      <span style="font-size: 2.5rem;">👁️</span>
      <div>
        <div class="eyebrow">Reto 2 de 5</div>
        <h1 style="margin: 0; font-size: 2rem;">Memoria Extrema</h1>
      </div>
    </div>

    <div class="card" style="margin-bottom: 20px; border-left: 5px solid #10b981;">
      <p style="font-size: 1.1rem; margin-bottom: 10px;">Un tablero de 12 objetos durante 30 segundos y cinco preguntas.</p>
      <p class="muted" style="margin: 0;"><b>Propósito:</b> Ejercitar la observación y la memoria visual.</p>
    </div>

    <div class="card">
        <div id="seccion-observacion" style="text-align: center; background: rgba(255,255,255,0.02); padding: 25px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
          <div id="cronometro-memoria" style="font-size: 2.5rem; font-weight: bold; color: #38bdf8; margin-bottom: 15px;">⏱️ 30s</div>
          <p class="muted" style="margin-bottom: 15px;">Observa los 12 objetos con atención antes de que el tiempo termine:</p>
          
          <div id="tablero-objetos" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; max-width: 400px; margin: 0 auto 20px auto;">
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">🍎</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">☕</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">💻</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">🎧</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">📚</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">🔑</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">💡</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">📱</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">🚗</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">⌚</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">✏️</div>
            <div style="background:#1e293b; padding:15px; border-radius:8px; font-size:1.8rem;">🪴</div>
          </div>

          <button type="button" id="btn-iniciar-memoria" class="btn btn-primary" onclick="iniciarMemoriaExtrema()">Iniciar observación (30s)</button>
        </div>

        <form id="form-memoria" style="margin-top: 20px; display: none;" onsubmit="validarMemoriaExtrema(event)">
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>1. ¿Qué objeto electrónico portátil con pantalla aparecía en el tablero?</b></p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m1" value="A" required> A) Computadora portátil (💻)</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m1" value="B"> B) Televisión antigua</label>
          </div>
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>2. ¿Cuál de estos elementos de audio formaba parte de los objetos?</b></p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m2" value="A" required> A) Unos audífonos (🎧)</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m2" value="B"> B) Una guitarra eléctrica</label>
          </div>
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>3. ¿Había una taza (☕) o una botella de agua en el tablero?</b></p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m3" value="A" required> A) Sí, había una taza de café (☕)</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m3" value="B"> B) No, solo había una botella</label>
          </div>
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>4. ¿Qué fruta formaba parte de los 12 objetos?</b></p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m4" value="A" required> A) Una manzana (🍎)</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m4" value="B"> B) Una banana</label>
          </div>
          <div class="card" style="margin-bottom: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);">
            <p><b>5. ¿Qué objeto pequeño de metal servía para abrir cerraduras?</b></p>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m5" value="A" required> A) Unas llaves (🔑)</label>
            <label style="display:block; margin: 8px 0; cursor:pointer;"><input type="radio" name="m5" value="B"> B) Un candado cerrado</label>
          </div>
          <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px; padding: 14px;">Validar respuestas de memoria</button>
        </form>

        <div id="resultado-memoria" style="margin-top: 25px; display: none; padding: 20px; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; text-align: center;">
          <h3 style="color: #38bdf8; margin-bottom: 15px;">Resultados de Memoria Extrema</h3>
          <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
            <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; padding: 10px 20px; border-radius: 6px;">
              <span style="display:block; font-size: 0.85rem; color: #a7f3d0;">Aciertos</span>
              <b id="mem-aciertos" style="font-size: 1.5rem; color: #34d399;">0</b>
            </div>
            <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; padding: 10px 20px; border-radius: 6px;">
              <span style="display:block; font-size: 0.85rem; color: #fca5a5;">Errores</span>
              <b id="mem-errores" style="font-size: 1.5rem; color: #f87171;">0</b>
            </div>
            <div style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; padding: 10px 20px; border-radius: 6px;">
              <span style="display:block; font-size: 0.85rem; color: #fde68a;">Puntos Obtenidos</span>
              <b id="mem-puntos" style="font-size: 1.5rem; color: #fbbf24;">0 / 100</b>
            </div>
          </div>
        </div>

        <script>
        function iniciarMemoriaExtrema() {
            const btn = document.getElementById('btn-iniciar-memoria');
            const crono = document.getElementById('cronometro-memoria');
            if (btn) btn.style.display = 'none';
            let tiempoRestante = 30;
            const intervalo = setInterval(() => {
                tiempoRestante--;
                if (crono) crono.innerText = `⏱️ ${tiempoRestante}s`;
                if (tiempoRestante <= 0) {
                    clearInterval(intervalo);
                    document.getElementById('seccion-observacion').style.display = 'none';
                    document.getElementById('form-memoria').style.display = 'block';
                }
            }, 1000);
        }

        function validarMemoriaExtrema(e) {
            e.preventDefault();
            const correctas = { m1: 'A', m2: 'A', m3: 'A', m4: 'A', m5: 'A' };
            let aciertos = 0, errores = 0;
            for (let m in correctas) {
                const sel = document.querySelector(`input[name="${m}"]:checked`);
                if (sel && sel.value === correctas[m]) aciertos++;
                else errores++;
            }
            let puntosObtenidos = aciertos * 20;

            document.getElementById('mem-aciertos').innerText = aciertos;
            document.getElementById('mem-errores').innerText = errores;
            document.getElementById('mem-puntos').innerText = `${puntosObtenidos} / 100 pts`;
            
            const resBox = document.getElementById('resultado-memoria');
            resBox.style.display = 'block';
            resBox.scrollIntoView({ behavior: 'smooth' });

            // Guardar en Base de Datos
            const formData = new FormData();
            formData.append('reto_slug', 'memoria-extrema');
            formData.append('aciertos', aciertos);
            formData.append('errores', errores);
            formData.append('puntos', puntosObtenidos);
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