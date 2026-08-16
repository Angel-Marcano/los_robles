# Queue Worker — Los Robles

## Configuración actual

El sistema usa `QUEUE_CONNECTION=database` (configurado en `.env`).
Los jobs (emails, notificaciones) se guardan en la tabla `jobs` de cada tenant.

## Cómo funciona

1. Cuando el código llama a `Mail::to(...)->queue(...)` o `dispatch(new Job)`,
   el job se guarda en la tabla `jobs` de la BD del tenant.
2. Un proceso worker (`php artisan queue:work`) lee esa tabla y ejecuta los jobs.
3. Si un job falla, se guarda en `failed_jobs` para revisión.

## En desarrollo (Laragon)

Abrir una terminal y dejar corriendo:

```bash
cd c:\laragon\www\los_robles
php artisan queue:work
```

Para detener: `Ctrl+C`

## En producción (Linux/VPS)

### Opción 1: Supervisor (recomendado)

Instalar supervisor:
```bash
sudo apt install supervisor
```

Crear config:
```bash
sudo nano /etc/supervisor/conf.d/losrobles-worker.conf
```

Contenido:
```ini
[program:losrobles-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/losrobles/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/losrobles/storage/logs/worker.log
stopwaitsecs=3600
```

Activar:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start losrobles-worker:*
```

### Opción 2: systemd

```bash
sudo nano /etc/systemd/system/losrobles-worker.service
```

```ini
[Unit]
Description=Los Robles Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/usr/bin/php /var/www/losrobles/artisan queue:work --sleep=3 --tries=3
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Activar:
```bash
sudo systemctl daemon-reload
sudo systemctl enable losrobles-worker
sudo systemctl start losrobles-worker
```

## Comandos útiles

- `php artisan queue:work` — inicia el worker (mantiene corriendo)
- `php artisan queue:work --once` — procesa un solo job y sale
- `php artisan queue:restart` — reinicia todos los workers después del job actual
- `php artisan queue:failed` — lista jobs fallidos
- `php artisan queue:retry all` — reintenta todos los jobs fallidos
- `php artisan queue:flush` — borra todos los jobs fallidos

## Nota multi-tenant

Como cada tenant tiene su propia BD, el worker necesita saber qué tenant procesar.
El sistema usa `IdentifyCondominium` en requests web, pero el worker CLI no tiene host.

Solución: el worker procesa la conexión `tenant` que esté configurada.
Para producción, se recomienda un worker por tenant o usar un comando personalizado
que itere sobre los condominios activos.