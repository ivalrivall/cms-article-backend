[![Laravel](https://github.com/ivalrivall/cms-article-backend/actions/workflows/laravel.yml/badge.svg)](https://github.com/ivalrivall/cms-article-backend/actions/workflows/laravel.yml)

## Installation Fresh Project
```
composer install
php artisan migrate:fresh
php artisan migrate --path=/database/migrations/main
php artisan migrate --path=/database/migrations/alter
php artisan db:seed
php artisan passport:install --force
```

## Default Credential
```
username: admin
pass: admin1234
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
