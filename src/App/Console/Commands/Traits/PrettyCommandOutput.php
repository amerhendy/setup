<?php

namespace Amerhendy\Setup\App\Console\Commands\Traits;

use Artisan;
use Illuminate\Console\Command;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
//designed by backpackfor laravel
trait PrettyCommandOutput
{
    /**
     * Run a SSH command.
     *
     * @param  string  $command  The SSH command that needs to be run
     * @param  bool  $beforeNotice  Information for the user before the command is run
     * @param  bool  $afterNotice  Information for the user after the command is run
     * @return mixed Command-line output
     */
    public function executeProcess($command, $beforeNotice = false, $afterNotice = false)
    {
        $this->echo('info', $beforeNotice ? ' '.$beforeNotice : implode(' ', $command));

        // make sure the command is an array as per Symphony 4.3+ requirement
        $command = is_string($command) ? explode(' ', $command) : $command;

        $process = new Process($command, null, null, null, $this->option('timeout'));
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->echo('comment', $buffer);
            } else {
                $this->echo('line', $buffer);
            }
        });

        // executes after the command finishes
        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if ($this->progressBar ?? null) {
            $this->progressBar->advance();
        }

        if ($afterNotice) {
            $this->echo('info', $afterNotice);
        }
    }

    /**
     * Run an artisan command.
     *
     * @param  string  $command  the artisan command to be run
     * @param  array  $arguments  key-value array of arguments to the artisan command
     * @param  bool  $beforeNotice  Information for the user before the command is run
     * @param  bool  $afterNotice  Information for the user after the command is run
     * @return mixed Command-line output
     */
    public function executeArtisanProcess($command, $arguments = [], $beforeNotice = false, $afterNotice = false)
    {
        //dd('php artisan '.implode(' ', (array) $command).' '.implode(' ', $arguments));
        $beforeNotice = $beforeNotice ? ' '.$beforeNotice : 'php artisan '.implode(' ', (array) $command).' '.implode(' ', $arguments);
        $this->echo('info', $beforeNotice);
        
        try {
            Artisan::call($command, $arguments);
        } catch (Exception $e) {
            throw new ProcessFailedException($e);
        }
        if ($this->progressBar ?? null) {
            $this->progressBar->advance();
        }
        if ($afterNotice) {
            $this->echo('info', $afterNotice);
        }
    }

    /**
     * Write text to the screen for the user to see.
     *
     * @param  string  $type  line, info, comment, question, error
     * @param  string  $content
     */
    public function echo($type, $content)
    {
        if ($this->option('debug') == false) {
            return;
        }
        // skip empty lines
        if (trim($content)) {
            $this->{$type}($content);
        }
    }

    /**
     * Write a title inside a box.
     *
     * @param  string  $header
     */
    public function box($header, $color = 'green')
    {
        $line = str_repeat('─', strlen($header));

        $this->newLine();
        $this->line("<fg=$color>┌───{$line}───┐</>");
        $this->line("<fg=$color>│   $header   │</>");
        $this->line("<fg=$color>└───{$line}───┘</>");
    }
    public function errorbox($header,$body, $color = 'green')
    {
        $line = str_repeat('─', strlen($body));
        $this->newLine();
        $this->line("<fg=$color>┌───{$line}───┐</>");
        $this->line("<fg=red>$header</>");
        $this->line("<fg=$color>│   $body   │</>");
        $this->line("<fg=$color>└───{$line}───┘</>");
    }

    /**
     * List choice element.
     *
     * @return void
     */
    public function listChoice(string $question, array $options, string $default = 'no', string $hint = null)
    {
        foreach ($options as $key => $option) {
            $value = $key + 1;
            $this->progressBlock("<fg=yellow>$value</> ".$option[name]);
            $this->closeProgressBlock($option->status, $option->statusColor ?? '');
            foreach ($option->description ?? [] as $line) {
                $this->line("    <fg=gray>{$line}</>");
            }
            $this->newLine();
        }

        return $this->ask(" $question", $default);
    }
    public function radiooptions(string $question, array $options, string $default = 'no', string $hint = null)
    {
        $this->asks($question);
        foreach($options as $a=>$b){
            $this->line("<fg=blue;bg=white>[$a]</> $b");
        }
        return $this->ask('',$default);
    }
    /**
     * Default info block element.
     *
     * @return void
     */
    public function infoBlock(string $text, string $title = 'info', string $background = 'blue', string $foreground = 'white')
    {
        $this->newLine();
        // low verbose level (-v) will display a note instead of info block
        if ($this->output->isVerbose()) {
            if ($title !== 'info') {
                $text = "$text <fg=gray>[<fg=$background>$title</>]</>";
            }

            return $this->line("  $text");
        }
        if(strstr($text,PHP_EOL)){
            $text=(\Str::of($text)->explode(PHP_EOL));
            $text=$text[0];
        }
        $this->line(sprintf("<fg=$foreground;bg=$background> %s </> $text", strtoupper($title)));
        $this->newLine();
    }

    /**
     * Default error block element
     * Shortcute to info block with error message.
     *
     * @return void
     */
    public function errorBlock(string $text)
    {
        $this->infoBlock($text, 'ERROR', 'red');
    }

    /**
     * Note element, usually used after an info block
     * Prints an indented text with a lighter color.
     *
     * @return void
     */
    public function note(string $text, string $color = 'gray', string $barColor = 'gray')
    {
        $this->line("  <fg=$barColor>│</> $text", "fg=$color");
    }

    /**
     * Progress element generates a pending in progress line block.
     *
     * @return void
     */
    public function progressBlock(string $text, string $progress = 'running', string $color = 'blue')
    {
        $this->maxWidth = $this->maxWidth ?? 128;
        $this->terminal = $this->terminal ?? new Terminal();
        $width = min($this->terminal->getWidth(), $this->maxWidth);
        $dotLength = $width - 5 - strlen(strip_tags($text.$progress));

        // In case it doesn't fit the screen, add enough lines with dots
        $textLength = strlen(strip_tags($text)) + 20;
        $dotLength += floor($textLength / $width) * $width;

        $this->consoleProgress = $progress;

        $this->output->write(sprintf(
            "  $text <fg=gray>%s</> <fg=$color>%s</>",
            str_repeat('.', max(1, $dotLength)),
            strtoupper($progress)
        ));
    }

    /**
     * Closes a progress block after it has been started.
     *
     * @return void
     */
    public function closeProgressBlock(string $progress = 'done', string $color = 'green')
    {
        $deleteSize = max(strlen($this->consoleProgress ?? ''), strlen($progress)) + 1;
        $newDotSize = $deleteSize - strlen($progress) - 1;

        $this->deleteChars($deleteSize);

        $this->output->write(sprintf(
            "<fg=gray>%s</> <fg=$color>%s</>",
            $newDotSize > 0 ? str_repeat('.', $newDotSize) : '',
            strtoupper($progress),
        ));
        $this->newLine();
    }

    /**
     * Closes a progress block with an error.
     *
     * @return void
     */
    public function errorProgressBlock(string $text = 'error')
    {
        $this->closeProgressBlock($text, 'red');
    }

    /**
     * Deletes one or multiple lines.
     *
     * @return void
     */
    public function deleteLines(int $amount = 1)
    {
        $this->output->write(str_repeat("\033[A\33[2K\r", $amount));
    }
    public function asks(string $question){
        $this->line("<fg=red>".$question."</>");
    }
    public function askmultilines($question,array $lines){
        $this->asks($question);
        foreach($lines as $line){
            $this->line("   <fg=blue>".$line."</>");
        }
        return $this->ask('');
    }
    /**
     * @return void
     */
    public function askHint(string $question, array $hints, string $default)
    {
        $hints = collect($hints)
            ->map(function ($hint) {
                return " <fg=gray>│ $hint</>";
            })
            ->join(PHP_EOL);
        return $this->ask($question.PHP_EOL.$hints, $default);
    }

    /**
     * Deletes one or multiple chars.
     *
     * @return void
     */
    public function deleteChars(int $amount = 1)
    {
        $this->output->write(str_repeat(chr(8), $amount));
    }
    function publishfiles($from,$to){
        if(isset($this->sourcePath)){
            $sourceFile = $this->sourcePath.$from;
        }else{
            $sourceFile =$from;
        }
        
        $copiedFile=$to;
        if(!File::exists($sourceFile)){
            $this->errorBlock('can not publish files<fg=cyan;bg=red>'.$sourceFile.'</> at line '.__LINE__);
            exit();
        }
        $canCopy = true;
        if(File::exists($copiedFile)){
            if($this->force == false){
                $canCopy = $this->confirm('File already exists at <bg=blue>'.$copiedFile.'</> - do you want to overwrite it?');
            }else{
                $canCopy = true;
            }
        }
        if ($canCopy) {
            $path = pathinfo($copiedFile);
            if (! file_exists($path['dirname'])) {
                File::makeDirectory($path['dirname'], 0755, true,$this->force);
            }
            if(File::isFile($sourceFile)){if (copy($sourceFile, $copiedFile)) {$this->info('Copied to '.$copiedFile);}else{return $this->error('Failed to copy '.$sourceFile.' to '.$copiedFile.' for unknown reason');}
            }elseif(File::isDirectory($sourceFile)){
             if(File::copyDirectory($sourceFile, $copiedFile)){$this->info('Copied to '.$copiedFile);}else{return $this->error('Failed to copy '.$sourceFile.' to '.$copiedFile.' for unknown reason');}
            }
            
        }
    }
    public function replaceinfile($file,$start,$replace){
        $sourcefile=$file;
        if(!File::exists($sourcefile)){
            return $this->error('file not exists '.$sourcefile);
        }
        $file_lines = file($sourcefile, FILE_IGNORE_NEW_LINES);
        $ln=$this->getLastLineNumberThatContains($start, $file_lines);
        if (!$ln) {
            $this->closeProgressBlock($start." not found", 'yellow');
            return;
        }
        $file_lines[$ln]=$replace;
        $new_file_content = implode(PHP_EOL, $file_lines);
        if (! File::put($sourcefile, $new_file_content)) {
            $this->errorProgressBlock();
            $this->note('Could not write to file.', 'red');

            return;
        }
    }
    public function checkIfCodeExists($needle,$haystack){
        $haystack=Str::squish($haystack);
        $needle=Str::squish($needle);
        $haystack = trim(preg_replace('/\s\s+/', ' ', $haystack));
        $haystack = trim(preg_replace('/ /', '', $haystack));
        $needle = trim(preg_replace('/\s\s+/', ' ', $needle));
        $needle = trim(preg_replace('/ /', '', $needle));
        $matching=Str::contains($needle,$haystack);
        return $matching;
    }
    public function getLastLineNumberThatContains($needle, $haystack,$skipcomment=false)
    {
        $matchingLines = array_filter($haystack, function ($k) use ($needle,$skipcomment) {
            if($skipcomment == true){
                if(!Str::startsWith(trim($k),'//')){
                    return strpos($k, $needle) !== false;
                }
            }else{
                    return strpos($k, $needle) !== false;
            }
            
        });
        if ($matchingLines) { 
            return array_key_last($matchingLines);
        }

        return false;
    }   
    public function customRoutesFileEndLine($file_lines)
    {
        // in case the last line has not been modified at all
        $end_line_number = array_search('}); // this should be the absolute last line of this file', $file_lines);

        if ($end_line_number) {
            return $end_line_number;
        }

        // otherwise, in case the last line HAS been modified
        // return the last line that has an ending in it
        $possible_end_lines = array_filter($file_lines, function ($k) {
            return strpos($k, '});') === 0;
        });

        if ($possible_end_lines) {
            end($possible_end_lines);
            $end_line_number = key($possible_end_lines);

            return $end_line_number;
        }
    }
    public function get_loaded_providers($provider=null){
        $prov=$this->getLaravel()->getLoadedProviders();
        if($provider == null){return $prov;}
        if(array_key_exists($provider,$prov)){
            return true;
        }
        return false;
    }
    
}
