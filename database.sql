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
 'Ocho preguntas con opciones seleccionables.\nCada equipo lee la pregunta, debate brevemente y el capitán elige una opción.\nLa respuesta se valida contra la solución definida por el organizador.\nCada pregunta tiene 30 segundos y una sola respuesta válida.',
 '15 puntos por respuesta correcta; 0 si es incorrecta o no se envía. Máximo 120.',
 'Análisis, atención, comunicación y consenso',
 '🧠','#2f7df6',120,9),
(2,'memoria-extrema',2,'Memoria Extrema',
 'Un tablero de 12 objetos durante 30 segundos y cinco preguntas.',
 'Ejercitar la observación y la memoria visual, además de la capacidad de compartir detalles.',
 'Se presenta un tablero con 12 objetos durante 30 segundos.\nAl terminar la observación el tablero se oculta.\nEl equipo responde cinco preguntas sobre los objetos, su posición o sus características.\nSin capturas de pantalla: se mide memoria visual.',
 '20 puntos por respuesta correcta. Máximo 100.',
 'Concentración, memoria de trabajo, descripción precisa y colaboración',
 '👁','#14b8a6',100,8),
(3,'codigo-secreto',3,'Código Secreto',
 'Tres acertijos que abren cerraduras sucesivas hasta el código final.',
 'Practicar el razonamiento lógico y la resolución colaborativa de problemas.',
 'Tres acertijos que desbloquean cerraduras o etapas sucesivas.\nCada acertijo tiene una respuesta verificable y una pista opcional.\nSe debe completar el acertijo actual antes de avanzar al siguiente.',
 '30 puntos por acertijo sin pista; 20 si usa pista; 0 si no se resuelve. Máximo 90.',
 'Lógica, persistencia, escucha y división de tareas',
 '🔒','#8b5cf6',90,9),
(4,'creatividad-sin-limites',4,'Creatividad Sin Límites',
 'Propuesta de un producto innovador evaluada con una rúbrica pública.',
 'Generar una idea nueva y explicar cómo aporta valor a un usuario o a una organización.',
 'El equipo propone un producto o servicio mediante un formulario.\nIncluye nombre, problema que resuelve, descripción, público objetivo y beneficio.\nImagen opcional o dibujo rápido.\nRúbrica conocida de antemano: Originalidad 40 · Utilidad 35 · Claridad 25.',
 'Rúbrica de 100 puntos evaluada por el facilitador (idealmente dos evaluadores que promedian).',
 'Creatividad, comunicación, orientación al usuario y pensamiento práctico',
 '💡','#f59e0b',100,11),
(5,'desafio-relampago',5,'Desafío Relámpago',
 'Seis desafíos de respuesta rápida, con un ejercicio de toma de decisiones.',
 'Decidir con rapidez sin perder de vista la calidad de la respuesta.',
 'Seis desafíos breves, incluyendo al menos un ejercicio de toma de decisiones.\nCada desafío tiene un límite de 1 minuto visible.\nEl capitán envía una sola respuesta por desafío.\nSe considera exactitud y, cuando aplique, justificación.',
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
