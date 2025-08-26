<?php
namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
class Addpermession extends Command{
    protected $signature='Amer:Addpermission
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : set force replace
    {--class}';
    protected $aliases=[
        'Amer:Permissions',
        'Amer:Permission'
    ];
    public $services,$wanted_services;
    protected $description=" add description permessions";
    use Traits\PrettyCommandOutput;
    public function handle()
    {
        $this->checkservice();
        if($this->option('force')){$this->force=" --force";}

    if(count($this->wanted_services)){
        $this->progressBar = $this->output->createProgressBar(count($this->wanted_services));
    }else{
        $this->progressBar = $this->output->createProgressBar(4);
    }
    $this->progressBar->setEmptyBarCharacter('<comment>=</comment>');
    $this->progressBar->setBarWidth(100);
    $this->progressBar->setFormat('verbose');
    $this->progressBar->minSecondsBetweenRedraws(0);
    $this->progressBar->maxSecondsBetweenRedraws(120);
    $this->progressBar->setRedrawFrequency(1);
    /////////////////////////////////////////////////////////////
        if(count($this->wanted_services)){
            foreach($this->wanted_services as $a=>$b){

                $this->line('');
                $this->info('Installing '.$b);
                $this->progressBar->start();
                $process = new Process(['composer', 'require', '', $b]);
                $process->setTimeout(300);
                try {
                    $process->mustRun();
                    echo $process->getOutput();
                    $provider=$this->services[$b];
                    $this->executeArtisanProcess('vendor:publish',[
                        '--provider'=>$provider::class
                    ],false,$this->force);
                } catch (ProcessFailedException $exception) {
                    echo $exception->getMessage();
                }
            }
        }
        ///////////////////////////////////////////////
        $this->line('');
        $this->info('Creating a permessions');
        $this->progressBar->start();
        $Class=$this->askclass();
        $this->addclass($Class);

    }
    public function checkservice(){
        $return=[];
        $this->services=[
            'spatie/laravel-permission'=>'Spatie\Permission\PermissionServiceProvider',
            'AmerHendy/Security'=>'Amerhendy\Security\AmerSecurityServiceProvider',
            ];
        $als=Arr::map($this->services,function($v,$k){
            return $this->get_loaded_providers($v);
        });
        foreach($als as $a=>$b){
            if($b==false){$return[]=$a;}
        }
        $this->wanted_services=$return;
    }
    public function askclass(){
        $Class=$this->ask('type permission className');
        if($Class == '' || $Class == null){return $this->askclass();}
        return $Class;
    }
    public function addclass($Class){
        $permessionmodel=config('permission.models.permission')??'Spatie\Permission\Models\Permission';
        $per=new $permessionmodel();
        $perlid=$per->get()->toArray();
        if(count(Arr::where($perlid,function($v,$k)use($Class){return Str::beforeLast($v['name'],'-') == $Class;}))){
            $this->errorBlock("Class Name Have Permission before, Please Select Another Class Name");
            return $this->handle();
        }
        $pers=['add','trash','update','delete','show'];
        $arr=[];
        for($i=0;$i<count($pers);$i++){
            $arr[]=[
                'id'=>\Str::uuid(),
                'name'=>$Class.'-'.$pers[$i],
                'guard_name'=>'Amer',
                'created_at'=>'now()'
            ];
        }
        $per->insert($arr);
        $this->line('');
        $this->info('Inserted','Success');
    }

}
