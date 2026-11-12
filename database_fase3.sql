-- =====================================================================
--  OLIMPIADAS VIRTUALES CORPORATIVAS · FASE 3 (versión final)
--  Cada persona inicia sesión sola (código del equipo + alias), juega a su ritmo
--  y el puntaje del equipo es el PROMEDIO de los puntajes individuales.
--  Ejecutar DESPUÉS de database.sql y database_fase2.sql.
--  ⚠ Borra respuestas/participantes de PRUEBA (equipos, preguntas, acertijos
--    y propuestas con sus evaluaciones NO se tocan).
-- =====================================================================
USE olimpiadas;

SET FOREIGN_KEY_CHECKS = 0;
-- Restos de la versión "en vivo" (si la habías ejecutado)
DROP TABLE IF EXISTS resultados_pregunta, respuestas_ind, juego;
-- Tablas que pasan de ser por equipo a ser por persona
DROP TABLE IF EXISTS borrador_listos, borradores, intentos_codigo, respuestas, progreso_pregunta, sesiones, participantes;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE participantes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  equipo_id  INT UNSIGNED NOT NULL,
  alias      VARCHAR(30) NOT NULL,            -- único dentro del equipo (sin distinguir mayúsculas/acentos)
  token      CHAR(64)    NOT NULL,            -- identifica el navegador de la persona
  last_seen  DATETIME    NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_equipo_alias (equipo_id, alias),
  KEY ix_equipo_seen (equipo_id, last_seen),
  FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Memoria Extrema: observación y envío de cada persona
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

-- Cronómetro individual por pregunta (retos 1 y 5)
CREATE TABLE progreso_pregunta (
  participante_id INT UNSIGNED NOT NULL,
  pregunta_id     INT UNSIGNED NOT NULL,
  inicio_at       DATETIME NOT NULL,
  PRIMARY KEY (participante_id, pregunta_id),
  FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
  FOREIGN KEY (pregunta_id)     REFERENCES preguntas(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- Respuestas individuales (una por persona y pregunta)
CREATE TABLE respuestas (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  participante_id INT UNSIGNED NOT NULL,
  equipo_id       INT UNSIGNED NOT NULL,
  reto_id         TINYINT UNSIGNED NOT NULL,
  pregunta_id     INT UNSIGNED NOT NULL,
  respuesta       CHAR(1) NULL,                 -- NULL = no enviada / tiempo agotado
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

-- Código Secreto: avance de cada persona
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

-- Creatividad Sin Límites: borrador compartido del equipo + "listo" de cada persona
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

-- Base del promedio: 'declarados' (integrantes declarados o registrados, el mayor) | 'registrados'
INSERT INTO configuracion (clave, valor) VALUES ('promedio_base', 'declarados')
  ON DUPLICATE KEY UPDATE valor = valor;

-- Los retos 1, 2, 3 y 5 se recalculan con la regla del promedio: se ponen en cero los datos de prueba
UPDATE puntuaciones SET puntos = 0, detalle = NULL WHERE reto_id <> 4;
