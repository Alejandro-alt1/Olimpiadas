-- =====================================================================
--  OLIMPIADAS VIRTUALES CORPORATIVAS · FASE 4 · Torneo por enfrentamientos (sistema suizo)
--  Ejecutar DESPUÉS de database_fase3.sql. Se puede repetir: reinicia el torneo
--  (rondas y enfrentamientos). No toca equipos, participantes ni respuestas.
-- =====================================================================
USE olimpiadas;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS enfrentamientos, rondas;
SET FOREIGN_KEY_CHECKS = 1;

-- Cada ronda la decide un reto (ronda 1 = Mentes Brillantes, etc.) o, si no hay reto, el facilitador
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
  puntos_a   SMALLINT UNSIGNED NULL,          -- puntos del reto de cada equipo al cerrar la ronda
  puntos_b   SMALLINT UNSIGNED NULL,
  ganador_id INT UNSIGNED NULL,
  empate     TINYINT(1) NOT NULL DEFAULT 0,
  estado     ENUM('pendiente','jugado') NOT NULL DEFAULT 'pendiente',
  manual     TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = el facilitador fijó el resultado
  nota       VARCHAR(255) NULL,
  UNIQUE KEY uq_ronda_a (ronda_id, equipo_a),
  UNIQUE KEY uq_ronda_b (ronda_id, equipo_b),
  FOREIGN KEY (ronda_id)   REFERENCES rondas(id)  ON DELETE CASCADE,
  FOREIGN KEY (equipo_a)   REFERENCES equipos(id) ON DELETE CASCADE,
  FOREIGN KEY (equipo_b)   REFERENCES equipos(id) ON DELETE CASCADE,
  FOREIGN KEY (ganador_id) REFERENCES equipos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Configuración del torneo
INSERT INTO configuracion (clave, valor) VALUES
  ('torneo_activo', '0'),
  ('torneo_rondas', '5'),
  ('torneo_primera', 'predefinido')   -- 'predefinido' (1-6, 2-7, 3-8…) o 'sorteo'
ON DUPLICATE KEY UPDATE valor = valor;
