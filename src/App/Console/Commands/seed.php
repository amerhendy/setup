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
class seed extends Command
{
    use Traits\PrettyCommandOutput;
    protected $progressBar;
    protected $signature = 'Amer:seed {package? : Package}
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
            'AmerHendy/Amer'=>config('Amer.amer.package_path'),
            'AmerHendy/Security'=>config('Amer.Security.package_path'),
            'AmerHendy/Employers'=>config('Amer.employers.package_path'),
            'AmerHendy/Employment'=>config('Amer.employment.package_path',base_path('vendor\AmerHendy\employment\src\\')),
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
            $path=$path."database\seeds";
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
        $path=$this->ask('Write the full path of CSV folder',database_path());
        $path=(string) Str::of($path)->finish('\\');
        $path=(string) Str::of($path)->replace('/','\\');
        if(!File::exists($path)){
            $this->error('CSV Path is Wrong, please write it aagain');
            return $this->askforPath();
        }
        return $path;
    }
    function folderc(){
        $path=$this->path;
        $files = array_diff(scandir($path), array('.', '..'));
        foreach($files as $a=>$b){
            $fileNameArray=explode('.',$b);
            if((string) Str::of($b)->afterLast('.') == 'csv'){
                $tableName=(string) Str::of($b)->beforeLast('.');
                if($this->checkIfTableExists($tableName) == true){
                    $seq=$this->createInsertSequance($b);
                    $this->insertdata($seq,$tableName);
                }
            }elseif((string) Str::of($b)->afterLast('.') == 'sql'){
                $this->readSqlFile($b);
            }
            $tableName=$fileNameArray[0];
        }
    }
    function readSqlFile($file){
        $file=$this->path.$file;
        $content=file_get_contents($file);
        $arraycontent=explode('INSERT INTO ',$content);
        foreach($arraycontent as $a=>$b){
            if($b==""){
                unset($arraycontent[$a]);
            }else{
                $arraycontent[$a]="INSERT INTO ".$b;
            }
            
        }
        foreach($arraycontent as $a=>$b){
            $this->getsqlheader($b);
        }
    }
    function insertSqlData($data){
        $tableName=$data['tableName'];
        $exists=[];$Success=[];
        foreach($data['rows'] as $a=>$b){
            if(isset($b['id'])){
                $id=$b['id'];
                $RowExists=\DB::table($tableName)->where('id',$id)->get();
                if(count($RowExists)){
                    $exists[]=$id;
                }else{
                    if(\DB::table($tableName)->insert($b)){
                        $Success[]=$id;
                    }else{
                        $this->errorBlock("Row id:".$id." not inserted (".implode(',',$b).")");
                    }
                }
            }else{
                if(\DB::table($tableName)->insert($b)){
                    $Success[]=$id;
                }else{
                    $this->errorBlock("Row id:".$id." not inserted (".implode(',',$b).")");
                }
            }
        }
        if(count($exists)){$this->infoBlock("Row id:".implode(', ',$exists)." exists Check your dataBase",'Row Exists @ '.$tableName);}
        if(count($Success)){$this->infoBlock("Row id:".implode(', ',$Success)." inserted",'Success @ '.$tableName);}
    }
    function RowExists($tableName,$id):bool
    {
        $RowExists=\DB::table($tableName)->where('id',$id)->get();
        if(count($RowExists)){return false;}
        return true;
    }
    function getsqlheader($sy){
        $headpart=(string) \Str::of($sy)->before('VALUES');
        $headI=\Str::of($headpart)->before('(');
        $tableName=(string) \Str::of($headI)->between('"','"');
        if($this->checkIfTableExists($tableName) == false){
            $this->error('table '.$tableName.' not found');
            return;
        }
        $headII=(string) \Str::of($headpart)->between('(',')');
        if(\Str::contains($headII,'"')){$headII=\Str::remove('"',$headII);}
        $colnames=explode(', ',$headII);
        $bodyPart=\Str::of($sy)->after('VALUES');
        $bodyPart=explode('),',$bodyPart);
        $body=
            \Arr::map($bodyPart,function($v,$k)use($tableName){
                $v=\Str::after($v,'(');
                if(Str::finish($v,';')){
                    $v=\Str::before($v,')');
                }
                
                return $v;
            });
            $exists=[];$Success=[];
            
            foreach ($body as $key => $value) {
                $id=(string) \Str::of($value)->before(',');
                $intid=(int) $id;
                if($this->RowExists($tableName,$intid) == false){
                    $exists[]=$intid;
                }else{
                    $sql=$headpart."VALUES (".$value.");";
                    if(\DB::unprepared($sql)){
                        $Success[]=$id;
                    }
                }
            }
            if(count($exists)){$this->infoBlock("Row id:".implode(', ',$exists)." exists Check your dataBase",'Row Exists @ '.$tableName);}
            if(count($Success)){$this->infoBlock("Row id:".implode(', ',$Success)." inserted",'Success @ '.$tableName);}
    }
    function checkIfTableExists($table){
            if(\Illuminate\Support\Facades\Schema::hasTable($table)){return true;}
            return false;
    }
    function createInsertSequance($file){
        $Sequance=[];
        $tableName=(string) Str::of($file)->beforeLast('.');
        $csv = $this->csvToArray($this->path.$file);
        foreach($csv as $a=>$b){
            if(!is_array($b)){
                unset($csv[$a]);
            }
        }
        $headerArray=explode(';',$csv[0][0]);
        for($i=1;$i<count($csv);$i++){
            $line=$csv[$i];
            if(is_array($line)){$line=$line[0];}
            $line=htmlspecialchars_decode($line);
            print($line);
            $line=Str::of($line)->explode(';')->toArray();
            $line=Arr::map($line,function($v,$k){
                if(is_numeric($v)){ $v=(int) $v;}
                elseif(is_bool($v)){$v=(boolean)$v;}
                elseif($v == 'now()'){$v=now()->toDateTimeString();}
                return $v;
            });
            if(count($line) == count($headerArray)){
                $line=array_combine($headerArray,$line);
                $Sequance[]=$line;
            }
        }
        
        
        return $Sequance;
    }
    function insertdata($seq,$tableName){
        $exists=[];$Success=[];
        foreach($seq as $a=>$b){
            $id=$b['id'];
            $RowExists=\DB::table($tableName)->where('id',$id)->get();
            if(count($RowExists)){
                $exists[]=$id;
            }else{
                if(\DB::table($tableName)->insert($b)){
                    $this->infoBlock("Row id:".$id." inserted (".implode(',',$b).")",'Success');
                }else{
                    $this->errorBlock("Row id:".$id." not inserted (".implode(',',$b).")");
                }
            }
        }
        if(count($exists)){$this->infoBlock("Row id:".implode(', ',$exists)." exists Check your dataBase",'Row Exists @ '.$tableName);}
        if(count($Success)){$this->infoBlock("Row id:".implode(', ',$Success)." exists Check your dataBase",'Row Exists @ '.$tableName);}
        
        //\DB::table($tableName)->insert($arr);
    }
    function csvToArray($csvFile){
 
        //$file_to_read = utf8_fopen_read($csvFile);
        if(is_dir($csvFile)){return;}
        $file_to_read = fopen($csvFile,'r');
        while (!feof($file_to_read) ) {
            $lines[] = fgetcsv($file_to_read, 1000, ',');
     
        }
        fclose($file_to_read);
        return $lines;
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
