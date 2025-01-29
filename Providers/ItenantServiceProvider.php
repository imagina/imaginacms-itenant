<?php

namespace Modules\Itenant\Providers;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Traits\CanPublishConfiguration;
use Modules\Core\Events\BuildingSidebar;
use Modules\Core\Events\LoadingBackendTranslations;
use Modules\Itenant\Listeners\RegisterItenantSidebar;

class ItenantServiceProvider extends ServiceProvider
{
    use CanPublishConfiguration;
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBindings();
        $this->app['events']->listen(BuildingSidebar::class, RegisterItenantSidebar::class);

        $this->app['events']->listen(LoadingBackendTranslations::class, function (LoadingBackendTranslations $event) {
            // append translations
        });


    }

    public function boot()
    {
       
        $this->publishConfig('itenant', 'config');
        $this->publishConfig('itenant', 'crud-fields');

        $this->mergeConfigFrom($this->getModuleConfigFilePath('itenant', 'settings'), "asgard.itenant.settings");
        $this->mergeConfigFrom($this->getModuleConfigFilePath('itenant', 'settings-fields'), "asgard.itenant.settings-fields");
        $this->mergeConfigFrom($this->getModuleConfigFilePath('itenant', 'permissions'), "asgard.itenant.permissions");

        //$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

    private function registerBindings()
    {
        $this->app->bind(
            'Modules\Itenant\Repositories\DomainRepository',
            function () {
                $repository = new \Modules\Itenant\Repositories\Eloquent\EloquentDomainRepository(new \Modules\Itenant\Entities\Domain());

                if (! config('app.cache')) {
                    return $repository;
                }

                return new \Modules\Itenant\Repositories\Cache\CacheDomainDecorator($repository);
            }
        );
        $this->app->bind(
            'Modules\Itenant\Repositories\OrganizationRepository',
            function () {
                $repository = new \Modules\Itenant\Repositories\Eloquent\EloquentOrganizationRepository(new \Modules\Itenant\Entities\Organization());

                if (! config('app.cache')) {
                    return $repository;
                }

                return new \Modules\Itenant\Repositories\Cache\CacheOrganizationDecorator($repository);
            }
        );
        $this->app->bind(
            'Modules\Itenant\Repositories\UserOrganizationRepository',
            function () {
                $repository = new \Modules\Itenant\Repositories\Eloquent\EloquentUserOrganizationRepository(new \Modules\Itenant\Entities\UserOrganization());

                if (! config('app.cache')) {
                    return $repository;
                }

                return new \Modules\Itenant\Repositories\Cache\CacheUserOrganizationDecorator($repository);
            }
        );
        $this->app->bind(
            'Modules\Itenant\Repositories\CategoryRepository',
            function () {
                $repository = new \Modules\Itenant\Repositories\Eloquent\EloquentCategoryRepository(new \Modules\Itenant\Entities\Category());

                if (! config('app.cache')) {
                    return $repository;
                }

                return new \Modules\Itenant\Repositories\Cache\CacheCategoryDecorator($repository);
            }
        );
// add bindings




    }


}
