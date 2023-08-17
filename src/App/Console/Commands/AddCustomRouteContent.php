<?php
namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use AmerHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Illuminate\Support\Facades\Route;
class AddCustomRouteContent extends Command
{
    protected $name = 'Amer:AddCustomRoute';
    protected $signature = 'Amer:AddCustomRoute
    {--code}
    {--path}
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : set force replace';
    protected $description ="AddCustomRoute";
    public $after;
    public $before;
    public $class;
    public $force=false;
    use Traits\PrettyCommandOutput;
    function doyoucreatewebphp($io,$input){
        $this->infoBlock('Routes/web.php file not exists');
        $this->infoBlock('we Can Create This File '.base_path('routes/web.php'));
        $input->setArgument(
            'path',
            $io->choice('Are you sure to create The file?',['yes','no'])
        );
    }
    function chooseroutefile($io,$input){}
    function createwebroutes(){
        $this->publishfiles(base_path('\vendor\AmerHendy\Setup\src\App\Console\stubs\webroute.stub'),base_path('routes/web.php'));
    }
    function choosepath($routefiles){
        if (! $path = $this->option('path')) {
            $path=$this->radiooptions('<fg=white>Please choose your Route File</>',$routefiles,0);
            //$path = $this->ask('write Route File Path','/routes/Amer/admin.php');
        }
        if(is_numeric($path)){
            $path=(int) $path;
            if(array_key_exists($path,$routefiles)){
                return $routefiles[$path];
            }else{
                return $this->choosepath($routefiles);
            }
        }
        if(in_array($path,$routefiles)){
            //C:\laragon\www\lotfy\loginsystem_Copy\employment\routes\web.php
            foreach($routefiles as $a=>$b){
                if($path == $b){return $b;}
            }
        }else{
            return $this->choosepath($routefiles);
        }
        return $this->choosepath($routefiles);
    }
    function afterroute($path){
        $after=$this->radiooptions('<fg=white>Do yo want add yor code after any part of file</>',['yes','no']);
        if($after == 'no' || $after == '1' || $after == 1){
            $this->after=false;
            return ;
        }
        $after=$this->ask("please paste the after code");
        if(empty($after) || $after == null){$this->after=false;return;}
        $lines=\File::lines($path)->toArray();
        $lcon=$this->getLastLineNumberThatContains($after,$lines);
        if($lcon == false){
            $this->line('');
            $this->errorBlock('code not found in route file');
            return $this->afterroute($path);
        }
        $this->after=[$after,$lcon];
        return;
    }
    function beforeroute($path){
        $before=$this->radiooptions('<fg=white>Do yo want add yor code before any part of file</>',['yes','no']);
        if($before == 'no' || $before == '1' || $before == 1){
            $this->before=false;
            return ;
        }
        $before=$this->ask("please paste the before code");
        if(empty($before) || $before == null){$this->before=false;return;}
        $lines=\File::lines($path)->toArray();
        $lcon=$this->getLastLineNumberThatContains($before,$lines);
        if($lcon == false){
            $this->line('');
            $this->errorBlock('code not found in route file');
            return $this->beforeroute($path);
        }
        $this->before   =[$before,$lcon];
        return;
    }
    function checkclass($path) {
        $class=$this->radiooptions('<fg=white>Do yo want add class to the Route?</>',['yes','no']);
        if($class == 'no' || $class == '1' || $class == 1){
            $this->class=false;
            return ;
        }
        $class=$this->ask("please write the full class name with nameSpace");
        $routes=\Illuminate\Support\Facades\Route::getRoutes();
        $routelist=[];
        foreach($routes as $a=>$b){
            if($b->getActionName()!=='Closure'){
                $bsr=$b->getController();
                $controller=$bsr::class;
            }else{
                $controller='';
            }
            if(!isset($b->action['as'])){
                $b->action['as']=null;
            }
            $routelist[]=[
                'methods'=>$b->methods,
                'as'=>$b->action['as'],
                'uri'=>$b->uri,
                'actionMethod'=>$b->getActionMethod(),
                'controller'=>$controller
            ];
        }
        if(!count($routelist)){$this->class=false;return;}
        if(Str::contains($class,'/')){
            $class=Str::replace('/','\\',$class);
        }
        $routelist=Arr::where($routelist,function($v,$k)use($class){
            return (string) $v['controller'] == $class;
        });
        if(count($routelist)){
            $this->errorBlock("route Class exists Before, Please choose another controller");
            return $this->checkclass($path);
        }
        return;
    }
    function checkclassName($path) {
        $class=$this->ask("please write the full class Name");
        $routes=\Illuminate\Support\Facades\Route::getRoutes();
        $routelist=[];
        foreach($routes as $a=>$b){
            if($b->getActionName()!=='Closure'){
                $bsr=$b->getController();
                $controller=$bsr::class;
            }else{
                $controller='';
            }
            if(!isset($b->action['as'])){
                $b->action['as']=null;
            }
            $routelist[]=[
                'methods'=>$b->methods,
                'as'=>$b->action['as'],
                'uri'=>$b->uri,
                'actionMethod'=>$b->getActionMethod(),
                'controller'=>$controller
            ];
        }
        if(!count($routelist)){$this->class=false;return;}
        $routelist=Arr::where($routelist,function($v,$k)use($class){
            return (string) $v['as'] == $class;
        });
        if(count($routelist)){
            $this->errorBlock("route Name exists Before, Please choose another Name");
            return $this->checkclassName($path);
        }
        return;
    }
    public function handle(){
        if($this->option('force')){$this->force='--force';}
        //check routefiles
        $routefiles=$this->checkservice();
        if(!count($routefiles)){
            $createweb=$this->radiooptions('do woy want to create routes/web.php?',['yes','no']);
            if($createweb == 'no'){exit();}
            $this->createwebroutes();
            $routefiles[]=base_path('routes/web.php');
        }else{
            if(!in_array(base_path('routes\web.php'),$routefiles)){
                $createweb=$this->radiooptions('<fg=white>do woy want to create routes/web.php?</>',['yes','no']);
                if($createweb == 'no' || $createweb == '1' ||$createweb == 1){
                }else{
                    $this->createwebroutes();
                    $routefiles[]=base_path('routes/web.php');
                }
                if($createweb == 'no'){exit();}
                $this->createwebroutes();
                $routefiles[]=base_path('routes/web.php');
            }
        }
        $this->box('Installing Custom Route For Amer Packages');
        $path=$this->choosepath($routefiles);
        $this->afterroute($path);
        $this->beforeroute($path);
        $this->checkclass($path);
        $this->checkclassName($path);
        if (! $code = $this->option('code')) {
            $code = $this->ask('write code insid Route File',"Route::Amer('Employment_Amasas','Admin\Employment_AmasasAmerController::class');");
        }
        $this->addroutedata($path,$code);
    }
    public function addroutedata($rootpath,$code){
        $this->progressBlock("Adding route to <fg=blue>$rootpath</>");
        if(\File::exists($rootpath) == false){
            $this->errorBlock("Route file ".$rootpath." not found please fix the package");
            exit();
        }
        $old_file_path = $rootpath;
        $file_lines = file($old_file_path, FILE_IGNORE_NEW_LINES);
        if($this->after == false && $this->before == false){
            $file_lines[count($file_lines)]=$code;
        }
        if($this->after == false && $this->before !== false){
            $file_lines = array_merge(array_slice($file_lines, 0, $this->before[1]), array($code), array_slice($file_lines, $this->before[1]));
        }
        if($this->after !== false && $this->before == false){
            $file_lines = array_merge(array_slice($file_lines, 0, $this->after[1]+1), array($code), array_slice($file_lines, $this->after[1]+1));
        }
        if($this->after !== false && $this->before !== false){
            $file_lines = array_merge(array_slice($file_lines, 0, $this->after[1]+1), array($code), array_slice($file_lines, $this->after[1]+1));
        }
        $new_file_content = implode(PHP_EOL, $file_lines);
        if (\File::put($rootpath, $new_file_content) == false) {
            $this->errorProgressBlock();
            $this->note('Could not write to file.', 'red');
            return;
        }
        $this->executeArtisanProcess('route:cache');
        $this->infoBlock('Adding Route Done,"Success');
    }
    public function checkservice(){
        $routefiles=[];
        $routefiles['web']=base_path('routes\web.php');
        $routefiles['console']=base_path('routes\console.php');
        $routefiles['channels']=base_path('routes\channels.php');
        $routefiles['api']=base_path('routes\api.php');
        $routefiles['Amer\Admin']=base_path('routes\Amer\Admin.php');
        $routefiles['Amer\employer']=base_path('routes\Amer\employer.php');
        $routefiles['Amer\employment']=base_path('routes\Amer\employment.php');
        $routefiles['Amer\Public']=base_path('routes\Amer\Public.php');
        $routefiles['Amer\AmerRoute']=config('amer.package_path').'route\AmerRoute.php';
        $routefiles['Amer\AmerRoute']=config('amer.package_path').'route\api.php';
        $routefiles['Employers\Routes']=config('employers.package_path').'route\Routes.php';
        $routefiles['Security\EmploymentRoute']=config('amerSecurity.package_path').'route\EmploymentRoute.php';
        $return=[];
        foreach ($routefiles as $key => $value) {
            if(\File::exists($value) === true){
                $return[]=$value;
            }
        }
        //$return=[];
        return $return;
    }
}
?>