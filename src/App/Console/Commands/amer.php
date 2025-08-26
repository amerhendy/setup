<?php
namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use AmerHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Config\FrameworkConfig;
use Symfony\Contracts\Translation\TranslatorInterface;
class amer extends Command
{
    use Traits\PrettyCommandOutput;
    use Traits\creatingamerobjects;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    ///want : controller,Model,Request,Lang,Route,Menu,Permission
    protected $signature = 'Amer:object {want : Want} {type : Type} {name : Name} 
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : set force replace';
    protected $aliases=[
        'Amer:Amer'
    ];
    public $modeltypes;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'amer library command';
    /**
     * Execute the console command./
     */
    public $classtype,$classtypeid,$className,$classinfo,$checkresult,$wantObjects,$want;
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = new OutputFormatterStyle('red', '#ff0', ['bold', 'blink']);
        $output->getFormatter()->setStyle('questions', $questions);
        $io = new SymfonyStyle($input, $output);
        $io->title('Creating Amer Objects');
        $io->listing([
            'it will create a Model',
            'it will create a Controller',
            'it will create a Request Files',
            'it will create a Migration Files',
        ]);
        $this->wantObjects=[
            'Model',
            'Controller',
            'Request',
            'Migration File',
            'Language File'
        ];
        $io->section('Please Select Your Object Type');
        if ( ! $input->getArgument('want')) {
            $this->infoBlock("you can choose multiple types like 1,2,3,...");
            $input->setArgument('want', $this->choice('<questions>What is your object type</>',$this->wantObjects,null,multiple:true));
        }
        $this->modeltypes=$this->modeltypes();
        if ( ! $input->getArgument('type')) {
            $input->setArgument('type', $this->choice('<questions>What is your object type</>',$this->modeltypes('Name')));
        }
        if ( ! $input->getArgument('name')) {
            $io->section('Please write Object name');
            $io->text(['dont start with number or any sign','do not use space or any sign','allowed sign "_"','model name must length more than 3 letters']);
            $input->setArgument('name', $this->ask('<questions>What is your Model Name?</>'));
        }
        
    }
    public function handle()
    {
        if($this->option('force')){$this->force='--force';}
        $this->prepareWantedObjects();
        $this->checkClassTypeInput();
        $this->checkClassNameInput();
        $this->checks();
        ///////////////////////////////
        //$this->insertRoute();
        //dd($this->classinfo["menu"]["Src"]);
        ///////////////////////////////
        
        $this->AmerCreate();
    }
    function prepareWantedObjects(){
        $want=$this->input->getArgument('want');
        foreach($want as $a=>$b){
            if(in_array($b,$this->wantObjects)){
                $this->want[]=$a;
            }
        }
        return;
    }
    function checkClassTypeInput(){
        $classtype=$this->input->getArgument('type');
        if(is_numeric($classtype)){
            if(!array_key_exists($classtype,$this->modeltypes('Name'))){
                $this->errorBlock('please choose right class type');
                return ;
            }
        }else{
            if(!in_array($classtype,$this->modeltypes('Name'))){
                $this->errorBlock('please choose right class type');
                return ;
            }
            $classtypearr=Arr::where($this->modeltypes('Name'),function($v,$k)use($classtype){if($v==$classtype){return $k;}});
            $classtypeid=(int) array_key_last($classtypearr);
        }
        $this->classtype=$classtype;$this->classtypeid=$classtypeid;
        return;
    }
    function checkClassNameInput(){
        $this->className=$this->input->getArgument('name');
        $this->createclassname($this->className,$this->wantObjects);
    }
    public function AmerCreate(){
        $objects=[
            0=>'model',
            1=>'controller',
            2=>'request',
            3=>'migration',
            4=>'lang'
        ];
        $creating=[];
        foreach($this->want as $a=>$b){
            if($this->checkresult[$objects[$b]] == null){$creating[]=$objects[$b];}
        }
        foreach ($creating as $key => $value) {
            $this->progressBlock('Creating/Update '.$value);
            $this->creat($value);
            $this->closeProgressBlock();
        }
    }
    function classstubs($classname){
        return '';
    }
}
