<?php
namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use AmerHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class createrequest extends GeneratorCommand
{
    protected $name = 'Amer:Request';
    //protected $signature = 'Amer:model {name} {table}';
    protected $signature = 'Amer:Request {name}';
    protected $description ="Create Request";
    protected $type = 'Request';

    protected function getPath($name)
    {
        $name = str_replace($this->laravel->getNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'Request.php';
    }
    protected function getStub()
    {return __DIR__.'/../stubs/request.stub';}
    protected function getOptions()
    {
        return [

        ];
    }
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Requests';
    }

}
?>