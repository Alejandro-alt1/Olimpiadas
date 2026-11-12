OLIMPIADAS VIRTUALES CORPORATIVAS
=================================
Servidor Ubuntu: ver INSTALL_UBUNTU.txt   |   XAMPP (pruebas locales): copiar a htdocs y ejecutar los 3 SQL.

Orden de los SQL en instalación nueva:  database.sql -> database_fase2.sql -> database_fase3.sql -> database_fase4.sql
Si ya tenías hasta la fase 3: ejecuta SOLO database_fase4.sql (no borra nada de lo anterior; si se repite, reinicia el torneo).

CÓMO FUNCIONA (versión final)
 - Cada persona entra sola: código del equipo + alias. No hay capitán ni líder.
 - Cada quien juega a su ritmo (Mentes Brillantes, Memoria Extrema, Código Secreto, Desafío Relámpago).
 - Puntaje del equipo en cada reto = PROMEDIO de los puntajes individuales (suma / base).
   Base configurable en Panel > Equipos: "integrantes declarados" (el mayor entre declarados y registrados)
   o "solo registrados".
 - Creatividad Sin Límites: una propuesta por equipo; todos editan un borrador compartido y se envía cuando
   la mayoría de los conectados marca "Listo". Los evaluadores califican con la rúbrica (promedio).
 - El facilitador abre/cierra cada reto desde el Panel; el marcador se actualiza solo.

PRUEBA DE CARGA (opcional, con ApacheBench):
  ab -n 1000 -c 50 "https://tu-dominio.com/marcador.php"

TORNEO POR ENFRENTAMIENTOS (sistema suizo)  ->  Panel > Torneo suizo  /  torneo.php (público)
 - Cada ronda es un reto: los equipos se enfrentan 1 contra 1 y gana el que tenga más puntos en ese reto.
 - Victoria 3 · empate 1 · derrota 0 (puntos de torneo). Los puntos de pruebas (retos) se llevan aparte.
 - Flujo: activar torneo -> generar ronda -> abrir reto -> cerrar reto -> cerrar ronda (asigna puntos).
 - Orden de la tabla: puntos de torneo, puntos de pruebas, victorias, enfrentamiento directo, Mentes Brillantes, Creatividad.
 - Sin rivales repetidos mientras exista una combinación válida. Se necesita un número PAR de equipos activos.
