<?php

/**
 * SimpleTemplate - PHP template engine
 *
 * @link https://github.com/FreeWall/SimpleTemplate
 * @author Michal Vaněk
 */

namespace SimpleTemplate;

/** Include other files */
require_once __DIR__."/Cache.php";
require_once __DIR__."/Parser.php";
require_once __DIR__."/Filters.php";
require_once __DIR__."/Validate.php";
require_once __DIR__."/Exception.php";

class Engine {
	/** @var Parser */
	private $parser;

	/**
	 * Construct method.
	 * @param array
	 */
	public function __construct($params){
		$this->parser = new Parser();
		$this->parser->setParams($params);
	}

	/**
	 * Cache settings.
	 * @param boolean
	 */
	public function setCache($bool){
		Cache::enabled($bool);
	}

	/**
	 * Load template from file.
	 * @param string file name
	 */
	public function loadTemplate($name){
		if(file_exists($name)){
			$content = file_get_contents($name);
			$this->parser->setContent($content);
		}
		else throw new Exception("File '$name' not found.");
	}

	/**
	 * Load template from string.
	 * @param string
	 */
	public function loadTemplateContent($content){
		$this->parser->setContent($content);
	}

	/**
	 * Add custom filter
	 * @param string
	 * @param callback
	 */
	public function addFilter($s,$callback){
		Filters::addFilter($s,$callback);
	}

	/**
	 * Returns parsed template.
	 * @return string
	 */
	public function getOutput(){
		$this->parser->parse();
		return $this->parser->getOutput();
	}
}