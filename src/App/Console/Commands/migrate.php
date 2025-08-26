<?php
namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Illuminate\Support\Facades\Artisan;
class migrate extends Command
{
    use Traits\PrettyCommandOutput;
    protected $progressBar;
    protected $signature = 'Amer:migrate {package? : Package}
    {--Path=*} : Path of Sql Folder.
    {--timeout=300} : How many seconds to allow each process to run.
    {--debug} : Show process output or not. Useful for debugging.
    {--force} : force replace data
    ';
    protected $description = 'start to seed Sql files';
    public $services, $path;
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
            if ( ! $input->getArgument('package')) {
                $this->box('Welcome to Amer Seed Installer');
                $io->title('Please Choose your Library');
                $services=$this->checkservice();
                $this->services[]=['other'=>''];
                $services[]='other';
                $input->setArgument(
                    'package', 
                    $io->choice('Select Library to Choose',$services,0)
                );
            }
    }
    public function handle()
    {
        $packagesSrc=[
            'AmerHendy/Amer'=>config('Amer.amer.package_path',base_path('vendor\AmerHendy\Amert\src\\')),
            'AmerHendy/Security'=>config('Amer.Security.package_path',base_path('vendor\AmerHendy\Security\src\\')),
            'AmerHendy/Employers'=>config('Amer.employers.package_path',base_path('vendor\AmerHendy\Employers\src\\')),
            'AmerHendy/Employment'=>config('Amer.employment.package_path',base_path('vendor\AmerHendy\Employment\src\\')),
            'other'=>'ss'
        ];
        $package=$this->input->getArgument('package');
        if(is_numeric($package)){
                $this->error('please Select write package Name');
                exit();
        }
        if($package == 'other'){
            $path=$this->askforPath();
        }else{
            $path=Arr::get($packagesSrc,$package);
            $path=$path."database\migrations";
        }
        $path=(string) Str::of($path)->finish('\\');
        $path=(string) Str::of($path)->replace('/','\\');
        if(!File::exists($path)){
            $path=$this->askforPath();
        }
        $this->path=$path;
        $this->folderc();
    }
    function askforPath(){
        $path=$this->ask('Write the full path of sql OR php folder',database_path());
        $path=(string) Str::of($path)->finish('\\');
        $path=(string) Str::of($path)->replace('/','\\');
        if(!File::exists($path)){
            $this->error('Folder Path is Wrong, please write it aagain');
            return $this->askforPath();
        }
        return $path;
    }    
    /**
     * folderc
     *get files in folder
     * @return void
     */
    function folderc(){
        $path=$this->path;
        $files = array_diff(scandir($path), array('.', '..'));
        foreach($files as $a=>$b){
            if((string) Str::of($b)->afterLast('.') == 'php'){
                $this->readPhpFile($b);
            }elseif((string) Str::of($b)->afterLast('.') == 'sql'){
                $this->readSqlFile($b);
            }
        }
    }    
    /**
     * readPhpFile
     *
     * @param  mixed $file : migration.php file
     * @return void
     */
    function readPhpFile($file){
        if($this->IfMigrationFile($file) == false){return $this->errorBlock($file .": is not has Migration Class");}
        $file=$this->path.$file;
        Artisan::Call("migrate --path=".Str::of($this->path)->remove(base_path()));
        $output = Artisan::output();
        $this->info($output);
    }    
    /**
     * IfMigrationFile
     *check if file haaas migration class
     * @param  mixed $file
     * @return void
     */
    function IfMigrationFile($file){
        $file=$this->path.$file;
        $fp = fopen($file, 'r');
        $class = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp)) break;

            $buffer .= fread($fp, 512);
            $tokens = token_get_all($buffer);
            if (strpos($buffer, '{') === false) continue;
            for (;$i<count($tokens);$i++) {
                if ($tokens[$i][0] === T_CLASS) {
                    for ($j=$i+1;$j<count($tokens);$j++) {
                        if ($tokens[$j] === '{') {
                            for($l=0;$l<10;$l++){
                                if($tokens[$i+$l][1] == 'migration' || $tokens[$i+$l][1] == 'Migration'){
                                    return true;
                                }
                            }
                            $class = $tokens[$i+2][1];
                        }
                    }
                }
            }
        }
        return false;
    }
    function readSqlFile($file){
        $fileName=$file;
        if(\Str::of($fileName)->startsWith('-')){return;}
        $file=$this->path.$file;
        $content=file_get_contents($file);
        try {
            $result=\DB::unprepared($content);
            if($result == true){
                \File::move($file,$this->path."-".$fileName);
            }
        } catch (\Illuminate\Database\QueryException $th) {
            $this->infoBlock($th->getMessage(),'Error','red','white');
            //$this->errorbox('error',$th->getMessage());
        }
        
        
    }
    
    public function checkservice(){
        $this->services=[
            'AmerHendy/Amer'=>'Amerhendy\Amer\AmerServiceProvider',
        'AmerHendy/Security'=>'Amerhendy\Security\AmerSecurityServiceProvider',
        'AmerHendy/Employers'=>'Amerhendy\Employers\EmployersServiceProvider',
        'AmerHendy/Employment'=>'Amerhendy\Employment\EmploymentServiceProvider',
        ];
        $als=Arr::map($this->services,function($v,$k){
            return $this->get_loaded_providers($v);
        });
        $return=[];
        foreach($als as $a=>$b){
            if($b==true){$return[]=$a;}
        }
        return $return;
    }
}
