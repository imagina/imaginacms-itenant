# imaginacms-itenant 

## Install
```bash
composer require imagina/itenant-module=v10.x-dev
```

## Enable the module
```bash
php artisan module:enable Itenant
```

## Seeder

```bash
php artisan module:seed Itenant
```

## Commands

### All Modules (Examples)
```bash
php artisan tenants:run module:list --tenants="1
```

```bash
php artisan tenants:run module:migrate --tenants="1"
```

```bash
php artisan tenants:run module:seed --tenants="1"
```

### Specific Module (Example: run seed to specific module in this tenant)
```bash
php artisan tenant:run module:seed --argument="module=moduleName" --tenants="1"
```
