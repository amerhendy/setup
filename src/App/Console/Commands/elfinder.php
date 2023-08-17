<?php

namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
class elfinder extends Command
{
    use Traits\PrettyCommandOutput;
    protected $progressBar;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Amer:elfinder
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : set force replace
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    public $sourcePath='';
    public $force=false;
    protected $description = 'publish elfinder data for Amer';
    public function __construct(){
        $this->sourcePath=config('amer.package_path');
        parent::__construct();
    }
    public function handle()
    {
        if($this->option('force')){$this->force=true;}
        $prov=$this->getLaravel()->getLoadedProviders();
        $this->progressBlock('Installing laravel-elfinder for Amer');
        foreach($prov as $a=>$b){
            if(Str::contains($a,'ElfinderServiceProvider')){
                $provresult='installed';
                break;
            }else{
                $provresult='not installed';
            }
        }
        if($provresult == 'not installed'){
            $process = new Process(['composer', 'require', '--dev', 'barryvdh/laravel-elfinder']);
            $process->setTimeout(300);
            $process->run();
            $this->closeProgressBlock();
        }else{
            $this->closeProgressBlock();
        }
        $this->progressBlock('Creating uploads directory:');
        switch (DIRECTORY_SEPARATOR) {
            case '/': // unix
                $createUploadDirectoryCommand = ['mkdir', '-p', 'public/uploads'];
                break;
            case '\\': // windows
                if (! file_exists('public\uploads')) {
                    $createUploadDirectoryCommand = ['mkdir', 'public\uploads'];
                }
                break;
        }
        if (isset($createUploadDirectoryCommand)) {
            $this->executeProcess($createUploadDirectoryCommand);
            $this->closeProgressBlock();
        }else{
            $this->closeProgressBlock();
        }
        $this->progressBlock('publish Config:');
            $this->publishfiles("/config/elfinder.php",config_path('elfinder.php'));
            if (! Config::get('elfinder.route.prefix')) {
                Config::set('elfinder.route.prefix', Config::get('amer.route_prefix').'/elfinder');
            }
            
        $this->closeProgressBlock();
        $this->progressBlock('publish assets:');
            $this->publishfiles("/public/js/packages/barryvdh/elfinder",config('amer.public_path').'/js/packages/barryvdh/elfinder');
        $this->closeProgressBlock();
        $this->progressBlock('publish Views:');
            $this->publishfiles('/resources/views/views',resource_path('views/vendor/elfinder'));
        $this->closeProgressBlock();
    }
}
