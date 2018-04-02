<?php
/**
 * Created by PhpStorm.
 * User: birjemin
 * Date: 30/03/2018
 * Time: 1:11 PM
 */

namespace Birjemin\Blueprint\Provider;


use Birjemin\Blueprint\Console\Command\Docs;
use Illuminate\Support\ServiceProvider;

/**
 * Class BlueprintServiceProvider
 * @package Birjemin\Blueprint\Provider
 */
class BlueprintServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerDocsCommand();

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.blueprint.doc',
        ];
    }

    /**
     * Register the documentation command.
     *
     * @return void
     */
    protected function registerDocsCommand()
    {
        $this->app->singleton('command.blueprint.doc', function ($app) {
            return new Docs(
                $app['Illuminate\Routing\Router'],
                $app['Birjemin\Blueprint\Blueprint'],
                $app['Birjemin\Blueprint\Writer'],
                'apiDefaultDoc',
                '0.0.1'
            );
        });

        $this->commands(['command.blueprint.doc']);
    }
}
