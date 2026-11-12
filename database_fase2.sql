-- =====================================================================
--  OLIMPIADAS VIRTUALES CORPORATIVAS · FASE 2 (los 5 retos)
--  Ejecutar DESPUÉS de database.sql. No borra equipos, puntos ni bitácora.
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

-- ---------------------------------------------------------------------
--  CONTENIDO INICIAL (editable desde Panel > Preguntas)
-- ---------------------------------------------------------------------
INSERT INTO preguntas (reto_id, orden, tipo, enunciado, opcion_a, opcion_b, opcion_c, opcion_d, correcta, puntos, tiempo_seg) VALUES
-- Reto 1 · Mentes Brillantes (8 x 15 = 120 pts, 30 s por pregunta)
(1,1,'opcion','¿Cuál de estas prácticas ayuda más a un equipo a tomar una decisión informada?','Escuchar evidencia y comparar alternativas','Elegir sin debatir','Ignorar los riesgos','Votar sin leer la información','A',15,30),
(1,2,'opcion','¿Cuál de estos elementos es un lenguaje de programación?','Excel','Python','Word','Photoshop','B',15,30),
(1,3,'opcion','En una conversación de trabajo, ¿qué debe hacer primero quien escucha?','Preparar su respuesta mientras el otro habla','Interrumpir para corregir','Atender y confirmar que entendió','Revisar el teléfono','C',15,30),
(1,4,'opcion','¿Qué número continúa la serie 2, 4, 8, 16, …?','24','30','20','32','D',15,30),
(1,5,'opcion','Un proyecto se retrasa por falta de claridad en las tareas. ¿Cuál es la mejor primera acción?','Esperar a que alguien pregunte','Asignar responsables y fechas claras','Cambiar de proyecto','Culpar al equipo anterior','B',15,30),
(1,6,'opcion','¿Qué significa la sigla KPI en el ámbito empresarial?','Kit de productividad interna','Proceso de inicio de proyecto','Indicador clave de desempeño','Plan integral de calidad','C',15,30),
(1,7,'opcion','Si 3 personas arman 3 cajas en 3 minutos, ¿cuántas cajas arman 6 personas en 3 minutos?','3','9','12','6','D',15,30),
(1,8,'opcion','¿Qué caracteriza a una retroalimentación (feedback) útil?','Específica, oportuna y respetuosa','General y sin ejemplos','Pública y con tono de burla','Solo cuando hay un error grave','A',15,30),
-- Reto 2 · Memoria Extrema (5 x 20 = 100 pts)
(2,1,'opcion','¿Qué objeto estaba en la esquina superior derecha?','Cámara','Audífonos','Taza','Balón','B',20,0),
(2,2,'opcion','¿Había una taza, una botella o ambas?','Solo una taza','Solo una botella','Ambas','Ninguna','C',20,0),
(2,3,'opcion','¿Qué objeto estaba justo a la izquierda de los audífonos?','Taza','Mochila','Balón','Cámara','A',20,0),
(2,4,'opcion','¿Qué objeto estaba justo debajo del balón?','Planta','Lentes','Libro','Cámara','D',20,0),
(2,5,'opcion','¿Cuál era el último objeto de la tercera fila?','Tenis','Teléfono','Sombrero','Botella','A',20,0),
-- Reto 5 · Desafío Relámpago (6 x 15 = 90 pts, 60 s por desafío)
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
