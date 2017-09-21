<?php

namespace TheCodingMachine\Laravel;

use Acclimate\Container\CompositeContainer;
use Illuminate\Support\ServiceProvider;
use Interop\Container\ServiceProviderInterface;
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

        $enableDiscovery = config('app.container-interop-service-provider-enable-discovery', true);

        if ($enableDiscovery) {
            $discovery = \TheCodingMachine\Discovery\Discovery::getInstance();

            $serviceProviders = $discovery->get(ServiceProviderInterface::class);
        }

        $manualServiceProviders = config('app.container-interop-service-providers', []);

        $serviceProviders = array_merge($serviceProviders, $manualServiceProviders);

        $rootContainer = new CompositeContainer();

        $laravelContainerAdapter = new LaravelContainerAdapter($this->app);

        $this->simplex = new Container([], $rootContainer);

        $rootContainer->addContainer($laravelContainerAdapter);
        $rootContainer->addContainer($this->simplex);


        foreach ($serviceProviders as $serviceProvider) {
            if ($serviceProvider instanceof \Interop\Container\ServiceProviderInterface) {
                $this->simplex->register($serviceProvider);
            } elseif (!is_string($serviceProvider) || !class_exists($serviceProvider)) {
                throw new ServiceProviderBridgeException('Error in parameter "app.container-interop-service-providers" or in thecodingmachine/discovery configuration: providers should be an instance of \Interop\Container\ServiceProviderInterface or a fully qualified class name. Invalid class name passed: "'.$serviceProvider.'"');
            } else {
                $this->simplex->register(new $serviceProvider);
            }
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
