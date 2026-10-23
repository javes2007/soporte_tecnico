

CREATE TABLE IF NOT EXISTS reportes (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  cliente   VARCHAR(100) NOT NULL,
  telefono  VARCHAR(20)  NULL,
  equipo    VARCHAR(30)  NOT NULL,
  problema  TEXT         NOT NULL,
  prioridad VARCHAR(10)  NOT NULL DEFAULT 'Media',
  estado    VARCHAR(20)  NOT NULL DEFAULT 'Pendiente',
  fecha     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO reportes (cliente, telefono, equipo, problema, prioridad, estado) VALUES
('María Pérez',   '3001234567', 'Portátil',    'No enciende después de una descarga eléctrica.',      'Alta',  'En reparación'),
('Carlos Gómez',  '3109876543', 'Computador',  'Muy lento y se reinicia solo al abrir programas.',    'Media', 'Pendiente'),
('Laura Díaz',    '3205558899', 'Impresora',   'Imprime con rayas negras en las hojas.',              'Baja',  'Resuelto'),
('Andrés Torres', '3152223344', 'Celular',     'La pantalla está rota y no responde al tacto.',       'Alta',  'Pendiente');

-- Historial de movimientos de los reportes. En el sitio también se crea
-- automáticamente si la tabla todavía no existe.
CREATE TABLE IF NOT EXISTS historial_reportes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reporte_id INT NOT NULL,
  accion VARCHAR(30) NOT NULL,
  cliente VARCHAR(100) NOT NULL,
  telefono VARCHAR(20) NULL,
  equipo VARCHAR(30) NOT NULL,
  problema TEXT NOT NULL,
  prioridad VARCHAR(10) NOT NULL,
  estado VARCHAR(20) NOT NULL,
  detalle TEXT NULL,
  fecha_evento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_historial_reporte (reporte_id),
  INDEX idx_historial_fecha (fecha_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
