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
use Illuminate\Support\Facades\File;
class createcontroller extends GeneratorCommand
{
    protected $name = 'Amer:controller';
    //protected $signature = 'Amer:model {name} {table}';
    protected $signature = 'Amer:controller {type : Type} {name : Name}
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : set force replace';
    protected $description ="Create controller";
    protected $type = 'controller';
    protected $io,$input;
    public $model=[];
    public $requestclass=[];
    public $classtype,$classtypeid,$classname,$classinfo;
    public $force=false;
    public $modeltypes= [
        1=>['name'=>'Admin','nameSpace'=>'App/Models/Admin'],
        2=>['name'=>'Employer','nameSpace'=>'App/Models/Employer'],
        3=>['name'=>'Employment','nameSpace'=>'App/Models/Employment'],
        4=>['name'=>'Public','nameSpace'=>'App/Models'],
    ];      
    protected $AmerTrait = 'Amerhendy\Employment\App\Models\Traits\AmerTrait';
    use Traits\PrettyCommandOutput;
    use Traits\creatingamerobjects;
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->input=$input;
        if($this->option('force')){$this->force='--force';}
        $this->construct();
        $this->io = new SymfonyStyle($input, $output);
        $this->box('Welcome to Amer Installer');
        $this->selecttype();
        $this->typename();
    }
    function selecttype(){
        $io=$this->io;$input=$this->input;
        $io->title('you can Create A Controller Here');
        $modeltypes=Arr::map($this->modeltypes,function($v,$k){return $v['name'];});
        if ( ! $input->getArgument('type')) {
            $input->setArgument('type', $io->choice('What is your object type',$modeltypes,1));

        }
        $this->classtype=$this->input->getArgument('type');
        $classtype=$this->classtype;
        $classtypearr=Arr::where($modeltypes,function($v,$k)use($classtype){if($v==$classtype){return $k;}});
        $this->classtypeid=(int) array_key_last($classtypearr);
    }
    function typename(){
        $input=$this->input;
        if ( ! $input->getArgument('name')) {
            $input->setArgument('name', $this->ask('Name ?'));
        }
    }
    public function construct(){
        foreach($this->modeltypes as $a=>$b){
            if($a == 0){$this->modeltypes[$a]['Dir']=config('amer.package_path').'App\\Models';}
            elseif($a==1){$this->modeltypes[$a]['Dir']=base_path().'\\App\\Models\\Admin';}
            elseif($a==2){$this->modeltypes[$a]['Dir']=base_path().'\\App\\Models\\Employer';}
            elseif($a==3){$this->modeltypes[$a]['Dir']=base_path().'\\App\\Models\\Employment';}
            elseif($a==4){$this->modeltypes[$a]['Dir']=base_path().'\\App\\Models';}
        }
    }
    public function handle(){
        $class=$this->argument('name');
        $this->createclassname($class,'createController');
        $this->checkModelExists();
        $this->checkControllerExists();
        $namespaceModels=$this->classinfo['Model']['nameSpace'].'/'.$this->classinfo['Model']['className'];
        $use=$this->buildClass($namespaceModels);
        $destinationclassPath=Str::replace('\\','/',$this->classinfo['controller']['Dir'])."/".$this->classinfo['controller']['className'].".php";
        File::put($destinationclassPath, $use);
        $this->infoBlock($this->type.' created successfully.');
        return;
        dd($destinationclassPath);
        if (! $existsOnApp && ! $existsOnModels) {
        
            $use=$this->buildClass($namespaceModels);
            
            
            $this->files->put($destinationclassPath, $Alphabeticallysorts);
            $this->infoBlock($this->type.' created successfully.');
            return;
        }
        $name = $existsOnApp ? $namespaceApp : $namespaceModels;
        $path = $this->getPath($name);
        if (! $this->hasOption('force') || ! $this->option('force')) {
            $file = $this->files->get($path);
            $lines = preg_split('/(\r\n)|\r|\n/', $file);

            // check if it already uses AmerTrait
            // if it does, do nothing
            if (Str::contains($file, $this->AmerTrait)) {
                $this->comment('Model already used AmerTrait.');

                return;
            }
            // if it does not have AmerTrait, add the trait on the Model
            foreach ($lines as $key => $line) {
                if (Str::contains($line, "class {$this->getNameInput()} extends")) {
                    if (Str::endsWith($line, '{')) {
                        // add the trait on the next
                        $position = $key + 1;
                    } elseif ($lines[$key + 1] == '{') {
                        // add the trait on the next next line
                        $position = $key + 2;
                    }

                    // keep in mind that the line number shown in IDEs is not
                    // the same as the array index - arrays start counting from 0,
                    // IDEs start counting from 1

                    // add AmerTrait
                    array_splice($lines, $position, 0, "    use \\{$this->AmerTrait};");

                    // save the file
                    $this->files->put($path, implode(PHP_EOL, $lines));

                    // let the user know what we've done
                    $this->info('Model already existed. Added AmerTrait to it.');

                    return;
                }
            }
            // In case we couldn't add the AmerTrait
            $this->error("Model already existed on '$name' and we couldn't add AmerTrait. Please add it manually.");
        }
    }
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());
        return $this->replaceTable($stub, $name);
    }
    protected function getStub()
    {return __DIR__.'/../stubs/controller.model-amer.stub';}
    
    protected function replaceTable(&$stub, $name)
    {
        $name = ltrim(preg_replace('/[A-Z]/', '_$0', str_replace($this->getNamespace($name).'\\', '', $name)), '_');
        $table=$name;
        $namespace=$this->classinfo['controller']['nameSpace'];
        $modelclass=$this->classinfo['Model']['className'];
        $controllerclass=$this->classinfo['controller']['className'];
        $modelnamespace=Str::replace('/','\\',$this->classinfo['Model']['nameSpace']).'\\'.$modelclass;
        $requestclass=$this->classinfo['request']['className'];
        $requestnamespace=Str::replace('/','\\',$this->classinfo['request']['nameSpace']).'\\'.$requestclass;
        $stub = str_replace('{{ model }}', $modelclass, $stub);
        $stub = str_replace('{{ class }}', $controllerclass, $stub);
        $stub = str_replace('{{ namespacedModel }}', $modelnamespace, $stub);
        $stub = str_replace('{{ modelVariable }}', 'id', $stub);
        $stub = str_replace('{{ InsertnamespacedRequests }}', $requestnamespace, $stub);
        $stub = str_replace('DummyClassRequest', $requestclass, $stub);
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ rootNamespace }}', 'App\\', $stub);
        return $stub;
    }
    function checkModelExists(){
        $type=$this->classtypeid;
        $classname=$this->classinfo['Model']["className"];
        $src=$this->classinfo['Model']["Dir"];
        //////////////////////////////////////////////
        $amerhenlpers=new AmerHelper();
        if(!File::exists($src)){
            $askModel=$this->confirm('there is no Model For This Controller, Do you Want to Create a Model?');
            if($askModel == true){
                Artisan::call('Amer:model', [
                    'type'=>$this->classtype,
                    'name'=>$this->classinfo['Model']["nameSpace"].'/'.$this->classinfo['Model']["className"],
                    '--force'=>true,
                    '--debug'=>true
                ]);
                return;
            }
        }else{
            $allmodels=$amerhenlpers::getModels(str::replace('\\','/',$src));
            if(!count($allmodels)){return;}
        }
        
    }
    function checkcontrollerExists(){
        
        $type=$this->classtypeid;
        $classname=$this->classinfo['controller']["className"];
        $src=$this->classinfo['controller']["Dir"];
        //////////////////////////////////////////////
        if(!File::exists($src)){
            File::makeDirectory($src,0755,true,true);
            return;
        }else{
            if(File::isFile($src)){
                //remove Model
                $this->errorBlock('there is file with the same name of your controler dir');
                $this->line('Please remove this File: '.$src);
                exit();
            }
        }
        $amerhenlpers=new AmerHelper();
        $allmodels=$amerhenlpers::getModels(str::replace('\\','/',$src));
        if(!count($allmodels)){return;}
        $are=Arr::where($allmodels,function($v,$k)use($classname){
            return $v["className"]==$classname;
        });
        if(count($are)){
            //remove Model
            $this->errorBlock('there is Controller with the same name of your controler ');
                $this->line('in this Dir: '.$src);
                exit();
        }
    }
    
    public function checkandcreatDir(){
        $classDir=$this->classinfo['controllerDir'];
        
        if(Str::endsWith($classDir, '\\')){$classDir=Str::beforeLast($classDir,'\\');}
        if(Str::endsWith($classDir, '/')){$classDir=Str::beforeLast($classDir,'/');}      
        if(File::exists($classDir)){
            dd($classDir);
            $this->line('Directory Exists');
            if(!File::isDirectory($classDir)){
                return ['error',$classDir.' is file not Directory'];
            }
        }else{
            $this->line('Creating Directory');
            if(File::isFile($classDir)){
                return ['error','there file with the Same Path Name please remove it '.$classDir];
            }
            if(File::makeDirectory($classDir,0755,true,true)){
                
            }
        }
    }
}
?>