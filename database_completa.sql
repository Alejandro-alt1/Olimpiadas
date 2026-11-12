-- =====================================================================
--  OLIMPIADAS VIRTUALES CORPORATIVAS · Base de datos (Fase 1)
--  Pegar completo en phpMyAdmin > pestaña SQL (o Importar)
-- =====================================================================
CREATE DATABASE IF NOT EXISTS olimpiadas
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE olimpiadas;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bitacora, puntuaciones, equipos, retos, usuarios, configuracion;
SET FOREIGN_KEY_CHECKS = 1;

-- Facilitadores / administradores (la primera cuenta se crea desde login.php)
CREATE TABLE usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario       VARCHAR(40)  NOT NULL UNIQUE,
  nombre        VARCHAR(80)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Configuración general del evento
CREATE TABLE configuracion (
  clave VARCHAR(50) PRIMARY KEY,
  valor VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Los 5 retos
CREATE TABLE retos (
  id               TINYINT UNSIGNED PRIMARY KEY,
  slug             VARCHAR(40)  NOT NULL UNIQUE,
  orden            TINYINT UNSIGNED NOT NULL,
  nombre           VARCHAR(60)  NOT NULL,
  resumen          VARCHAR(255) NOT NULL,
  proposito        TEXT NOT NULL,
  mecanica         TEXT NOT NULL,            -- una viñeta por línea
  regla_puntos     VARCHAR(255) NOT NULL,
  habilidades      VARCHAR(255) NOT NULL,
  icono            VARCHAR(8)   NOT NULL,
  color            CHAR(7)      NOT NULL,
  puntos_max       SMALLINT UNSIGNED NOT NULL,
  duracion_min     TINYINT UNSIGNED NOT NULL, -- según la agenda sugerida
  estado           ENUM('pendiente','abierto','cerrado') NOT NULL DEFAULT 'pendiente',
  inicio_at        DATETIME NULL
) ENGINE=InnoDB;

-- Equipos (10 sugeridos, de 5 a 10 integrantes)
CREATE TABLE equipos (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(60) NOT NULL UNIQUE,
  color     CHAR(7)     NOT NULL DEFAULT '#3b82f6',
  capitan   VARCHAR(80) NOT NULL DEFAULT '',
  miembros  TINYINT UNSIGNED NOT NULL DEFAULT 5,
  pin       VARCHAR(8)  NOT NULL,            -- código de acceso del capitán
  activo    TINYINT(1)  NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Puntos por equipo y reto (UNIQUE = un reto nunca se suma dos veces)
CREATE TABLE puntuaciones (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipo_id  INT UNSIGNED NOT NULL,
  reto_id    TINYINT UNSIGNED NOT NULL,
  puntos     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  detalle    VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_equipo_reto (equipo_id, reto_id),
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
  FOREIGN KEY (reto_id)   REFERENCES retos(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- Bitácora: incidencias, decisiones y cambios de puntos (auditoría)
CREATE TABLE bitacora (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo          ENUM('incidencia','puntos','sistema') NOT NULL DEFAULT 'sistema',
  reto_id       TINYINT UNSIGNED NULL,
  equipo_id     INT UNSIGNED NULL,
  descripcion   VARCHAR(500) NOT NULL,
  decision      VARCHAR(500) NULL,
  autorizado_por VARCHAR(80) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reto_id)   REFERENCES retos(id)   ON DELETE SET NULL,
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  DATOS INICIALES
-- ---------------------------------------------------------------------
INSERT INTO configuracion (clave, valor) VALUES
 ('evento_nombre', 'Olimpiadas Virtuales Corporativas'),
 ('marcador_visible', '1');

INSERT INTO retos (id, slug, orden, nombre, resumen, proposito, mecanica, regla_puntos, habilidades, icono, color, puntos_max, duracion_min) VALUES
(1,'mentes-brillantes',1,'Mentes Brillantes',
 'Ocho preguntas de opción múltiple con validación automática.',
 'Activar el razonamiento y la toma de decisiones bajo una presión de tiempo moderada.',
 'Ocho preguntas con opciones seleccionables.
Cada equipo lee la pregunta, debate brevemente y el capitán elige una opción.
La respuesta se valida contra la solución definida por el organizador.
Cada pregunta tiene 30 segundos y una sola respuesta válida.',
 '15 puntos por respuesta correcta; 0 si es incorrecta o no se envía. Máximo 120.',
 'Análisis, atención, comunicación y consenso',
 '🧠','#2f7df6',120,9),
(2,'memoria-extrema',2,'Memoria Extrema',
 'Un tablero de 12 objetos durante 30 segundos y cinco preguntas.',
 'Ejercitar la observación y la memoria visual, además de la capacidad de compartir detalles.',
 'Se presenta un tablero con 12 objetos durante 30 segundos.
Al terminar la observación el tablero se oculta.
El equipo responde cinco preguntas sobre los objetos, su posición o sus características.
Sin capturas de pantalla: se mide memoria visual.',
 '20 puntos por respuesta correcta. Máximo 100.',
 'Concentración, memoria de trabajo, descripción precisa y colaboración',
 '👁','#14b8a6',100,8),
(3,'codigo-secreto',3,'Código Secreto',
 'Tres acertijos que abren cerraduras sucesivas hasta el código final.',
 'Practicar el razonamiento lógico y la resolución colaborativa de problemas.',
 'Tres acertijos que desbloquean cerraduras o etapas sucesivas.
Cada acertijo tiene una respuesta verificable y una pista opcional.
Se debe completar el acertijo actual antes de avanzar al siguiente.',
 '30 puntos por acertijo sin pista; 20 si usa pista; 0 si no se resuelve. Máximo 90.',
 'Lógica, persistencia, escucha y división de tareas',
 '🔒','#8b5cf6',90,9),
(4,'creatividad-sin-limites',4,'Creatividad Sin Límites',
 'Propuesta de un producto innovador evaluada con una rúbrica pública.',
 'Generar una idea nueva y explicar cómo aporta valor a un usuario o a una organización.',
 'El equipo propone un producto o servicio mediante un formulario.
Incluye nombre, problema que resuelve, descripción, público objetivo y beneficio.
Imagen opcional o dibujo rápido.
Rúbrica conocida de antemano: Originalidad 40 · Utilidad 35 · Claridad 25.',
 'Rúbrica de 100 puntos evaluada por el facilitador (idealmente dos evaluadores que promedian).',
 'Creatividad, comunicación, orientación al usuario y pensamiento práctico',
 '💡','#f59e0b',100,11),
(5,'desafio-relampago',5,'Desafío Relámpago',
 'Seis desafíos de respuesta rápida, con un ejercicio de toma de decisiones.',
 'Decidir con rapidez sin perder de vista la calidad de la respuesta.',
 'Seis desafíos breves, incluyendo al menos un ejercicio de toma de decisiones.
Cada desafío tiene un límite de 1 minuto visible.
El capitán envía una sola respuesta por desafío.
Se considera exactitud y, cuando aplique, justificación.',
 '15 puntos por desafío completado correctamente. Máximo 90.',
 'Priorización, agilidad mental, criterio, comunicación y responsabilidad',
 '⚡','#ef4444',90,9);

INSERT INTO equipos (nombre, color, capitan, miembros, pin) VALUES
('Equipo 1','#2f7df6','',5,'4821'),
('Equipo 2','#14b8a6','',5,'7305'),
('Equipo 3','#8b5cf6','',5,'1946'),
('Equipo 4','#f59e0b','',5,'6082'),
('Equipo 5','#ef4444','',5,'3517'),
('Equipo 6','#ec4899','',5,'9264'),
('Equipo 7','#22c55e','',5,'5738'),
('Equipo 8','#06b6d4','',5,'2409'),
('Equipo 9','#f97316','',5,'8153'),
('Equipo 10','#a3e635','',5,'6670');

-- Fila de puntos en 0 para cada equipo/reto (el marcador nunca queda vacío)
INSERT INTO puntuaciones (equipo_id, reto_id, puntos)
SELECT e.id, r.id, 0 FROM equipos e CROSS JOIN retos r;

-- =====================================================================
--  OLIMPIADAS VIRTUALES CORPORATIVAS · FASE 2 (los 5 retos)
-- =====================================================================
USE olimpiadas;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS evaluaciones, propuestas, intentos_codigo, acertijos, respuestas,
                     progreso_pregunta, sesiones, memoria_objetos, preguntas;
SET FOREIGN_KEY_CHECKS = 1;

-- Preguntas de opción (retos 1, 2 y 5)
CREATE TABLE preguntas (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reto_id    TINYINT UNSIGNED NOT NULL,
  orden      TINYINT UNSIGNED NOT NULL,
  tipo       ENUM('opcion','decision') NOT NULL DEFAULT 'opcion',
  enunciado  VARCHAR(500) NOT NULL,
  opcion_a   VARCHAR(200) NOT NULL,
  opcion_b   VARCHAR(200) NOT NULL,
  opcion_c   VARCHAR(200) NOT NULL,
  opcion_d   VARCHAR(200) NOT NULL,
  correcta   CHAR(1) NOT NULL,
  puntos     SMALLINT UNSIGNED NOT NULL,
  tiempo_seg SMALLINT UNSIGNED NOT NULL DEFAULT 0,   -- 0 = sin límite individual
  UNIQUE KEY uq_reto_orden (reto_id, orden),
  FOREIGN KEY (reto_id) REFERENCES retos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tablero de Memoria Extrema (12 objetos, 4 columnas x 3 filas)
CREATE TABLE memoria_objetos (
  posicion TINYINT UNSIGNED PRIMARY KEY,
  emoji    VARCHAR(12) NOT NULL,
  nombre   VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

-- Estado por equipo (Memoria Extrema: observación y envío)
CREATE TABLE sesiones (
  equipo_id INT UNSIGNED NOT NULL,
  reto_id   TINYINT UNSIGNED NOT NULL,
  inicio_at DATETIME NOT NULL,
  fin_at    DATETIME NULL,
  PRIMARY KEY (equipo_id, reto_id),
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
  FOREIGN KEY (reto_id)   REFERENCES retos(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- Cronómetro individual por pregunta (retos 1 y 5)
CREATE TABLE progreso_pregunta (
  equipo_id   INT UNSIGNED NOT NULL,
  pregunta_id INT UNSIGNED NOT NULL,
  inicio_at   DATETIME NOT NULL,
  PRIMARY KEY (equipo_id, pregunta_id),
  FOREIGN KEY (equipo_id)   REFERENCES equipos(id)   ON DELETE CASCADE,
  FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Respuestas de los capitanes (UNIQUE = una sola respuesta por pregunta)
CREATE TABLE respuestas (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipo_id    INT UNSIGNED NOT NULL,
  reto_id      TINYINT UNSIGNED NOT NULL,
  pregunta_id  INT UNSIGNED NOT NULL,
  respuesta    CHAR(1) NULL,                 -- NULL = no enviada / tiempo agotado
  correcta     TINYINT(1) NOT NULL DEFAULT 0,
  puntos       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  justificacion VARCHAR(500) NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_equipo_pregunta (equipo_id, pregunta_id),
  FOREIGN KEY (equipo_id)   REFERENCES equipos(id)   ON DELETE CASCADE,
  FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Código Secreto
CREATE TABLE acertijos (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden      TINYINT UNSIGNED NOT NULL UNIQUE,
  enunciado  VARCHAR(500) NOT NULL,
  respuestas VARCHAR(255) NOT NULL,          -- variantes aceptadas separadas por |
  pista      VARCHAR(255) NOT NULL,
  fragmento  VARCHAR(4)   NOT NULL           -- parte del código final que revela
) ENGINE=InnoDB;

CREATE TABLE intentos_codigo (
  equipo_id      INT UNSIGNED NOT NULL,
  acertijo_id    INT UNSIGNED NOT NULL,
  pista_usada    TINYINT(1) NOT NULL DEFAULT 0,
  resuelto       TINYINT(1) NOT NULL DEFAULT 0,
  intentos       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ultimo_intento DATETIME NULL,
  puntos         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (equipo_id, acertijo_id),
  FOREIGN KEY (equipo_id)   REFERENCES equipos(id)   ON DELETE CASCADE,
  FOREIGN KEY (acertijo_id) REFERENCES acertijos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Creatividad Sin Límites
CREATE TABLE propuestas (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipo_id   INT UNSIGNED NOT NULL UNIQUE,
  nombre      VARCHAR(80)  NOT NULL,
  problema    VARCHAR(500) NOT NULL,
  descripcion VARCHAR(800) NOT NULL,
  publico     VARCHAR(250) NOT NULL,
  beneficio   VARCHAR(500) NOT NULL,
  imagen      VARCHAR(120) NULL,
  enviada_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evaluaciones (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  propuesta_id INT UNSIGNED NOT NULL,
  usuario_id   INT UNSIGNED NOT NULL,
  originalidad TINYINT UNSIGNED NOT NULL,    -- 0-40
  utilidad     TINYINT UNSIGNED NOT NULL,    -- 0-35
  claridad     TINYINT UNSIGNED NOT NULL,    -- 0-25
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_prop_eval (propuesta_id, usuario_id),
  FOREIGN KEY (propuesta_id) REFERENCES propuestas(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO preguntas (reto_id, orden, tipo, enunciado, opcion_a, opcion_b, opcion_c, opcion_d, correcta, puntos, tiempo_seg) VALUES
(1,1,'opcion','¿Cuál de estas prácticas ayuda más a un equipo a tomar una decisión informada?','Escuchar evidencia y comparar alternativas','Elegir sin debatir','Ignorar los riesgos','Votar sin leer la información','A',15,30),
(1,2,'opcion','¿Cuál de estos elementos es un lenguaje de programación?','Excel','Python','Word','Photoshop','B',15,30),
(1,3,'opcion','En una conversación de trabajo, ¿qué debe hacer primero quien escucha?','Preparar su respuesta mientras el otro habla','Interrumpir para corregir','Atender y confirmar que entendió','Revisar el teléfono','C',15,30),
(1,4,'opcion','¿Qué número continúa la serie 2, 4, 8, 16, …?','24','30','20','32','D',15,30),
(1,5,'opcion','Un proyecto se retrasa por falta de claridad en las tareas. ¿Cuál es la mejor primera acción?','Esperar a que alguien pregunte','Asignar responsables y fechas claras','Cambiar de proyecto','Culpar al equipo anterior','B',15,30),
(1,6,'opcion','¿Qué significa la sigla KPI en el ámbito empresarial?','Kit de productividad interna','Proceso de inicio de proyecto','Indicador clave de desempeño','Plan integral de calidad','C',15,30),
(1,7,'opcion','Si 3 personas arman 3 cajas en 3 minutos, ¿cuántas cajas arman 6 personas en 3 minutos?','3','9','12','6','D',15,30),
(1,8,'opcion','¿Qué caracteriza a una retroalimentación (feedback) útil?','Específica, oportuna y respetuosa','General y sin ejemplos','Pública y con tono de burla','Solo cuando hay un error grave','A',15,30),
(2,1,'opcion','¿Qué objeto estaba en la esquina superior derecha?','Cámara','Audífonos','Taza','Balón','B',20,0),
(2,2,'opcion','¿Había una taza, una botella o ambas?','Solo una taza','Solo una botella','Ambas','Ninguna','C',20,0),
(2,3,'opcion','¿Qué objeto estaba justo a la izquierda de los audífonos?','Taza','Mochila','Balón','Cámara','A',20,0),
(2,4,'opcion','¿Qué objeto estaba justo debajo del balón?','Planta','Lentes','Libro','Cámara','D',20,0),
(2,5,'opcion','¿Cuál era el último objeto de la tercera fila?','Tenis','Teléfono','Sombrero','Botella','A',20,0),
(5,1,'opcion','Tu equipo tiene un problema urgente y solo 10 minutos para resolverlo. ¿Qué haces?','Esperar a ver si se resuelve solo','Pedir que otro equipo lo resuelva','Definir el problema, priorizar y repartir tareas','Tomar una decisión sin consultar a nadie','C',15,60),
(5,2,'opcion','Calcula: 15 × 4 − 10 = ?','50','60','45','70','A',15,60),
(5,3,'decision','Un proyecto tiene una entrega urgente, pero se detecta un error que puede afectar al cliente. ¿Qué debería hacer el equipo?','Ocultar el problema y entregar a tiempo','Identificar el impacto, comunicar el riesgo y proponer una corrección realista','Entregar y esperar que nadie lo note','Cancelar la entrega sin avisar','B',15,60),
(5,4,'opcion','Completa la secuencia: 3, 6, 12, 24, ?','36','42','30','48','D',15,60),
(5,5,'opcion','Una reunión de 30 minutos ya consumió 20 sin acuerdos. ¿Qué es lo más conveniente?','Cerrar con acuerdos mínimos y asignar seguimiento','Extenderla sin límite de tiempo','Cancelarla sin avisar','Repetir el mismo debate','A',15,60),
(5,6,'opcion','¿Cuál palabra NO pertenece al grupo?','Martes','Jueves','Septiembre','Sábado','C',15,60);

INSERT INTO memoria_objetos (posicion, emoji, nombre) VALUES
(1,'⚽','Balón'),(2,'🎒','Mochila'),(3,'☕','Taza'),(4,'🎧','Audífonos'),
(5,'📷','Cámara'),(6,'🌱','Planta'),(7,'🕶️','Lentes'),(8,'📕','Libro'),
(9,'🧴','Botella'),(10,'🎩','Sombrero'),(11,'📱','Teléfono'),(12,'👟','Tenis');

INSERT INTO acertijos (orden, enunciado, respuestas, pista, fragmento) VALUES
(1,'Tengo teclas, pero no abro puertas; puedo escribir sin lápiz. ¿Qué soy?','teclado|un teclado|el teclado','Lo usas todos los días frente a la computadora.','4'),
(2,'Cuanto más me quitas, más grande me hago. ¿Qué soy?','agujero|hueco|un agujero|un hueco','Piensa en un bache o en un pozo.','1'),
(3,'Si me nombras, desaparezco. ¿Qué soy?','silencio|el silencio','Es lo que pide el facilitador antes de empezar un reto.','7');

-- =====================================================================
--  OLIMPIADAS VIRTUALES CORPORATIVAS · FASE 3 (versión final)
-- =====================================================================
USE olimpiadas;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS resultados_pregunta, respuestas_ind, juego;
DROP TABLE IF EXISTS borrador_listos, borradores, intentos_codigo, respuestas, progreso_pregunta, sesiones, participantes;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE participantes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipo_id  INT UNSIGNED NOT NULL,
  alias      VARCHAR(30) NOT NULL,
  token      CHAR(64)    NOT NULL,
  last_seen  DATETIME    NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_equipo_alias (equipo_id, alias),
  KEY ix_equipo_seen (equipo_id, last_seen),
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sesiones (
  participante_id INT UNSIGNED NOT NULL,
  reto_id   TINYINT UNSIGNED NOT NULL,
  equipo_id INT UNSIGNED NOT NULL,
  inicio_at DATETIME NOT NULL,
  fin_at    DATETIME NULL,
  PRIMARY KEY (participante_id, reto_id),
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
  FOREIGN KEY (reto_id)   REFERENCES retos(id)   ON DELETE CASCADE,
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE progreso_pregunta (
  participante_id INT UNSIGNED NOT NULL,
  pregunta_id     INT UNSIGNED NOT NULL,
  inicio_at       DATETIME NOT NULL,
  PRIMARY KEY (participante_id, pregunta_id),
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
  FOREIGN KEY (pregunta_id)     REFERENCES preguntas(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE respuestas (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  participante_id INT UNSIGNED NOT NULL,
  equipo_id       INT UNSIGNED NOT NULL,
  reto_id         TINYINT UNSIGNED NOT NULL,
  pregunta_id     INT UNSIGNED NOT NULL,
  respuesta       CHAR(1) NULL,
  correcta        TINYINT(1) NOT NULL DEFAULT 0,
  puntos          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  justificacion   VARCHAR(500) NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_part_pregunta (participante_id, pregunta_id),
  KEY ix_equipo_reto (equipo_id, reto_id),
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
  FOREIGN KEY (equipo_id)       REFERENCES equipos(id)       ON DELETE CASCADE,
  FOREIGN KEY (pregunta_id)     REFERENCES preguntas(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE intentos_codigo (
  participante_id INT UNSIGNED NOT NULL,
  acertijo_id     INT UNSIGNED NOT NULL,
  equipo_id       INT UNSIGNED NOT NULL,
  pista_usada     TINYINT(1) NOT NULL DEFAULT 0,
  resuelto        TINYINT(1) NOT NULL DEFAULT 0,
  intentos        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ultimo_intento  DATETIME NULL,
  puntos          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (participante_id, acertijo_id),
  KEY ix_equipo (equipo_id),
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
  FOREIGN KEY (acertijo_id)     REFERENCES acertijos(id)     ON DELETE CASCADE,
  FOREIGN KEY (equipo_id)       REFERENCES equipos(id)       ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE borradores (
  equipo_id   INT UNSIGNED PRIMARY KEY,
  nombre      VARCHAR(80)  NOT NULL DEFAULT '',
  problema    VARCHAR(500) NOT NULL DEFAULT '',
  descripcion VARCHAR(800) NOT NULL DEFAULT '',
  publico     VARCHAR(250) NOT NULL DEFAULT '',
  beneficio   VARCHAR(500) NOT NULL DEFAULT '',
  imagen      VARCHAR(120) NULL,
  editado_por INT UNSIGNED NULL,
  version     INT UNSIGNED NOT NULL DEFAULT 1,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (equipo_id)   REFERENCES equipos(id)       ON DELETE CASCADE,
  FOREIGN KEY (editado_por) REFERENCES participantes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE borrador_listos (
  equipo_id       INT UNSIGNED NOT NULL,
  participante_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (equipo_id, participante_id),
  FOREIGN KEY (equipo_id)       REFERENCES equipos(id)       ON DELETE CASCADE,
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO configuracion (clave, valor) VALUES ('promedio_base', 'declarados')
  ON DUPLICATE KEY UPDATE valor = valor;

UPDATE puntuaciones SET puntos = 0, detalle = NULL WHERE reto_id <> 4;

-- =====================================================================
--  OLIMPIADAS VIRTUALES CORPORATIVAS · FASE 4 · Torneo por enfrentamientos
-- =====================================================================
USE olimpiadas;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS enfrentamientos, rondas;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE rondas (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero     TINYINT UNSIGNED NOT NULL UNIQUE,
  reto_id    TINYINT UNSIGNED NULL,
  estado     ENUM('en_curso','cerrada') NOT NULL DEFAULT 'en_curso',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  cerrada_at DATETIME NULL,
  FOREIGN KEY (reto_id) REFERENCES retos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE enfrentamientos (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ronda_id   INT UNSIGNED NOT NULL,
  equipo_a   INT UNSIGNED NOT NULL,
  equipo_b   INT UNSIGNED NOT NULL,
  puntos_a   SMALLINT UNSIGNED NULL,
  puntos_b   SMALLINT UNSIGNED NULL,
  ganador_id INT UNSIGNED NULL,
  empate     TINYINT(1) NOT NULL DEFAULT 0,
  estado     ENUM('pendiente','jugado') NOT NULL DEFAULT 'pendiente',
  manual     TINYINT(1) NOT NULL DEFAULT 0,
  nota       VARCHAR(255) NULL,
  UNIQUE KEY uq_ronda_a (ronda_id, equipo_a),
  UNIQUE KEY uq_ronda_b (ronda_id, equipo_b),
  FOREIGN KEY (ronda_id)   REFERENCES rondas(id)  ON DELETE CASCADE,
  FOREIGN KEY (equipo_a)   REFERENCES equipos(id) ON DELETE CASCADE,
  FOREIGN KEY (equipo_b)   REFERENCES equipos(id) ON DELETE CASCADE,
  FOREIGN KEY (ganador_id) REFERENCES equipos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO configuracion (clave, valor) VALUES
  ('torneo_activo', '0'),
  ('torneo_rondas', '5'),
  ('torneo_primera', 'predefinido')
ON DUPLICATE KEY UPDATE valor = valor;
