<?php
namespace Amerhendy\Setup;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
class AmerSetup extends ServiceProvider
{
    public $startcomm="Amer";
    protected $commands = [
        App\Console\Commands\Install::class,
        App\Console\Commands\AddCustomRouteContent::class,
        App\Console\Commands\Addmenu::class,
        App\Console\Commands\Addpermession::class,
        App\Console\Commands\amer::class,
        App\Console\Commands\guards::class,
        App\Console\Commands\seed::class,
        App\Console\Commands\migrate::class,
    ];
    protected $defer = false;
    public function register(): void
    {
        $this->commands($this->commands);
        $this->loadroutes($this->app->router);
        $this->loadviewfiles();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

    }
    public function loadroutes(Router $router)
    {
        //$path=base_path('vendor/AmerHendy/Setup/src/Route/Route.php');
            //$this->loadRoutesFrom($path);
    }
    function loadviewfiles() {
        $basefiles=base_path("vendor/AmerHendy/Setup/src/View");    
        if (file_exists($basefiles)) {
            $this->loadViewsFrom($basefiles, 'SetUp');
        }
        $this->loadViewsFrom($basefiles, 'SetUp');
    }
}
