

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
