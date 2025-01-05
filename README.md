# Configuración de Laravel Reverb en Producción

Este proyecto utiliza Laravel y Reverb para gestionar la comunicación en tiempo real a través de WebSockets. A continuación se describen los pasos necesarios para configurar Laravel con Reverb en un entorno de producción.

## Requisitos

Asegúrate de tener los siguientes programas y servicios instalados en tu servidor:

- [PHP 8.2](https://www.php.net/)
- [Apache](https://httpd.apache.org/)
- [Supervisor](http://supervisord.org/)
- [Let's Encrypt](https://letsencrypt.org/) para SSL

## Pasos de Configuración

### 1. Apache
#### * Configuración del SSL

Abre el archivo de configuración de SSL en tu servidor Apache y agrega o modifica lo siguiente:

    <IfModule mod_ssl.c>
    <VirtualHost *:443>
        ServerAdmin webmaster2@localhost
        ServerName proyectos.desarrollo.com.co
        DocumentRoot /var/www/proyectos-ds/frontend/dist
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

    <FilesMatch \.php$>
            SetHandler "proxy:unix:/var/run/php/php8.3-fpm.sock|fcgi://localhost"
        </FilesMatch>
        
        SSLEngine on
        SSLCertificateFile /etc/letsencrypt/live/proyectos.desarrollo.com.co/fullchain.pem
        SSLCertificateKeyFile /etc/letsencrypt/live/proyectos.desarrollo.com.co/privkey.pem

        #Proxy WebSocket connections (importante para reverb)
        ProxyPass /app ws://0.0.0.0:8080/app
        ProxyPassReverse /app ws://0.0.0.0:8080/app
        ProxyPass /apps http://0.0.0.0:8080/apps
        ProxyPassReverse /apps http://0.0.0.0:8080/apps

    Include /etc/letsencrypt/options-ssl-apache.conf
    </VirtualHost>
    <Directory /var/www/proyectos-ds/frontend/dist>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted

        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            RewriteRule ^index\\.html$ - [L]
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule . /index.html [L]
        </IfModule>
    </Directory>
    </IfModule> 
#### * Reiniciar Apache
    sudo systemctl restart apache2

### 2. Supervisor
#### * Instalar Supervisor en el servidor

    sudo apt-get install supervisor: 
#### * Crear Archivo de Configuración de Supervisor
Dentro del directorio /etc/supervisor/conf.d/, crea un archivo de configuración para el trabajador de Laravel y Reverb. Ejemplo de archivo laravel-worker-dsGestion.conf:

    [program:laravel-worker-dsGestion-queue]
    process_name=%(program_name)s_%(process_num)02d
    command=php8.2 /var/www/proyectos-ds/backend/artisan queue:work
    autostart=true
    autorestart=true
    user=root
    numprocs=1
    redirect_stderr=true
    stdout_logfile=/var/www/proyectos-ds/backend/storage/logs/laravel-worker/queue.log

    [program:laravel-worker-dsGestion-reverb]
    process_name=%(program_name)s_%(process_num)02d
    command=php8.2 /var/www/proyectos-ds/backend/artisan reverb:start
    autostart=true
    autorestart=true
    user=root
    numprocs=1
    redirect_stderr=true
    stdout_logfile=/var/www/proyectos-ds/backend/storage/logs/laravel-worker/reverb.log

#### * Ejecutar Supervisor
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start laravel-worker:*

### 3. Configuración del archivo .env en Laravel

    BROADCAST_CONNECTION=reverb
    QUEUE_CONNECTION=sync
    REVERB_APP_ID=948232
    REVERB_APP_KEY=xy0noyro3mg1g5qgtc8m
    REVERB_APP_SECRET=zilp38c0wwsqvo4c6uxh
    REVERB_HOST=proyectos.desarrollo.com.co
    REVERB_PORT=443
    REVERB_SCHEME=https

### 4. Configuración del archivo .env en Vue.js

    VITE_REVERB_APP_KEY=xy0noyro3mg1g5qgtc8m
    VITE_REVERB_HOST="proyectos.desarrollo.com.co"
    VITE_REVERB_PORT="433"
    VITE_REVERB_SCHEME="https"


## Enlaces de Referencia

### 1. **Documentación Oficial de Laravel sobre Reverb**
Si deseas aprender más sobre cómo integrar Reverb en tu aplicación Laravel, puedes consultar la [documentación oficial de Laravel sobre Reverb](https://laravel.com/docs/11.x/reverb).

### 2. **Guía de Implementación de Laravel Reverb con Sanctum y WebSockets**
Para configurar Reverb con Laravel Sanctum y WebSockets en una SPA (Single Page Application), consulta este artículo detallado de Medium:  
[How to Use Laravel Reverb with Sanctum & Websockets for API & SPA](https://medium.com/@datascale/how-to-use-laravel-reverb-with-sanctum-websockets-for-api-spa-e1391f9843be).

### 3. **Configuración de Reverb en Producción con Apache**
Si estás desplegando tu aplicación Laravel en un servidor Apache, puedes encontrar una guía útil para configurar Reverb en producción en este artículo de StackOverflow:  
[How to Set Up Laravel Reverb in a Production with Apache Server](https://stackoverflow.com/questions/78679529/how-to-set-up-laravel-reverb-in-a-production-with-apache-server).

### 4. **Uso de Supervisor con Laravel**
Para manejar procesos en segundo plano, como los trabajadores de Laravel, puedes usar Supervisor. Este artículo de Medium te explica cómo hacerlo:  
[How to Use Supervisord for Your Laravel Application](https://medium.com/@danielarcher/how-to-use-supervisord-for-your-laravel-application-66015f104703).
