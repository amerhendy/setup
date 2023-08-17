<?php
namespace Amerhendy\Setup\App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
class Addmenu extends Command
{
    use Traits\PrettyCommandOutput;
    protected $signature="Amer:Addmenu {--Code} {--between} {--before} {--after} {--Path} {--timeout=300} {--debug}   {--force}";
    protected $description="Add code TO Amer Menus";
    public $force=false;
    public $Path,$between,$before,$after;
    public function handle()
    {
        if($this->option('force')){$this->force='--force';}
        $this->selectpath();
        $this->between();
        if (! $Code = $this->option('Code')) {
            $def='Amer
            Hendy
            Ali';
            $Code = $this->ask('write HTML Blade Code',$def??'');
        }
        
        $this->addData($Code);
    }
    function selectpath(){
        $menufiles=$this->getMenufiles();
        $this->box('Adding URLS To AMER Menus');
        if (! $Path = $this->option('Path')) {
            $Path = $this->radiooptions('Select The Path',$menufiles,$menufiles[4]);
        }
        
        $newpath='';
        if($Path == '' || $Path == null){
            $this->infoBlock('please select right path');
            return $this->selectpath();
        }
        if(is_numeric($Path)){
            foreach($menufiles as $a=>$b){
                if($Path == $a){$newpath=$b;}
            }
            if($newpath == ''){
                $this->infoBlock('please select right path');
                return $this->selectpath();
            }
            $this->Path=$newpath;
        }
        if(in_array($Path,$menufiles)){
            $this->Path=$Path;
            return;
        }
        $this->infoBlock('please select right path');
                return $this->selectpath();
    }
    function getMenufiles(){
        $files=[];
        if($this->get_loaded_providers('Amerhendy\Amer\AmerServiceProvider') == true){
            $files[]=config('amer.package_path').'resources/views/Amer/Base/page/Header/mainmenu.blade.php';
            $files[]=config('amer.package_path').'resources/views/Amer/Base/page/Header/usersBlock.blade.php';
            $files[]=config('amer.package_path').'resources/views/Amer/Base/page/Header/menu_model.blade.php';
            $files[]=config('amer.package_path').'resources/views/Amer/Base/page/SideBar/layout.blade.php';
            $files[]=base_path().'/resources/views/vendor/Amer/Base/inc/menu/admin.blade.php';
            $files[]=base_path().'/resources/views/vendor/Amer/Base/inc/menu/employer.blade.php';
            $files[]=base_path().'/resources/views/vendor/Amer/Base/inc/menu/employment.blade.php';
            $files[]=base_path().'/resources/views/vendor/Amer/Base/inc/menu/mainmenu.blade.php';
        }
        return Arr::where($files,function($k,$v){return File::exists($k);});
    }
    function between(){
        if(!$between =$this->option('between')){
            $between = $this->confirm('Do you wan to put code between two codes?',true);
        }
        if($between == false){$this->between=false; $this->infoBlock('your code will be in the last of file'); return;}
        $this->before();
        $this->after();
        if($this->before == false && $this->after == false){$this->between=false;}
        
    }
    function before(){
        $path=$this->Path;
        $before=$this->confirm('<fg=white>Do yo want add yor code before any part of file</>',true);
        if($before !== true){
            $this->before=false;
            return ;
        }
        $def='<div class="collapse list-group list-group-flush" id="userpermisions-collapse" style="">';
        $before=$this->ask("please paste the before code",$def??'');
        if(empty($before) || $before == null){$this->before=false;return;}
        $lines=\File::lines($path)->toArray();
        $lcon=$this->getLastLineNumberThatContains($before,$lines);
        if($lcon == false){
            $this->line('');
            $this->errorBlock('code not found in route file');
            return $this->before();
        }
        $this->before=[$before,$lcon];
    }
    function after(){
        $path=$this->Path;
        $after=$this->confirm('<fg=white>Do yo want add yor code after any part of file</>',true);
        if($after !== true){
            $this->after=false;
            return ;
        }
        $def='</div>';
        $after=$this->ask("please paste the after code",$def ?? '');
        if(empty($after) || $after == null){$this->after=false;return;}
        $lines=\File::lines($path)->toArray();
        if(is_array($this->before)){
            $newslice=[];
            $slice=array_slice($lines,$this->before[1]+1);
            foreach($slice as $a=>$b){
                $newslice[$a+$this->before[1]+1]=$b;
            }
            $lines=$slice;
        }
        $lcon=$this->getLastLineNumberThatContains($after,$lines);
        if($lcon == false){
            $this->line('');
            $this->errorBlock('code not found in route file');
            return $this->after($path);
        }
        if(isset($this->before[1])){
            $this->after=[$after,$lcon+$this->before[1]+1];
        }else{
            $this->after=[$after,$lcon-1];
        }
        return;
    }
    function addData($Code){
        $Code= explode("\n", $Code);
        $path=$this->Path;
        $this->progressBlock("Adding code to <fg=blue> $path </>");
        if(\File::exists($path) == false){
            $this->errorBlock("Menu file ".$path." not found please fix the package");
            return $this->selectpath();
        }
        $old_file_path = $path;
        $file_lines = file($old_file_path, FILE_IGNORE_NEW_LINES);
        $mainfilelines=$file_lines;
        if($this->after == false && $this->before == false){
            $mainfilelines=array_merge($file_lines,$Code);
        }
        
        if($this->after == false && $this->before !== false){
            $mainfilelines = array_merge(array_slice($mainfilelines, 0, $this->before[1]+1), $Code, array_slice($mainfilelines, $this->before[1]+1));
        }
        if($this->after !== false && $this->before == false){
            $mainfilelines = array_merge(array_slice($mainfilelines, 0, $this->after[1]+1), $Code, array_slice($mainfilelines, $this->after[1]+1));
        }
        if($this->after !== false && $this->before !== false){
            $mainfilelines = array_merge(array_slice($mainfilelines, 0, $this->after[1]), $Code, array_slice($mainfilelines, $this->after[1]));
        }
        $new_file_content = implode(PHP_EOL, $mainfilelines);
        if (\File::put($this->Path, $new_file_content) == false) {
            $this->errorProgressBlock();
            $this->note('Could not write to file.', 'red');
            return;
        }
        $this->closeProgressBlock();
    }
}
