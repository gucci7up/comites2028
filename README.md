# Sistema de Gestión de Comités Afectivos

## Requisitos

- XAMPP (Apache, MySQL, PHP)
- Navegador web moderno

## Instalación

1. Clone o descargue este repositorio en la carpeta `htdocs` de XAMPP (generalmente en `C:\xampp\htdocs\sandro`).
2. Asegúrese de que los servicios de Apache y MySQL estén iniciados en el panel de control de XAMPP.
3. Acceda al sistema a través de su navegador web: `http://localhost/sandro/`

## Configuración de la Base de Datos

Al acceder por primera vez, el sistema detectará que la base de datos no existe y le ofrecerá dos opciones:

### Opción 1: Configuración Automática

1. Haga clic en el botón "Ejecutar configuración automática" en la página de configuración.
2. El sistema creará automáticamente la base de datos y todas las tablas necesarias.

### Opción 2: Configuración Manual

1. Abra phpMyAdmin (generalmente en `http://localhost/phpmyadmin`).
2. Cree una nueva base de datos llamada `comites_prm`.
3. Seleccione la base de datos recién creada.
4. Vaya a la pestaña "Importar".
5. Seleccione el archivo `db/comites_prm.sql` de este proyecto.
6. Haga clic en "Continuar" para importar la estructura de la base de datos.

## Acceso al Sistema

Una vez configurada la base de datos, puede acceder al sistema con las siguientes credenciales predeterminadas:

- **Usuario:** admin
- **Contraseña:** admin123

## Estructura del Sistema

- **Login:** Autenticación de usuarios.
- **Dashboard:** Panel principal con estadísticas y accesos rápidos.
- **Comités:** Gestión de comités afectivos.
- **Miembros:** Gestión de miembros de comités.
- **Coordinadores:** Gestión de coordinadores de comités.
- **Consultas:** Búsqueda y consulta de información.
- **Usuarios:** Administración de usuarios del sistema (solo para administradores).

## Solución de Problemas

### Error: Base de datos no encontrada

Si recibe un error indicando que la base de datos `comites_prm` no existe, siga las instrucciones en la página de configuración que aparecerá automáticamente.

### Error: Función no definida

Si recibe un error de función no definida, asegúrese de que todos los archivos del sistema estén correctamente instalados y que no falte ningún archivo en la estructura del proyecto.

### Error: Acceso denegado

Si recibe un error de acceso denegado a la base de datos, verifique que las credenciales en el archivo `db/config.php` coincidan con su configuración de MySQL en XAMPP.