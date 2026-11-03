# Panel de solicitudes

El panel privado está en `/admin/`. El sitio público conserva HTML, CSS y JavaScript; PHP recibe las solicitudes y MySQL las almacena. No se requiere SMTP: **Responder en Outlook** abre un correo nuevo en la aplicación predeterminada con el destinatario, el asunto `Re: ...` y un texto inicial. El envío se confirma manualmente desde el panel.

## Requisitos

- PHP 8.3 o superior con extensiones `pdo_mysql` y `mbstring`.
- MySQL 8.
- Servidor web configurado para ejecutar PHP y usar HTTPS en producción.

## Configuración

Define estas variables de entorno para el proceso PHP:

- `GC_DB_HOST`, `GC_DB_NAME`, `GC_DB_USER`, `GC_DB_PASSWORD`
- `GC_APP_KEY`: secreto aleatorio de al menos 32 caracteres, por ejemplo `bin2hex(random_bytes(32))`.
- `GC_TIMEZONE`: opcional; por defecto `America/Santiago`.

1. Crea una base de datos MySQL con codificación `utf8mb4`.
2. Ejecuta `admin/schema.sql` en esa base de datos.
3. Configura las variables anteriores en el entorno de PHP; no guardes claves en el repositorio ni en archivos públicos.
4. Desde la raíz del proyecto ejecuta `php admin/create-admin.php`. El comando crea el primer administrador y genera una contraseña temporal aleatoria que se muestra una sola vez.
5. Ingresa a `/admin/login.php` y cambia la contraseña. El primer acceso exige este cambio.

El comando de creación inicial solo funciona en CLI y no crea otra cuenta si ya hay un administrador. No existe registro público. El usuario administrador puede crear, editar, restablecer contraseñas y eliminar cuentas; el nivel 1 puede crear otras cuentas de nivel 1, pero no editar ni eliminar cuentas.

## Gestión diaria

El panel abre las solicitudes del día actual en orden cronológico. El calendario lineal muestra los días de cada mes con su día de la semana, permite cambiar de mes o seleccionar una fecha y señala feriados nacionales chilenos recurrentes y Semana Santa. Los feriados regionales y los feriados extraordinarios no están incluidos. Una solicitud puede marcarse como pendiente, respondida o realizada. Al eliminar cuentas, se conserva la solicitud y se limpia la referencia al usuario responsable.

Las cuentas nuevas reciben una contraseña aleatoria temporal que se muestra una sola vez en pantalla y deben cambiarla en el primer ingreso. Las contraseñas se guardan usando `password_hash()`; las nuevas contraseñas deben tener al menos 12 caracteres y un máximo de 72 bytes.

## Puesta en producción

Configura el directorio del proyecto para que el servidor ejecute los `.php` y habilita HTTPS. Limita la ejecución CLI de `admin/create-admin.php` al operador del servidor, protege los respaldos de la base de datos y aplica la política de retención de datos personales del negocio. La base de datos se guarda en UTC y se presenta en la zona horaria configurada.
