<?php

Class NarniaGD
{
	protected $selfpath = null;
	protected $blacklist = null;
	protected $searchStart = null;
	protected $searchEnd = null;
	protected $uniquelist = array();
    protected $newLine = "\n";

	function __construct() {

		date_default_timezone_set('Europe/Riga');
		echo 'Narnia Guardian';
		$this->selfpath = realpath(dirname(__FILE__));
		$this->blacklist = file($this->selfpath.'/blacklist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$this->uniquelist = file($this->selfpath.'/uniquelist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$this->searchStart = '<?php';
		$this->searchEnd = '?>';

        $this->newLine = isset($_SERVER["SERVER_NAME"]) ? "</br>" : "\n";
	}
	function logSuccess($root, $string) {
        echo "# " . $string;
		$escaped = preg_replace('/[^A-Za-z0-9_\-]/', '_', $root);
		file_put_contents($this->selfpath.'/logs/main.log',date('Y-m-d G:i').' '.$string.PHP_EOL, FILE_APPEND);
		file_put_contents($this->selfpath.'/logs/root-'.$escaped.'.log',date('Y-m-d G:i').' '.$string.PHP_EOL, FILE_APPEND);
	}

	function cleanMess($curContent){
		foreach ($this->blacklist as $bad){
			$pos = strpos($curContent,$bad);
			if($pos){
				$start=strrpos(substr($curContent,0,$pos), $this->searchStart);
				$end=$pos+strpos(substr($curContent,$pos), $this->searchEnd)+strlen($this->searchEnd);
				$lenght=$end-$start;
				if ($lenght<($pos+strlen($this->searchEnd)+10)){
					$this->logSuccess('error-'.$root,'This is messed up with PHP tags '.$path);
				}
				echo $bad.' pos '.$pos.' start '.$start.' end '.$end.' len '.$lenght.$this->newLine;
				if (($start < $end) && ($end < strlen($curContent))){
					$curContent=substr_replace($curContent,"",$start,$lenght);
				} else {
					$this->logSuccess('error-'.$root,'This is messed up with PHP tags '.$path);
				}
			}
		}
		return $curContent;
	}

	function getUnique($string, $path){
		$strings = explode("\n", $string);
		if (!in_array($strings[0], $this->uniquelist)) {
			$this->uniquelist[]=$strings[0];
			echo $path.$this->newLine;
			echo $strings[0].$this->newLine;
		}
	}

	public function cleanFiles($root){
		unlink($root . "/license.php");
		$time_start = (float) array_sum(explode(' ',microtime()));
		$iter = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST,
				RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
				);

		foreach ($iter as $path) {
			if ($path->getExtension()=='php') {
				$dirty=file_get_contents($path);
				$clean = $this->cleanMess($dirty);
				if ($dirty<>$clean){
					$savethis = file_put_contents($path,$clean);
					if ($savethis){
						$this->logSuccess($root,'Cleaned up '.$path);
					}
					$savethis = null;
				}
				$count = substr_count($clean,'\\');
				if ($count>1000){
					$this->logSuccess('error-'.$root,'This is BAD FILE '.$count.' '.$path);
				}
				$this->getUnique($clean, $path);

			}
		}


		$time_end = (float) array_sum(explode(' ',microtime()));
		$time_diff = "Processing $root time: ". sprintf("%.4f", ($time_end-$time_start))." seconds";
		$this->logSuccess($root,  $time_diff);
		file_put_contents($this->selfpath.'/logs/main-scripttime.log',date('Y-m-d G:i').' '.$time_diff.PHP_EOL, FILE_APPEND);
	}

	function __destruct() {
		echo 'Lets go sleep';
		$output = null;
		foreach ($this->uniquelist as $line){
			$output=$output.$line.PHP_EOL;
		}
		file_put_contents($this->selfpath.'/logs/uniquelist.txt',$output);
		// print_r($this->uniquelist);
	}
}

// cli entry
if (!count(debug_backtrace()))
{
	if (count($argv)) {
		$Guard = new NarniaGD;
		$Guard->cleanFiles(realpath($argv[1]));
	} else {
		echo "Usage: php {$argv[0]} [relative path]";
	}
}

