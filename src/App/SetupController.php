<?php
namespace Amerhendy\Setup\App;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Auth;
use \Cviebrock\EloquentSluggable\Services\SlugService;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
class SetupController extends Controller
{
    use \Amerhendy\Setup\App\controllerhelper;
    protected $currentClass;
    
    protected $settings = [];
    protected $currentOperation;
    protected $routeMethod;
    protected $id;
    public $services;
    public function __construct() {
    }
    public function __invoke(Request $request)
    {
        return $this->index();
    }
    public function index()
    {
        $this->setuppackages(['predis/predis']);
        dd("AAAAAAAAAAAAA");
        $data=[];
        $data['checkservice']=$this->checkservice();
        $data['services']=$this->services;
        $data['dbtypes']=['pgsql','sqlite','mysql','sqlsrv'];
        $data['db_default']=config('database.default');
        $data['connections']=config('database.connections');
        $data['logging']=config('logging');
        $data['broadcasting']=config('broadcasting');
        $data['cache']=config('cache');
        $data['filesystems']=$this->createnewfilesystems();
        $data['queue']=config('queue');
        $data['session']=config('session');
        $data['mail']=config('mail');
        $data['dbconfig']=config('database.connections.'.$data['db_default']);
        return view('SetUp::setup.pages.first',$data);
    }
    function createnewfilesystems(){
        $filesystems=config('filesystems');
        return $filesystems;
    }
    public function checkservice(){
        $this->services=[
        'AmerHendy/Amer'=>'Amerhendy\Amer\AmerServiceProvider',
        'AmerHendy/Security'=>'Amerhendy\Security\AmerSecurityServiceProvider',
        'AmerHendy/Employer'=>'Amerhendy\Employers\EmployersServiceProvider',
        'AmerHendy/Setup'=>'Amerhendy\Setup\AmerSetup',
        'AmerHendy/Employment'=>'Amerhendy\Employment\EmploymentServiceProvider',
        ];
        $als=\Arr::map($this->services,function($v,$k){
            return $this->get_loaded_providers($v);
        });
        $return=[];
        foreach($als as $a=>$b){
            if($b==false){$return[]=$a;}
        }
        return $return;
    }
    public function get_loaded_providers($provider=null){
        //dd(App()->getLoadedProviders());
        $prov=App()->getLoadedProviders();
        if($provider == null){return $prov;}
        if(array_key_exists($provider,$prov)){
            return true;
        }
        return false;
    }
    function post(){
        if(isset($_POST['packages'])){
            return $this->setuppackages($_POST['packages']);
        }elseif(isset($_POST['env'])){
                return $this->changeenv();
        }elseif(isset($_POST['config'])){
            return $this->changeConfig();
            
        }else{
            dd($_POST);
        }
    }
    /*array:9 [ // vendor\AmerHendy\Setup\src\App\SetupController.php:86
  "config" => "broadcast"
  "default" => "pusher"
  "scheme" => "https"
  "key" => ""
  "secret" => ""
  "app_id" => ""
  "cluster" => "mt1"
  "host" => "api-mt1.pusher.com"
  "port" => "443"
]*/
    function changeConfig(){
        $path=\File::files(config_path());
        $configs=[];
        foreach($path as $a=>$b){
            $b=$b->getRealPath();
            $b=\Str::replace(config_path(),'',$b);
            $b=\Str::replace('.php','',$b);
            $b=\Str::replace('\\','',$b);
            $b=\Str::replace('/','',$b);
            $configs[]=$b;
        }
        $file=$_POST['config'];
        if(isset($_POST['env'])){
            $key=$_POST['key'];
            $data=$_POST['data'];
            if($key == 'fallback_locale'){
                $key="'fallback_locale'";
                $replace="'fallback_locale'=>'".$data."',";
                $res=$this->replaceinfile(base_path('config/app.php'),$key,$replace);
            }
        }else{
            $envtext=$this->getsetinvtext();
            $alo=\Artisan::call($envtext);
            if($alo == 0){
                return $this->selectFileFunction();
            }
            
        }
        return $this->result($res);
    }
    function selectFileFunction(){
        $file=$_POST['config'];
        
        switch ($file) {
            case 'log':
                return $this->change_log();
                break;
            case 'broadcasting':
                return $this->change_broadcast();
                break;
            case 'cache':
                $envtext.="CACHE_DRIVER";
                break;
            case 'filesystems':
                $envtext.="FILESYSTEM_DISK";
                break;
            case 'queue':
                $envtext.="QUEUE_CONNECTION";
                break;
            case 'session':
                $envtext.="SESSION_DRIVER";
                break;
            case 'mail':
                $envtext.="MAIL_MAILER";
                break;
            default:
                dd($file);
                break;
        }
    }
    function getsetinvtext(){
        $file=$_POST['config'];
        $default=$_POST['default'];
        $envtext='env:set ';
        $a=$this->composer($file);
        switch ($file) {
            case 'log':
                $envtext.="LOG_CHANNEL";
                break;
            case 'broadcasting':
                $envtext.="BROADCAST_DRIVER";
                break;
            case 'cache':
                $envtext.="CACHE_DRIVER";
                break;
            case 'filesystems':
                $envtext.="FILESYSTEM_DISK";
                break;
            case 'queue':
                $envtext.="QUEUE_CONNECTION";
                break;
            case 'session':
                $envtext.="SESSION_DRIVER";
                break;
            case 'mail':
                $envtext.="MAIL_MAILER";
                break;
            default:
                dd($file);
                break;
        }
        dd($file);
        $envtext.='='.$default;
        return $envtext;
    }
    function composer($file){
        $default=$_POST['default'];
        switch ($file) {
            case 'broadcasting':
                if($default == 'pusher'){
                    $this->setuppackages(['pusher/pusher-php-server']);

                    $syn="pusher/pusher-php-server";
                }
                if($default == 'ably'){
                    $syn='ably/ably-php';
                }
                break;
            
            case 'cache':
                if($default == 'Redis'){
                    $syn="predis/predis";
                }
                break;
            
            case 'filesystems':
                if($default == 's3'){
                    $syn='--with-all-dependencies league/flysystem-aws-s3-v3 "^1.0"';
                }
                if($default == 'SFTP'){
                    $syn='league/flysystem-sftp ~1.0';
                    ///////////////
                }

                break;
            
            case 'queue':
                if($default == 'beanstalkd'){
                    $syn='pda/pheanstalk ~4.0';
                }
                if($default == 'sqs'){
                    $syn='aws/aws-sdk-php ~3.0';
                }
                if($default == 'redis'){
                    $syn='predis/predis ~1.0';
                }

                break;
            case 'mail':
                if($default == 'mailgun'){
                    $syn='guzzlehttp/guzzle';
                }
                if($default == 'postmark'){
                    $syn='guzzlehttp/guzzle';
                    $syn.='||wildbit/swiftmailer-postmark';
                }
                if($default == 'ses'){
                    $syn='aws/aws-sdk-php';
                }

                break;
            
            default:
                # code...
                break;
        }
        if(\Str::contains($syn,"||")){
            $syn=explode('||',$syn);
        }else{
            $syn=[$syn];
        }
        foreach($syn as $a=>$b){
            $process = new Process(['cat']);
            $process->setInput('foobar');
            $process->run();
        }
        return($syn);
    }
    function changeenv(){
        $key=$_POST['env'];
        if($key == 'APP_URL'){
            $appurl=$_POST['data'];
            $res=$this->replaceinfile(base_path('.env'),$key,$replace=$key."=".$appurl);
            return $this->result($res);
        }elseif($key == 'APP_NAME' || $key == 'APP_ENV'){
            $data=$_POST['data'];
            $alo=\Artisan::call('env:set '.$key.'='.$data);
            if($alo == 0){
                return $this->result(['status'=>'success','message'=>'done']);
            }
        }elseif($key == 'APP_KEY'){
            $alo=\Artisan::call('key:generate');
            if($alo == 0){
                return $this->result(['status'=>'success','message'=>'done']);
            }
        }
            
    }
    function setuppackages($packs){
        if(count($packs)){
            $a=[];
            foreach ($packs as $key => $value) {
                //$a[$value]=shell_exec('composer require '.$value);
                $a[$value]=$this->execInBackground('composer require '.$value);
            }
            return $a;
        }
    }
    function execInBackground($cmd) {
        $a=\Amerhendy\Setup\App\Console\Commands\composerrun::class;
        $b=new $a();
        dd(\Artisan::call("Amer:composerrun '".$cmd."'"));
        
        $paths=getenv('path');
        foreach(explode(';',$paths) as $a=>$b){
            if(\Str::contains($b,'composer')){
                $composer=$b;
            }
            if(\Str::contains($b,'php')){
                $php=$b;
            }
            print $b.'<br>';
        }
        $cmd=$php .'\php.exe '.$composer.'\composer.bat '.$cmd;
        //dd($cmd);
        if (substr(php_uname(), 0, 7) == "Windows"){$runpath=\Str::replace('\\','\\\\',base_path('vendor\AmerHendy\Setup\src\App\Console\runcmd.bat'));}else{$runpath=base_path('vendor\AmerHendy\Setup\src\App\Console\runcmd.bat');}
        define('RUNCMDPATH', $runpath);
        //$a=exec($php.' C:\laragon\bin\composer\composer\composer.phar require local/asasasas',$out,$ret);
        $a=exec('@echo off 
        start composer "require predis/predis" cd C:\laragon\www\lotfy\loginsystem_Copy\employment',$out,$ret);
        dd($a,$out,$ret);
        if (substr(php_uname(), 0, 7) == "Windows"){
            $handle=popen(RUNCMDPATH.' '.$cmd,'r');
            //echo "'$handle'; " . gettype($handle) . "\n";
            $read = fread($handle, 2096);
            pclose($handle);
        }
        else {
            
            $handle=exec($cmd . " > /dev/null &");   
    
        }
        return $handle;
    }
    
}