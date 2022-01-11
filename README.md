[![Laravel](https://github.com/ivalrivall/cargo/actions/workflows/laravel.yml/badge.svg)](https://github.com/ivalrivall/cargo/actions/workflows/laravel.yml)

## Installation Fresh Project
```
composer install
php artisan migrate:fresh
php artisan migrate --path=/database/migrations/alter
php artisan migrate --path=/database/migrations/drop
php artisan migrate --path=/database/migrations/main
php artisan db:seed
php artisan laravolt:indonesia:seed
php artisan passport:install --force
```

## Default Password
```
driver: driver123
user: user1234
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
