<?php
namespace SimpleTemplate;

require_once __DIR__."/Cache.php";
require_once __DIR__."/Parser.php";
require_once __DIR__."/Filters.php";
require_once __DIR__."/Validate.php";
require_once __DIR__."/Exception.php";

class Engine {
	private $parser;

	public function __construct($params){
		$this->parser = new Parser();
		$this->parser->setParams($params);
	}

	public function setCache($bool){
		Cache::enabled($bool);
	}

	public function loadTemplate($name){
		if(file_exists($name)){
			$content = file_get_contents($name);
			$this->parser->setContent($content);
		}
		else throw new Exception("File '$name' not found.");
	}

	public function loadTemplateContent($content){
		$this->parser->setContent($content);
	}

	public function getOutput(){
		$this->parser->parse();
		return $this->parser->getOutput();
	}
}
?>