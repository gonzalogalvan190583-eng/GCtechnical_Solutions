# Panel de solicitudes

El panel privado está en `/admin/`. El sitio público conserva HTML, CSS y JavaScript; PHP recibe las solicitudes y MySQL las almacena. No se requiere SMTP: **Responder en Outlook** abre un correo nuevo en la aplicación predeterminada con el destinatario, el asunto `Re: ...` y un texto inicial. El envío se confirma manualmente desde el panel.

## Requisitos

- PHP 8.3 o superior con extensiones `pdo_mysql` y `mbstring`.
- MySQL 8.
- Servidor web configurado para ejecutar PHP y usar HTTPS en producción.

## Configuración

En producción, define estas variables de entorno para el proceso PHP:

- `GC_DB_HOST`, `GC_DB_NAME`, `GC_DB_USER`, `GC_DB_PASSWORD`
- `GC_APP_KEY`: secreto aleatorio de al menos 32 caracteres, por ejemplo `bin2hex(random_bytes(32))`.
- `GC_TIMEZONE`: opcional; por defecto `America/Santiago`.

### Desarrollo local en Windows

El panel necesita un servidor PHP y una base de datos MySQL en ejecución. Abrir `index.html` directamente en el navegador o usar una vista previa de archivos estáticos no ejecuta PHP. El repositorio incluye un inicio local para Windows:

#### Ejecutar desde la terminal con XAMPP

Abre PowerShell en la carpeta del proyecto y agrega temporalmente PHP de XAMPP al `PATH`:

```powershell
cd C:\gctech_solutions\GCtechnical_Solutions
$env:Path = "C:\xampp\php;" + $env:Path
```

Inicia MySQL y después ejecuta el servidor integrado con PHP:

```powershell
C:\xampp\mysql_start.bat
php -S 127.0.0.1:8000
```

Abre `http://127.0.0.1:8000/` en el navegador. Para detener el servidor PHP presiona `Ctrl+C`. Para detener MySQL:

```powershell
C:\xampp\mysql_stop.bat
```

#### Ejecutar con el script incluido

Desde la carpeta del proyecto, inicia el servidor y las comprobaciones de configuración:

```powershell
cd C:\gctech_solutions\GCtechnical_Solutions
$env:Path = "C:\xampp\php;" + $env:Path
powershell -NoProfile -ExecutionPolicy Bypass -File .\start-local.ps1
```

El script inicia PHP de forma independiente y abre el sitio en `http://127.0.0.1:8000/`. Para detenerlo:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\stop-local.ps1
```

#### Ejecutar desde VS Code

Abre la carpeta del proyecto en VS Code y presiona `F5`. También puedes ejecutar desde la paleta de comandos la tarea **Iniciar servidor local GC**. La configuración abre automáticamente `http://127.0.0.1:8000/` en Microsoft Edge.

1. Instala PHP 8.3 o superior y MySQL 8; PHP debe estar en `PATH` y tener habilitadas `pdo_mysql` y `mbstring`.
2. Ejecuta `powershell -NoProfile -ExecutionPolicy Bypass -File .\start-local.ps1` desde la carpeta del proyecto. La primera vez se crea `.env` local con una clave privada aleatoria.
3. Completa en `.env` las credenciales de una base de datos MySQL. Si necesitas crear una base y un usuario local, puedes ejecutar este SQL en MySQL Workbench:

   ```sql
   CREATE DATABASE gc_technical_solutions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'gc_app'@'127.0.0.1' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE';
   GRANT ALL PRIVILEGES ON gc_technical_solutions.* TO 'gc_app'@'127.0.0.1';
   ```

   Usa esa base, usuario y contraseña en `.env`; cambia `CAMBIA_ESTA_CLAVE` por una contraseña propia. Ejecuta `admin/schema.sql` en la base recién creada una sola vez.
4. Vuelve a ejecutar el comando. El inicio verifica las extensiones PHP, la conexión a MySQL y las tablas antes de iniciar el servidor.
5. Crea la cuenta inicial desde la carpeta del proyecto con `php admin/create-admin.php` y sigue las instrucciones para establecer la contraseña.
6. Abre `http://127.0.0.1:8000/admin/login.php`. En VS Code también puedes iniciar la configuración **Abrir Mi Web en Microsoft Edge** con F5.

El servidor se inicia como un proceso independiente, por lo que cerrar VS Code no lo detiene. Para apagarlo usa `powershell -NoProfile -ExecutionPolicy Bypass -File .\stop-local.ps1`. MySQL debe seguir ejecutándose; el servidor PHP no almacena las cuentas ni las solicitudes.

`.env` y los registros/PID locales están excluidos de Git. Al traer cambios con Git, la configuración y los datos de MySQL de esa instalación se conservan. En un clon nuevo, la configuración y la base de datos no se descargan desde GitHub: configura `.env`, crea la base de datos, ejecuta el esquema y crea el administrador una vez. No subas `.env` ni respaldos con datos al repositorio.

### Instalación manual (servidor existente o producción)

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
