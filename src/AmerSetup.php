<?php
namespace Amerhendy\Setup;
use Illuminate\Support\ServiceProvider;

class AmerSetup extends ServiceProvider
{
    public $startcomm="Amer";
    protected $commands = [
        \Amerhendy\Setup\App\Console\Commands\Install::class,
        \Amerhendy\Setup\app\Console\Commands\AddCustomRouteContent::class,
        \Amerhendy\Setup\app\Console\Commands\Addmenu::class,
        \Amerhendy\Setup\app\Console\Commands\Addpermession::class,
        \Amerhendy\Setup\App\Console\Commands\amer::class,
        \Amerhendy\Setup\App\Console\Commands\guards::class,
        
        
        
    ];
    protected $defer = false;
    public function register(): void
    {
        $this->commands($this->commands);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

    }

}
