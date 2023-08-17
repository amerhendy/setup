<?php

namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
class guards extends Command
{
    use Traits\PrettyCommandOutput;
    protected $progressBar;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Amer:Auth  {type : Type}
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : set force replace
    {--N|name= : The name of the new user}
                            {--E|email= : The user\'s email address}
                            {--P|password= : User\'s password}
                            {--encrypt=true : Encrypt user\'s password if it\'s plain text ( true by default )}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    public $sourcePath='';
    public $force=false;
    protected $description = 'publish auth Guards files data for Amer';
    use Traits\PrettyCommandOutput;
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $options = new OutputFormatterStyle('red', '#ff0', ['bold', 'blink','underscore']);
        $underscore = new OutputFormatterStyle(null, null, ['bold', 'blink','underscore']);
        $output->getFormatter()->setStyle('underscore', $underscore);
        $output->getFormatter()->setStyle('options', $options);
        $services=$this->checkservice();
        $this->wanted_services=$services;
        $io = new SymfonyStyle($input, $output);
        $this->box('Welcome to Amer Installer');
        if(count($services)){
            $io->title('you can install this library');
            $listing=Arr::map($services,function($k,$v){return 'Installing '.$k;});
            $io->listing($listing);
            $io->section('installing Amer Objects');
            if ( ! $input->getArgument('type')) {
                $tes=implode(', ',$services);
                $input->setArgument(
                    'type', 
                    $io->choice('Are you sure to install '.$tes,['yes','no'])
                );
            }
        }else{
            if ( ! $input->getArgument('type')) {
                $input->setArgument(
                    'type', 
                    $io->choice('Are you sure to install Options?',['yes','no'])
                );
            }
        }
    }
    public function __construct(){
        $this->sourcePath=config('amer.package_path');
        parent::__construct();
    }
    public function handle()
    {
        if($this->input->getArgument('type') == 'no'){exit();}
        if($this->option('force')){$this->force='--force';}
        $mainsteps=10;
        if(count($this->wanted_services)){
            $this->progressBar = $this->output->createProgressBar(count($this->wanted_services));
        }else{
            $this->progressBar = $this->output->createProgressBar($mainsteps);
        }
        $this->progressBar->setEmptyBarCharacter('<comment>=</comment>');
        $this->progressBar->setBarWidth(100);
        $this->progressBar->setFormat('verbose');
        $this->progressBar->minSecondsBetweenRedraws(0);
        $this->progressBar->maxSecondsBetweenRedraws(120);
        $this->progressBar->setRedrawFrequency(1);
        /////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////
        if(count($this->wanted_services)){
            $this->box('Creating Auth Components');
            foreach($this->wanted_services as $a=>$b){
                $this->line('');
                $this->info('Installing '.$b);
                $this->progressBar->start();
                $process = new Process(['composer', 'require', '', $b]);
                $process->setTimeout(300);
                try {
                    $process->mustRun();
                    echo $process->getOutput();
                } catch (ProcessFailedException $exception) {
                    echo $exception->getMessage();
                }
            }
        }
        /////////////////////////////////////////////////////////////
        $this->line('');
        $this->info('publish Spatie');
        $this->progressBar->advance();
        $this->executeArtisanProcess('vendor:publish',[
            '--provider'=>\Spatie\Permission\PermissionServiceProvider::class
        ],false,$this->force);
                $this->line('');
                $this->info('publish Config');
                $this->progressBar->advance();
                $configfolder=getallfiles(Config('amerSecurity.package_path').'config');
                foreach($configfolder as $a=>$b){
                    $filename=Str::afterLast($b,'/');
                    $filename=Str::beforeLast($filename,'.');
                    $source=Str::after($b,'src');
                    $this->publishfiles('../../Security/src/'.$source,base_path('config/'.$filename.'.php'));
                }
                /////////
                $this->line('');
                $this->info('Optimizing laravel');
                $this->progressBar->advance();
                $this->executeArtisanProcess('optimize:clear');
                $this->executeArtisanProcess('config:clear');
                //////////////
                $this->line('');
                $this->info('Optimizing Route');
                $this->progressBar->advance();
                $this->executeArtisanProcess('route:clear');
                $this->executeArtisanProcess('route:cache');
                ////////////
                $this->line('');
                $this->info('Migrate Permession database');
                $this->progressBar->advance();
                $migrationfolder=getallfiles(Config('amerSecurity.package_path').'database\migrations');
                foreach($migrationfolder as $a=>$b){
                    $filename=Str::afterLast($b,'/');
                    $filename=Str::beforeLast($filename,'.');
                    $source=Str::after($b,'src');
                    $this->publishfiles('../../Security/src/'.$source,base_path('database/migrations/'.$filename.'.php'));
                }
                $this->executeArtisanProcess('migrate');
                ////////////////////////////////
            
            /////////////////////////////////////////
            $pubnewmig=false;
            $rolescolumns = \Schema::getColumnListing('roles');
            $permessionscolumns = \Schema::getColumnListing('permissions');
            if(!count(Arr::where($rolescolumns,function($v,$k){return $v=="ArName";}))){$pubnewmig=true;}
            elseif(!count(Arr::where($rolescolumns,function($v,$k){return $v=="deleted_at";}))){$pubnewmig=true;}
            elseif(!count(Arr::where($rolescolumns,function($v,$k){return $v=="sort";}))){$pubnewmig=true;}
            elseif(!count(Arr::where($permessionscolumns,function($v,$k){return $v=="ArName";}))){$pubnewmig=true;}
            elseif(!count(Arr::where($permessionscolumns,function($v,$k){return $v=="deleted_at";}))){$pubnewmig=true;}
            if($pubnewmig == true){
                //$newmigrationfileName="database/migrations/".rand(1,1000000)."-permessions.php";
                //$this->publishfiles('/migrations/permessions.php',base_path($newmigrationfileName));
                //$this->executeArtisanProcess('migrate',['--path'=>$newmigrationfileName,'--force'=>'']);
            }
            $this->line('');
            $this->info('Create Roles');
            $this->progressBar->advance();
            $this->createroles();
            $this->line('');
            $this->info('User Area');
            $this->progressBar->advance();
            $this->usertable();

    }
    function createroles(){
        $this->executeArtisanProcess('permission:create-role Programmer Amer --team-id=1 "role-show|role-add|role-update|role-trash|role-delete|permission-show|permission-add|permission-update|permission-trash|permission-delete|user-add|user-trash|user-update|user-delete|user-show"');
        $this->executeArtisanProcess('permission:create-role Administrator Amer --team-id=1 "role-show|role-add|role-update|role-trash|role-delete|permission-show|permission-add|permission-update|permission-trash|permission-delete|user-add|user-trash|user-update|user-delete|user-show"');
        $this->executeArtisanProcess('permission:create-role SuperUser Amer --team-id=1 "role-show|role-add|role-update|role-trash|role-delete|permission-show|permission-add|permission-update|permission-trash|permission-delete|user-add|user-trash|user-update|user-delete|user-show"');
        $this->executeArtisanProcess('permission:create-role Employers Amer --team-id=1 "role-show|permission-show|user-show"');
        
        //executeArtisanProcess('permission:cache-reset');
    }
    function usertable(){
        $auth = config('amerSecurity.auth.model');
        $user = new $auth();
        $this->line('');$this->line('<options>Installing admin Model</>');$this->line('');
        $this->info('Creating a new user');
        $loghint=[
            '<bg=red>Attention!',
            '*<underscore>dont start with number or any sign</>','*<underscore>do not use space or any sign</>',
            '*<underscore>allowed sign "_"</>','*<underscore>model name must length more than 6 letters</>'];
        $name=$this->askmultilines('    <options>enter user name</>',$loghint);
        $name=trim($name);
        if(($name == '') || (is_numeric($name)) || (is_null($name)) || (strlen($name)<6)){
            $this->errorBlock("Please Insert Right Name");
            exit();
        }
        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬-]/', $name))
        {
            $this->errorBlock('The name has symbol!');return;
        }
        
        if(Str::isAscii($name) == false){$this->errorBlock('The class has symbol!');exit();}
        $loghint=['enter right email'];
        $email=$this->askmultilines('enter your Email',$loghint);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errorBlock('Please Enter Valid Email');exit();
          }
          if (! $password = $this->option('password')) {
            $password = $this->secret('Password');
        }
        if ($this->option('encrypt')) {
            $password = bcrypt($password);
        }
        
        if(count($user->where('email',$email)->get())){
            $this->info('user already exists');
        }else{
            $req=$user->get()->toArray();
            $lastid=Arr::last(Arr::sort(Arr::map($req, function ($value, $key) {
                return $value['id'];
            })));
            $user->id=$lastid+1;
            $user->name = $name;
            $user->email = $email;
            $user->password = $password;
            if ($user->save()) {
                $this->info('Successfully created new user');
            } else {
                $this->error('Something went wrong trying to save your user');
            }
        }
    }
    public function checkservice(){
        $return=[];
        
        $als=[
        'AmerHendy/Security'=>$this->get_loaded_providers('Amerhendy\Security\AmerSecurityServiceProvider'),
        'laravel/ui'=>$this->get_loaded_providers('Laravel\Ui\UiServiceProvider'),
        'spatie/laravel-permission'=>$this->get_loaded_providers('Spatie\Permission\PermissionServiceProvider'),
        ];
        foreach($als as $a=>$b){
            if($b==false){$return[]=$a;}
        }
        return $return;
    }
}
