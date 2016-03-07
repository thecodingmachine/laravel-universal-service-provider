<?php

namespace TheCodingMachine\Laravel;

use Acclimate\Container\CompositeContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Puli\Discovery\Api\Discovery;
use Puli\Discovery\Binding\ClassBinding;
use Simplex\Container;

class ContainerInteropBridgeServiceProvider extends ServiceProvider
{
    /**
     * @var Container
     */
    private $simplex;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

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
     * @return Container
     * @throws ServiceProviderBridgeException
     */
    private function getSimplex()
    {
        if ($this->simplex) {
            return $this->simplex;
        }

        $serviceProviders = [];

        $enablePuli = config('app.container-interop-service-provider-enable-puli', true);

        if ($enablePuli) {
            try {
                $discovery = $this->app->make(Discovery::class);
            } catch (BindingResolutionException $e) {
                throw new ServiceProviderBridgeException('Could not instantiate Puli discovery. Did you think about adding the PuliDiscovery service provider to the list of Laravel service providers?', 0, $e);
            }

            $bindings = $discovery->findBindings('container-interop/service-provider');

            foreach ($bindings as $binding) {
                if ($binding instanceof ClassBinding) {
                    $serviceProviders[] = $binding->getClassName();
                }
            }
        }

        $manualServiceProviders = config('app.container-interop-service-providers', []);

        $serviceProviders = array_merge($serviceProviders, $manualServiceProviders);

        $rootContainer = new CompositeContainer();

        $laravelContainerAdapter = new LaravelContainerAdapter($this->app);

        $this->simplex = new Container([], $rootContainer);

        $rootContainer->addContainer($laravelContainerAdapter);
        $rootContainer->addContainer($this->simplex);


        foreach ($serviceProviders as $serviceProvider) {
            if (!is_string($serviceProvider) || !class_exists($serviceProvider)) {
                throw new ServiceProviderBridgeException('Error in parameter "app.container-interop-service-providers" or in Puli binding: providers should be fully qualified class names. Invalid class name passed: "'.$serviceProvider.'"');
            }

            $this->simplex->register($serviceProvider);
        }

        return $this->simplex;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $simplex = $this->getSimplex();

        foreach ($simplex->keys() as $identifier) {
            $this->app->singleton($identifier, function($app) use ($identifier, $simplex) {
                return $simplex->get($identifier);
            });
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        $simplex = $this->getSimplex();
        return $simplex->keys();
    }
}
