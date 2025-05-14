<a name="top"># VY - Network Attach Storage Proyect <a> 

Este proyecto documenta el proceso de transformar un **Samsung Galaxy S8** en un **servidor web autónomo** y **sistema de gestión de archivos (similar a un NAS)** utilizando **Alpine Linux**. Una solución portátil y de bajo consumo para alojar sitios web y gestionar archivos de forma remota a través de una interfaz web, aprovechando la capacidad de procesamiento y almacenamiento de dispositivos moviles.

## Características Principales

* **Servidor Web Funcional:** Alojamiento de sitios web y aplicaciones dinámicas (PHP).
* **Base de Datos MariaDB:** Almacenamiento de datos estructurados para aplicaciones y la gestión de usuarios.
* **Acceso Remoto Seguro:** Acceso vía SSH y configuración de DDNS para acceso desde Internet [VilyionNAS](http://vilyion.sytes.net/).
* **Portabilidad:** Aprovecha el formato compacto y la capacidad de batería del dispositivo móvil.
* **Base Minimalista (Alpine Linux):** Sistema operativo ligero y enfocado en la seguridad.

## Tecnologías Utilizadas

* **Samsung Galaxy S8:** El hardware base para nuestro servidor portátil.
* **[PostmarketOS](https://postmarketos.org/):** Una imagen de kernel modificada para flashear Alpine Linux. 
* **[Alpine Linux](https://www.alpinelinux.org/):** Una distribución Linux muy ligera y segura, ideal para sistemas empotrados que nos proporciona el entorno operativo.
* **[lighttpd](https://www.lighttpd.net/):** Un servidor web rápido, ligero y de bajo consumo, adecuado para el hardware del dispositivo.
* **PHP:** El lenguaje de programación del lado del servidor necesario para ejecutar aplicaciones dinámicas como FileGator.
* **MariaDB:** Un popular sistema de gestión de bases de datos relacionales, usado para almacenar datos de aplicaciones y usuarios.
* **SSH:** Protocolo seguro para acceso remoto a la línea de comandos del dispositivo.
* **/sys/class/power_supply/:** Interfaz del kernel Linux para acceder a información de la batería y fuentes de energía (para monitoreo avanzado).

## Proceso de Instalación y Configuración

Aquí se detalla el camino seguido para configurar el Galaxy S8 como servidor VilyionNAS:

### 1. Preparación del Dispositivo (Galaxy S8)

Este paso implica reemplazar el sistema operativo Android por una distribución Linux completa.

1.  **Desbloquear el Bootloader:** Habilitar la opción "OEM Unlock" en las opciones de desarrollador del dispositivo. **Advertencia:** Esto anulará la garantía y borrará todos los datos del teléfono.
2.  **Instalar un Recovery Personalizado (TWRP):** TWRP (Team Win Recovery Project) es un recovery alternativo que permite flashear imágenes de sistema no oficiales.
    * Descargar la imagen de TWRP compatible con tu modelo exacto de Galaxy S8.
    * Flashear TWRP usando herramientas como Odin en Windows o `heimdall` en Linux.
3.  **Flashear Alpine Linux:** Obtener una imagen de Alpine Linux compatible con la arquitectura ARM del Galaxy S8 y flashearla usando TWRP. El método exacto de flasheo puede variar según la imagen de Alpine utilizada.
4.  **Primer Arranque en Alpine Linux:** El dispositivo arrancará en Alpine. Inicialmente, el acceso podría ser a través de ADB o Termux (si la imagen lo incluye) hasta configurar el acceso SSH.

### 2. Configuración Inicial de Alpine Linux y Acceso Remoto

Una vez en Alpine, se configuran los aspectos básicos del sistema.

1.  **Acceso Inicial:** Conectarse al dispositivo usando ADB (si está habilitado) o a través de una terminal en el dispositivo (como Termux si la imagen lo permite).
2.  **Configuración de Red:** Configurar la conexión de red (WiFi, Ethernet por USB si es posible). El script bash `wifi_manager.sh` desarrollado en este proyecto ayuda a gestionar conexiones WiFi predefinidas o nuevas.
3.  **Instalar Servidor SSH:** Instalar y configurar un servidor SSH (como OpenSSH) para acceso remoto seguro a la línea de comandos.
    ```bash
    sudo apk add openssh
    sudo rc-service sshd start
    sudo rc-update add sshd default # Para iniciar automáticamente al arrancar
    ```
4.  **Acceso vía SSH:** Conectarse al dispositivo desde otra computadora usando un cliente SSH.

### 3. Instalación y Configuración del Entorno Web y Base de Datos

Aquí se instalan los componentes necesarios para el servidor web y la base de datos.

1.  **Instalar lighttpd, PHP y MariaDB:** Utilizar el gestor de paquetes `apk` de Alpine para instalar los servicios web y de base de datos.
    ```bash
    apk add lighttpd php php-mysqli php-curl php-json php-iconv php-dom php-xml php-zip php-gd php-mbstring php-openssl mariadb mariadb-client
    # Se pueden necesitar otras extensiones de PHP dependiendo de FileGator
    ```
    * **lighttpd:** Servidor web que servirá los archivos de la página y FileGator.
    * **PHP:** Intérprete para ejecutar los scripts de FileGator y la página web. Se instalan varias extensiones comunes (`php-mysqli`, `php-curl`, etc.) necesarias para FileGator.
    * **MariaDB:** El servidor de base de datos para almacenar datos.

2.  **Configurar lighttpd:** Editar el archivo de configuración principal de lighttpd (`/etc/lighttpd/lighttpd.conf`).
    * Configurar `server.document-root` para tu página web principal si la tienes.
    * Habilitar el módulo `mod_cgi` para ejecutar scripts PHP.
    * Configurar un `alias.url` para que lighttpd sepa dónde encontrar los archivos de FileGator (por ejemplo, en `/home/cesar/dirsaveserver/filegator/`).
    * Usar un bloque `$HTTP["url"]` con `cgi.assign` para indicar a lighttpd que procese los archivos `.php` dentro del alias de FileGator usando `php-cgi`. Asegúrate de que la expresión regular del alias es correcta (ej. `^/filegator/`).

    ```lighttpd
    # ... en la sección server.modules ...
    server.modules = (
        # ...
        "mod_cgi",
        # ...
        "mod_alias",
        # ...
    )

    # ... en la configuración global o Virtual Host ...
    server.document-root = "/ruta/a/la/raiz/de/tu/web" # Si tienes una página principal

    alias.url = (
        "/filegator/" => "/home/cesar/dirsaveserver/filegator/" # Ruta absoluta a la instalación de FileGator
    )

    $HTTP["url"] =~ "^/filegator/" { # Asegúrate del slash al final
        cgi.assign = (
            ".php" => "/usr/bin/php-cgi" # Ruta al ejecutable PHP-CGI
        )
    }

    index-file.names = ("index.php", "index.html", ...) # Asegúrate de que index.php esté aquí

    # ... otras configuraciones ...
    ```

3.  **Configurar PHP (`php.ini`):** Editar el archivo `php.ini` utilizado por `php-cgi`.
    * Asegurar que `log_errors = On` y `error_log = /ruta/a/tu/php_error.log` estén configurados y la ruta del log sea escribible por el usuario `lighttpd`. Esto es crucial para diagnosticar errores 500.

4.  **Configurar y Arrancar MariaDB:**
    * Inicializar el directorio de datos de MariaDB si es necesario.
    * Arrancar el servicio de MariaDB.
    ```bash
    # Puede variar según la instalación de Alpine/MariaDB
    mysql_install_db --basedir=/usr --datadir=/var/lib/mysql
    rc-service mariadb start
    rc-update add mariadb default # Para iniciar automáticamente al arrancar
    ```
    * Ejecutar el script de seguridad inicial de MariaDB (`mysql_secure_installation`) para establecer la contraseña de root y asegurar la instalación básica.
    * **Crear una base de datos para FileGator:** Conectarse como root y crear la base de datos (ej. `db_filegator`).
    * **Crear un usuario de base de datos dedicado para FileGator:** Crear un usuario con una contraseña segura (`adminweb`) y otorgarle los permisos necesarios (`SELECT`, `INSERT`, `UPDATE`, `CREATE`, `DROP`, etc.) en la base de datos de FileGator.


### 6. Gestión de Usuarios e Integración (Conceptos)

* **Fuente de Verdad de Usuarios:** En este setup, FileGator se convierte en la fuente principal de gestión de usuarios. Sus tablas en la base de datos MariaDB contienen la información de los usuarios.
* **Integración con Página Web Existente:** Para que tu página web principal reconozca a los usuarios registrados en FileGator, debes modificar el código PHP de tu página (especialmente la lógica de login y verificación de sesión) para que consulte la tabla de usuarios de FileGator en la base de datos MariaDB (verificando nombre de usuario y contraseña hasheada) en lugar de tu tabla de usuarios original.

## Gestión de Archivos y Almacenamiento

* **Directorio de Almacenamiento Seguro:** Configura FileGator para que almacene los archivos en un directorio en el sistema de archivos del dispositivo que **no sea directamente accesible** por lighttpd a través de `server.document-root` o `alias.url`. La ruta configurada en `configuration.php` debe ser absoluta.
* **Organización por Usuario (Requiere Customización):** Para que cada usuario tenga su propio espacio de almacenamiento y no pueda ver los archivos de otros, probablemente necesitarás modificar el código de FileGator. Esto implica adaptar el adaptador de almacenamiento (`Filesystem`) o la lógica de manejo de rutas para que trabajen con subdirectorios por usuario (por ejemplo, `/ruta/segura/almacenamiento/usuario_ID/`).
* **Límites de Almacenamiento por Usuario (Requiere Customización):** La funcionalidad para limitar el espacio de almacenamiento por usuario basándose en un valor en la base de datos requiere modificar el código de FileGator. Debes añadir lógica en el proceso de subida de archivos para verificar el espacio usado por el usuario actual (posiblemente almacenado en la tabla de usuarios de FileGator) contra su límite configurado antes de permitir la subida.

## Monitoreo de Batería (Exploración)

El dispositivo móvil tiene una batería, lo que añade una dimensión interesante. La información sobre el nivel y estado de carga de la batería está expuesta a través del sistema de archivos `/sys/class/power_supply/`.

* **Acceso a Información:** En este dispositivo específico, la información se encontró en rutas como `/sys/class/power_supply/ac/device/power_supply/battery/`. Archivos como `capacity` (nivel en %) y `status` (Charging, Discharging, Full) contienen los datos relevantes.
* **Posible Control de Carga (Avanzado y Específico del Dispositivo):** La posibilidad de controlar el inicio/detención de la carga basándose en el nivel de la batería (ej. detener al 80%, reanudar al 20%) es altamente dependiente del hardware y si el kernel expone una interfaz de control (archivos como `charge_control`, `charging_enabled`, etc.) en `/sys/class/power_supply/` o directorios relacionados. Implementar esto requiere investigar la interfaz específica de tu dispositivo y escribir un script de monitoreo en segundo plano.

## Mejoras Futuras Potenciales

* Implementar completamente la organización de archivos y límites de almacenamiento por usuario en FileGator.
* Desarrollar un script de monitoreo de batería en segundo plano y mostrarla como función en la web. 
* Configurar otros protocolos de acceso a archivos (SFTP, WebDAV si no está integrado en FileGator, SMB con Samba si los recursos lo permiten).
* Integrar soluciones de sincronización de archivos (ej. Syncthing) o backup.
* Configurar un servicio de DNS dinámico (como DuckDNS) para acceso desde Internet.

## Contribuir

## Licencia

---
