<?php
require __DIR__ . '/includes/bootstrap.php';
$titulo = 'Desafío Relámpago';
$pagina = 'retos';
require __DIR__ . '/includes/header.php';
?>

<div class="section">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
      <span style="font-size: 2.5rem;">⚡</span>
      <div>
        <div class="eyebrow">Reto 5 de 5 — Modo Kahoot</div>
        <h1 style="margin: 0; font-size: 2rem;">Desafío Relámpago</h1>
      </div>
    </div>

    <div class="card" style="margin-bottom: 20px; border-left: 5px solid #eab308;">
      <p style="font-size: 1.1rem; margin-bottom: 10px;">Preguntas cortas a contrarreloj. Tienen 15 segundos por pregunta para seleccionar la opción correcta.</p>
      <p class="muted" style="margin: 0;"><b>Regla de Puntos:</b> 15 puntos por cada respuesta correcta enviada a tiempo.</p>
    </div>

    <div class="card" id="contenedor-juego" style="position: relative; overflow: hidden; min-height: 380px;">
        
        <!-- PANTALLA INICIAL -->
        <div id="pantalla-inicio" style="text-align: center; padding: 40px 20px;">
            <div style="font-size: 4rem; margin-bottom: 10px;">⚡</div>
            <h2 style="margin-bottom: 15px;">¿Listos para el Desafío Relámpago?</h2>
            <p class="muted" style="margin-bottom: 25px;">El reloj comenzará a correr inmediatamente al iniciar.</p>
            <button type="button" class="btn btn-primary" style="padding: 15px 35px; font-size: 1.2rem;" onclick="comenzarKahoot()">¡Comenzar Kahoot!</button>
        </div>

        <!-- PANTALLA DE PREGUNTAS EN VIVO -->
        <div id="pantalla-pregunta" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <span id="num-pregunta" class="badge" style="background: rgba(255,255,255,0.1); font-size: 1rem; padding: 8px 12px;">Pregunta 1 de 6</span>
                <div id="reloj-kahoot" style="font-size: 1.8rem; font-weight: bold; color: #ef4444; background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 5px 15px; border-radius: 20px;">⏱️ 15s</div>
            </div>

            <h2 id="texto-pregunta" style="text-align: center; margin: 30px 0; font-size: 1.4rem; min-height: 60px;">Cargando...</h2>

            <div id="opciones-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 20px;">
                <!-- Las opciones de respuesta se renderizan dinámicamente -->
            </div>
        </div>

        <!-- PANTALLA FINAL DE RESULTADOS -->
        <div id="resultado-desafio" style="display: none; padding: 30px 20px; text-align: center;">
          <h2 style="color: #38bdf8; margin-bottom: 20px;">¡Tiempo Agotado! Resultados del Desafío</h2>
          
          <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; padding: 15px 25px; border-radius: 8px;">
              <span style="display:block; font-size: 0.9rem; color: #a7f3d0;">Aciertos Totales</span>
              <b id="des-aciertos" style="font-size: 2rem; color: #34d399;">0 / 6</b>
            </div>
            <div style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; padding: 15px 25px; border-radius: 8px;">
              <span style="display:block; font-size: 0.9rem; color: #fde68a;">Puntos Obtenidos</span>
              <b id="des-puntos" style="font-size: 2rem; color: #fbbf24;">0 / 90 pts</b>
            </div>
          </div>
          
          <p id="mensaje-kahoot" class="muted" style="margin-top: 15px; font-size: 1.1rem;"></p>
        </div>

    </div>

    <div style="margin-top: 20px; text-align: center;">
      <a href="<?= url('juegos.php') ?>" class="btn btn-ghost">← Volver a la lista de retos</a>
    </div>
</div>

<script>
// Lista de preguntas estilo Kahoot
const preguntasKahoot = [
    {
        pregunta: "1. ¿Cuál es la prioridad ante un contratiempo de último minuto?",
        opciones: [
            { texto: "A) Comunicar rápido y buscar opciones", correcta: true, color: "#e11d48" },
            { texto: "B) Ocultarlo hasta finalizar la sesión", correcta: false, color: "#2563eb" },
            { texto: "C) Ignorar la falla y continuar", correcta: false, color: "#d97706" },
            { texto: "D) Cancelar todo el evento", correcta: false, color: "#16a34a" }
        ]
    },
    {
        pregunta: "2. Al priorizar tareas con tiempo limitado, se atiende primero:",
        opciones: [
            { texto: "A) Lo más fácil de ignorar", correcta: false, color: "#e11d48" },
            { texto: "B) Lo de mayor impacto y urgencia real", correcta: true, color: "#2563eb" },
            { texto: "C) Lo que requiera menos discusión", correcta: false, color: "#d97706" },
            { texto: "D) Lo último que se recibió", correcta: false, color: "#16a34a" }
        ]
    },
    {
        pregunta: "3. ¿Qué clave fortalece la comunicación en un equipo bajo presión?",
        opciones: [
            { texto: "A) Responder al azar para ganar tiempo", correcta: false, color: "#e11d48" },
            { texto: "B) Escucha activa y mensajes concisos", correcta: true, color: "#2563eb" },
            { texto: "C) Trabajar aislados sin consultar", correcta: false, color: "#d97706" },
            { texto: "D) Dejar que el líder decida todo solo", correcta: false, color: "#16a34a" }
        ]
    },
    {
        pregunta: "4. ¿Cuál es la mejor actitud ante una decisión dividida en el equipo?",
        opciones: [
            { texto: "A) Votar rápido y respaldar la mayoría", correcta: true, color: "#e11d48" },
            { texto: "B) Discutir hasta agotar el reloj", correcta: false, color: "#2563eb" },
            { texto: "C) No enviar ninguna respuesta", correcta: false, color: "#d97706" },
            { texto: "D) Dividirse en grupos separados", correcta: false, color: "#16a34a" }
        ]
    },
    {
        pregunta: "5. ¿Qué recurso es primordial cuando falta poco tiempo en una ronda?",
        opciones: [
            { texto: "A) Cambiar de opinión continuamente", correcta: false, color: "#e11d48" },
            { texto: "B) Agilidad mental y consenso directo", correcta: true, color: "#2563eb" },
            { texto: "C) Reiniciar el navegador web", correcta: false, color: "#d97706" },
            { texto: "D) Pedir más tiempo a los árbitros", correcta: false, color: "#16a34a" }
        ]
    },
    {
        pregunta: "6. El objetivo del Desafío Relámpago es ejercitar:",
        opciones: [
            { texto: "A) Toma de decisiones rápida y coordinada", correcta: true, color: "#e11d48" },
            { texto: "B) Memorización de términos largos", correcta: false, color: "#2563eb" },
            { texto: "C) Redacción de reportes extensos", correcta: false, color: "#d97706" },
            { texto: "D) Programación de bases de datos", correcta: false, color: "#16a34a" }
        ]
    }
];

let indiceActual = 0;
let aciertos = 0;
let errores = 0;
let temporizador;
let tiempoRestante = 15;

function comenzarKahoot() {
    document.getElementById('pantalla-inicio').style.display = 'none';
    document.getElementById('pantalla-pregunta').style.display = 'block';
    mostrarPregunta();
}

function mostrarPregunta() {
    if (indiceActual >= preguntasKahoot.length) {
        finalizarJuego();
        return;
    }

    const q = preguntasKahoot[indiceActual];
    document.getElementById('num-pregunta').innerText = `Pregunta ${indiceActual + 1} de ${preguntasKahoot.length}`;
    document.getElementById('texto-pregunta').innerText = q.pregunta;

    const grid = document.getElementById('opciones-grid');
    grid.innerHTML = '';

    q.opciones.forEach(opc => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.style.cssText = `background: ${opc.color}; color: white; border: none; padding: 20px; font-size: 1.1rem; border-radius: 8px; cursor: pointer; text-align: left; transition: transform 0.1s; font-weight: bold;`;
        btn.innerText = opc.texto;
        btn.onclick = () => seleccionarRespuesta(opc.correcta);
        grid.appendChild(btn);
    });

    // Reiniciar temporizador por pregunta
    clearInterval(temporizador);
    tiempoRestante = 15;
    document.getElementById('reloj-kahoot').innerText = `⏱️ ${tiempoRestante}s`;

    temporizador = setInterval(() => {
        tiempoRestante--;
        document.getElementById('reloj-kahoot').innerText = `⏱️ ${tiempoRestante}s`;

        if (tiempoRestante <= 0) {
            clearInterval(temporizador);
            errores++;
            siguientePregunta();
        }
    }, 1000);
}

function seleccionarRespuesta(esCorrecta) {
    clearInterval(temporizador);
    if (esCorrecta) aciertos++;
    else errores++;

    siguientePregunta();
}

function siguientePregunta() {
    indiceActual++;
    mostrarPregunta();
}

function finalizarJuego() {
    document.getElementById('pantalla-pregunta').style.display = 'none';
    const puntos = aciertos * 15;

    document.getElementById('des-aciertos').innerText = `${aciertos} / ${preguntasKahoot.length}`;
    document.getElementById('des-puntos').innerText = `${puntos} / 90 pts`;
    document.getElementById('mensaje-kahoot').innerText = aciertos >= 4 ? "¡Excelente velocidad de reacción e integración!" : "Buen intento. Practiquen la velocidad de respuesta en equipo.";

    document.getElementById('resultado-desafio').style.display = 'block';

    // Guardar en la Base de Datos automáticamente
    const formData = new FormData();
    formData.append('reto_slug', 'desafio-relampago');
    formData.append('aciertos', aciertos);
    formData.append('errores', errores);
    formData.append('puntos', puntos);
    formData.append('equipo_id', 1);

    fetch('guardar_resultado.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => console.log('Resultado de Kahoot guardado en BD:', data));
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>