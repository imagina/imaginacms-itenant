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

### Update Tenants DB (Recommended after globla composer update)

#### All tenants

```bash
php artisan itenant:module-db-update
```
#### Update Specific Tenants

```bash
php artisan itenant:module-db-update --tenants="2,3"
```

