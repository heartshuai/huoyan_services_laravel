<?php

namespace Huoyan\Services;

use Illuminate\Support\ServiceProvider;
class ServicesProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerServices();

    }

    /**
     * Register config.
     */
    protected function configure()
    {

        $this->mergeConfigFrom(
            __DIR__.'/../config/huoyan_services.php', 'huoyan_services'
        );

    }

    /**
     * Setup the resource publishing groups for huoyan_services.
     *
     * @return void
     */
    protected function offerPublishing()
    {

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/huoyan_services.php' => config_path('huoyan_services.php'),
            ], 'huoyan_services_config');
        }
    }

    /**
     * Register huoyan_services services in the container.
     *
     * @return void
     */
    protected function registerServices()
    {
        $config=$this->app->make('config')->get('huoyan_services');
        foreach ($config as $groupKey=>$groupVal){
            foreach ($groupVal as $key=>$val){

                $this->app->singleton("huoyan_services.$groupKey.$key", function ($app) use($val,$key){
                    $val['controller']=$key;
                    return new Services($val);
                });
            }
        }

    }
}
