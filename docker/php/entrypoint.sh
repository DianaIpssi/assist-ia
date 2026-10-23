#!/bin/sh
set -e

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f bin/console ]; then
    php bin/console cache:clear --no-warmup || true
    php bin/console doctrine:migrations:migrate --no-interaction || true
    # Données fictives : toujours en dev, en prod seulement si LOAD_DEMO_DATA=1 (démonstration)
    if [ "${APP_ENV:-dev}" != "prod" ] || [ "${LOAD_DEMO_DATA:-0}" = "1" ]; then
        php bin/console app:load-demo-data --no-interaction || true
    fi
    php bin/console app:ensure-availability-slots --no-interaction || true
    php bin/console app:purge-chat-messages --no-interaction || true

    if [ "${APP_ENV:-dev}" = "prod" ]; then
        php bin/console asset-map:compile || true
    else
        rm -rf public/assets
    fi
fi

exec "$@"
