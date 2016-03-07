<?php
namespace TheCodingMachine\Laravel;

use Illuminate\Support\ServiceProvider;
use Puli\Discovery\Api\Discovery;
use Puli\Repository\Api\ResourceRepository;
use Puli\UrlGenerator\Api\UrlGenerator;

class PuliServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    private $alreadyRegistered = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->alreadyRegistered) {
            return;
        }
        $this->alreadyRegistered = true;

        $this->app->singleton(PULI_FACTORY_CLASS, function($app) {
            $factoryClass = PULI_FACTORY_CLASS;
            return new $factoryClass();
        });

        $this->app->singleton(ResourceRepository::class, function($app) {
            $factory = $app->make(PULI_FACTORY_CLASS);
            return $factory->createRepository();
        });

        $this->app->singleton(Discovery::class, function($app) {
            $factory = $app->make(PULI_FACTORY_CLASS);
            return $factory->createDiscovery($app->make(ResourceRepository::class));
        });

        $this->app->singleton(UrlGenerator::class, function($app) {
            $factory = $app->make(PULI_FACTORY_CLASS);
            return $factory->createUrlGenerator($app->make(Discovery::class));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        // The provides method is ALSO registering the services because those services can be used by
        // provides methods of other services down the hill.
        $this->register();

        return [PULI_FACTORY_CLASS, ResourceRepository::class, Discovery::class, UrlGenerator::class];
    }
}
