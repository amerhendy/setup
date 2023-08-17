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
use Illuminate\Support\Facades\File;
class createmodel extends GeneratorCommand
{
    protected $name = 'Amer:model';
    //protected $signature = 'Amer:model {name} {table}';
    protected $signature = 'Amer:model {type : Type} {name : Name}
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : set force replace
    ';
    public $modeltypes= [
        1=>['name'=>'Admin','namespace'=>'App/Models/Admin'],
        2=>['name'=>'Employer','namespace'=>'App/Models/Employer'],
        3=>['name'=>'Employment','namespace'=>'App/Models/Employment'],
        4=>['name'=>'Public','namespace'=>'App/Models'],
        0=>['name'=>'Root','namespace'=>'Amerhendy/Employment/App/Models'],
    ];  
    public $classinfo=[];
    protected $description ="Create Model";
    protected $type = 'Model';
    protected $AmerTrait = 'Amerhendy\Employment\App\Models\Traits\AmerTrait';
    use Traits\PrettyCommandOutput;
    protected function interact(InputInterface $input, OutputInterface $output)
    {
            $modeltypes=Arr::map($this->modeltypes,function($v,$k){return $v['name'];});
            //$type = $this->choice('What is your object type',$modeltypes);
        if ( ! $input->getArgument('type')) {
            $input->setArgument('type', $this->choice('What is your object type',$modeltypes));
        }
        //"Amerhendy\Employment\App\Models\CityAmers"
        if ( ! $input->getArgument('name')) {
            $input->setArgument('name', $this->ask('Name ?'));
        }
        
    }
    public function construct(){
        foreach($this->modeltypes as $a=>$b){
            if($a == 0){$this->modeltypes[$a]['src']=config('amer.package_path').'App\\Models';}
            elseif($a==1){$this->modeltypes[$a]['src']=base_path().'\\App\\Models\\Admin';}
            elseif($a==2){$this->modeltypes[$a]['src']=base_path().'\\App\\Models\\Employer';}
            elseif($a==3){$this->modeltypes[$a]['src']=base_path().'\\App\\Models\\Employment';}
            elseif($a==4){$this->modeltypes[$a]['src']=base_path().'\\App\\Models';}
        }
    }
    public function handle(){
        $this->construct();
        $typeres=$this->argument('type');
        $modeltypes=Arr::map($this->modeltypes,function($v,$k){return $v['name'];});
        $modesdty=Arr::where($modeltypes,function($v,$k)use($typeres){return $v==$typeres;});
        $type=array_key_last($modesdty);
        $name=$this->argument('name');
        $this->classinfo=[
            'NameSpace'=>$this->modeltypes[$type]['namespace'],
            'className'=>$name,
            'classSrc'=>$this->modeltypes[$type]['src'].'\\'.$name.'.php',
            'classDir'=>$this->modeltypes[$type]['src'],
            'type'=>$type
        ];
        $this->setplusnamespace();
        $classDir=$this->classinfo['classDir'];
        $namespaceModels=Str::replace('/','\\',$this->classinfo['NameSpace'].'/'.$this->classinfo['className']);
        $this->line('check Directory');
        $checkdir=$this->checkandcreatDir($classDir);
        if((is_array($checkdir)) && in_array('error',$checkdir)){
            $this->errorBlock($checkdir[1]);exit();
        }
        $use=$this->buildClass($namespaceModels);
        $Alphabeticallysorts=$this->sortImports($use);
        $destinationclassPath=$this->classinfo["classSrc"];
        $this->files->put($destinationclassPath, $Alphabeticallysorts);
        $this->infoBlock($this->type.' created successfully.');
    }
    protected function getStub()
    {return __DIR__.'/../stubs/model-admin.stub';}
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceTable($stub, $name)->replaceClass($stub, $name);
    }
    protected function replaceTable(&$stub, $name)
    {
        $name = ltrim(preg_replace('/[A-Z]/', '_$0', str_replace($this->getNamespace($name).'\\', '', $name)), '_');
        $table=$name;
        $stub = str_replace('DummyTable', $table, $stub);
        return $this;
    }
    public function checkandcreatDir($classDir){
        if(Str::endsWith($classDir, '\\')){$classDir=Str::beforeLast($classDir,'\\');}
        if(Str::endsWith($classDir, '/')){$classDir=Str::beforeLast($classDir,'/');}      
        if(File::exists($classDir)){
            $this->line('Directory Exists');
            if(!File::isDirectory($classDir)){
                return ['error',$classDir.' is file not Directory'];
            }
        }else{
            $this->line('Creating Directory');
            if(File::isFile($classDir)){
                return ['error','there file with the Same Path Name please remove it '.$classDir];
            }
            if(!File::makeDirectory($classDir,0755,true,true)){
                
            }
        }
    }
    function setplusnamespace(){
        $name=$this->classinfo["className"];
        $type=$this->classinfo['type'];
        $namespace=Str::replace('/','\\',$this->classinfo['NameSpace'])."\\";
        if(Str::contains($name,'/')){$name=Str::replace('/','\\',$name);}
        if(Str::contains($name,'\\')){
            $newname=Str::afterLast($name,'\\');
            $name=Str::beforeLast($name,$newname);
            if($namespace  == $name){
                $this->classinfo["NameSpace"]=$namespace;
                $this->classinfo["className"]=$newname;
                $this->classinfo["classDir"]=$this->modeltypes[$type]['src'];
                $this->classinfo["classSrc"]=$this->modeltypes[$type]['src']."\\".$newname.".php";
                $plusnamespace="";
            }elseif(Str::contains($name,$namespace)){
                // name=AmerHendy/Employment/App/Models/Admin/Base/Teams
                //namespace=App/Models/Admin/
                if(!Str::startswith($name,$namespace)){
                    $confirm=$this->confirm('this model <bg=green>"'.$name.'"</> will be inside <bg=gray>"'.$namespace.'"</>',true);
                    if($confirm == false){$this->errorBlock("Please Write model Name Again And be sure that it is not inside the namespace");dd('');}
                    $plusnamespace= Str::beforeLast($name, $newname);
                }else{
                    $plusnamespace= Str::between($name, $namespace, $newname);
                }
                $this->classinfo["NameSpace"]=$namespace.Str::beforeLast($plusnamespace,"\\");
                $this->classinfo["className"]=$newname;
                $this->classinfo["classDir"]=$this->modeltypes[$type]['src']."\\".Str::beforeLast($plusnamespace,"\\");
                $this->classinfo["classSrc"]=$this->modeltypes[$type]['src']."\\".Str::beforeLast($plusnamespace,"\\").'\\'.$newname.".php";
            }elseif(Str::contains($namespace,$name)){
                dd(__LINE__ ."TRY IT IN THIS LINE");
            }else{
                $plusnamespace= Str::between($name, $namespace, $newname);
                $this->classinfo["className"]=$newname;
                $this->classinfo["NameSpace"]=$namespace.Str::beforeLast($plusnamespace,"\\");
                $this->classinfo["classDir"]=$this->modeltypes[$type]['src']."\\".Str::beforeLast($plusnamespace,"\\");
                $this->classinfo["classSrc"]=$this->modeltypes[$type]['src']."\\".Str::beforeLast($plusnamespace,"\\").'\\'.$newname.".php";
            }
        }
    }
}
?>