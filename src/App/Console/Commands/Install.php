<?php

namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Support\Facades\App;
use Illuminate\support\ServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use AmerHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
class Install extends Command
{
    use Traits\PrettyCommandOutput;
    protected $progressBar;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Amer:install {type : Type}
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : force replace data
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'start to publish public files before work ... lets start';


    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public $services, $wanted_services;
    public $sourcePath='';
    public $force=false;
    use Traits\PrettyCommandOutput;
    protected function interact(InputInterface $input, OutputInterface $output)
    {
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
        $this->sourcePath=config('Amer.Amer.package_path');
        parent::__construct();
    }
    public function handle()
    {
        if($this->input->getArgument('type') == 'no'){exit();}
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
                } catch (ProcessFailedException $exception) {
                    echo $exception->getMessage();
                }
            }
        }
        /////////////////////////////////////////////////////////////
        $this->line('');
        $this->info('Link Storage');
        $this->progressBar->start();
        $this->executeArtisanProcess('storage:link'.$this->force);
        ////////
        $this->line('');
        $this->info('Key Genration');
        $this->progressBar->advance();
        $this->progressBar->setProgress(1);
        $this->executeArtisanProcess('key:generate'.$this->force);
        ////////////
        $this->line('');
        $this->box('website link');
        $this->progressBar->advance();
        $this->progressBar->setProgress(3);
        $APP_URL=$this->ask('what is web site link',env('APP_URL'));
        $this->replaceinfile(base_path('.env'),"APP_URL",$replace="APP_URL=".$APP_URL);
        ////////////
        $this->line('');
        $this->box('set webSite Language');
        $language=$this->radiooptions('choose your web site language',['ar','en'],app()->config['app.locale']);
        if($language == 0 || $language == 'ar'){$language="ar";}elseif($language == 1 || $language == 'en'){$language="en";}else{$language="ar";}
        $this->progressBar->advance();
        $this->progressBar->setProgress(4);
        $this->replaceinfile(base_path('/config/app.php'),"'locale'",$replace="'locale'=>'".$language."',");
        $this->replaceinfile(base_path('/config/app.php'),"'fallback_locale'",$replace="'fallback_locale'=>'".$language."',");
        $this->progressBar->finish();
    }
    public function config_storage(){}
    public function config_auth(){}
    public function checkservice(){
        $this->services=[
        'AmerHendy/Amer'=>'Amerhendy\Amer\AmerServiceProvider',
        'AmerHendy/Security'=>'Amerhendy\Security\AmerSecurityServiceProvider',
        'AmerHendy/Employers'=>'Amerhendy\Employers\EmployersServiceProvider',
        'AmerHendy/Setup'=>'Amerhendy\Setup\AmerSetup',
        'AmerHendy/Employment'=>'Amerhendy\Employment\EmploymentServiceProvider',
        'amerhendy/drivers'=>'Amerhendy\Drivers\DriversServiceProvider',
        ];
        $als=Arr::map($this->services,function($v,$k){
            return $this->get_loaded_providers($v);
        });
        $return=[];
        foreach($als as $a=>$b){
            if($b==false){$return[]=$a;}
        }
        return $return;
    }

}
