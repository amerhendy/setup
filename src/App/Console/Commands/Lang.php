<?php

namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
class Lang extends Command
{
    use Traits\PrettyCommandOutput;
    protected $progressBar;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Amer:lang
    {--T|type=} {--N|name=} {--L|langs=*} {--S|sync} {--C|clear} {--P|path=}
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
    protected $description = 'publish Language files For Amer Object';
    public function __construct(){
        $this->sourcePath=config('amer.package_path');
        parent::__construct();
    }
    public function handle()
    {
        if($this->option('force')){$this->force='--force';}
        if (! $name = $this->option('name')) {
            $name = $this->ask('name');
        }
        if (! $path = $this->option('path')) {
            $path = $this->ask('path');
        }
        if(Str::contains('\\',$path)){$path=Str::replace('\\','/',$path);}
        $this->progressBlock('checking Amer Language Folders');
        foreach(File::directories(lang_path('vendor\Amer')) as $a=>$b){
            $newdir=$b.'\\'.Str::beforeLast($path,'/');
            if(!File::Exists($newdir)){
                $this->progressBlock('creating Amer Language Folders');
                File::makeDirectory($newdir,0755,true,$this->force);
                $this->closeProgressBlock();
            }
            $filepath=$newdir."\\".$name.".php";
                $this->progressBlock('creating '.$name.' Language File');
                File::put($filepath,$this->filecontent($name));
                $this->closeProgressBlock();
        }
        $this->closeProgressBlock();
        $dir='';
        $this->line('name: '.$name);
        $this->line('path'.$path);

        //  Amer/Amerhendies.php

    }
    public function filecontent($name){
        $content=
'<?php
/************************
 *  Created By Amer Hendy Language Consol For Laravel
 *      EGYPT -2023
 *     '.$name.'
 * 
 * **********************/
return [
    \''.$name.'\'=>\''.$name.'\',
    \'singular\'=>\''.Str::singular($name).'\',
    \'plural\'=>\''.Str::plural($name).'\',
    \'create\'=>\''.Str::singular($name).'\',
    \'edit\'=>\''.Str::singular($name).'\',
];
';
        return $content;
    }
}
