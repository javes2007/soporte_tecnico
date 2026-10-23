# Soporte Técnico

Sistema PHP + MySQL para registrar equipos recibidos en reparación y seguir su estado.

## Funciones iniciales
- Registrar equipos y datos del cliente.
- Consultar reportes por código, teléfono o cliente.
- Cambiar el estado del equipo.
- Guardar historial de cambios de estado con fecha y observación.
- Ver qué equipos están pendientes, en reparación, listos o entregados.

## Requisitos
- PHP 8.1 o superior con extensión MySQLi.
- MySQL/MariaDB.
- Servidor web Apache o compatible.

## Instalación
1. Crear una base de datos MySQL.
2. Importar `database/schema.sql` en esa base de datos.
3. Copiar `config.example.php` como `config.php` y poner los datos reales de conexión.
4. Subir los archivos a la carpeta pública del hosting.
5. Abrir el sitio en el navegador.

**Importante:** no publiques `config.php`, contraseñas, tokens ni datos reales de clientes. `config.php` está excluido por `.gitignore`.

## Estado del proyecto
Esta es una base inicial creada en GitHub porque el repositorio estaba vacío. Todavía no reemplaza ni modifica los archivos que estén funcionando en el hosting. Antes de integrar esta base con la instalación existente, se deben recuperar y revisar los archivos actuales y confirmar el esquema de base de datos para proteger los registros existentes.
