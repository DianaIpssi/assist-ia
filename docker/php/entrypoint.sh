set -e

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ -f bin/console ]; then
    php bin/console cache:clear --no-warmup || true
    php bin/console doctrine:migrations:migrate --no-interaction || true
    php bin/console app:load-demo-data --no-interaction || true
    php bin/console app:ensure-availability-slots --no-interaction || true
fi

exec "$@"
