<?php
/**
 * Created by PhpStorm.
 * User: birjemin
 * Date: 30/03/2018
 * Time: 1:11 PM
 */
namespace Birjemin\Blueprint\Console\Command;

use Illuminate\Routing\Router;
use ReflectionClass;
use Birjemin\Blueprint\Writer;
use Illuminate\Support\Arr;
use Birjemin\Blueprint\Blueprint;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class Docs extends Command
{
    /**
     * Router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The blueprint instance.
     *
     * @var \Birjemin\Blueprint\Blueprint
     */
    protected $blueprint;

    /**
     * Blueprint instance.
     *
     * @var \Birjemin\Blueprint\Blueprint
     */
    protected $docs;

    /**
     * Writer instance.
     *
     * @var \Birjemin\Blueprint\Writer
     */
    protected $writer;

    /**
     * Default documentation name.
     *
     * @var string
     */
    protected $name;

    /**
     * Default documentation version.
     *
     * @var string
     */
    protected $version;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birjemin:docs {--name= : Name of the generated documentation}
                                     {--use-version= : Version of the documentation to be generated}
                                     {--output-file= : Output the generated documentation to a file}
                                     {--include-path= : Path where included documentation files are located}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API documentation from annotated controllers';

    /**
     * Create a new docs command instance.
     *q
     * @param Router $router
     * @param Blueprint $blueprint
     * @param Writer $writer
     * @param $name
     * @param $version
     */
    public function __construct(Router $router, Blueprint $blueprint, Writer $writer, $name, $version)
    {
        parent::__construct();

        $this->router = $router;
        $this->blueprint = $blueprint;
        $this->writer = $writer;
        $this->name = $name;
        $this->version = $version;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $contents = $this->blueprint->generate($this->getControllers(), $this->getDocName(), $this->getVersion(), $this->getIncludePath());
        if ($file = $this->option('output-file')) {
            $this->writer->write($contents, $file);
            return $this->info('Documentation was generated successfully.');
        }

        return $this->line($contents);
    }

    /**
     * Get the documentation name.
     *
     * @return string
     */
    protected function getDocName()
    {
        $name = $this->option('name') ?: $this->name;

        if (! $name) {
            $this->comment('A name for the documentation was not supplied. Use the --name option or set a default in the configuration.');

            exit;
        }

        return $name;
    }

    /**
     * Get the include path for documentation files.
     *
     * @return string
     */
    protected function getIncludePath()
    {
        return base_path($this->option('include-path'));
    }

    /**
     * Get the documentation version.
     *
     * @return string
     */
    protected function getVersion()
    {
        $version = $this->option('use-version') ?: $this->version;

        if (! $version) {
            $this->comment('A version for the documentation was not supplied. Use the --use-version option or set a default in the configuration.');

            exit;
        }

        return $version;
    }

    /**
     * Get all the controller instances.
     *
     * @return array
     */
    protected function getControllers()
    {
        $controllers = new Collection;

        foreach ($this->router->getRoutes() as $collection) {
            if ($this->filterController($collection) && $controller = $collection->getController()) {
                $this->addControllerIfNotExists($controllers, $controller);
            }
        }
        return $controllers;
    }

    /**
     * Illuminate\Routing\Route getActionName方法获取方法名称，如果是回调函数,返回:Closure
     *
     * @param RouteCollection $collection
     *
     * @return bool
     */
    protected function filterController($collection)
    {
        return strpos($collection->getActionName(), '@') ? true : false;
    }

    /**
     * Add a controller to the collection if it does not exist. If the
     * controller implements an interface suffixed with "Docs" it
     * will be used instead of the controller.
     *
     * @param \Illuminate\Support\Collection $controllers
     * @param object                         $controller
     *
     * @return void
     */
    protected function addControllerIfNotExists(Collection $controllers, $controller)
    {
        $class = get_class($controller);

        if ($controllers->has($class)) {
            return;
        }

        $reflection = new ReflectionClass($controller);

        $interface = Arr::first($reflection->getInterfaces(), function ($key, $value) {
            return ends_with($key, 'Docs');
        });

        if ($interface) {
            $controller = $interface;
        }

        $controllers->put($class, $controller);
    }
}
