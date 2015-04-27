<?php

/**
 * SimpleTemplate - Parser
 *
 * @author Michal Vaněk
 */

namespace SimpleTemplate;

class Parser {
	/** @var array */
	private $templateParams;
	/** @var array */
	private $templateParamsTmp;
	/** @var string */
	private $templateContent;
	/** @var string */
	private $templateContentTmp;
	/** @var string */
	private $hashTemplate;

	/**
	 * Sets array of variables to parse.
	 * @param array
	 */
	public function setParams($params){
		$this->templateParams = $params;
	}

	/**
	 * Sets template content.
	 * @param string
	 */
	public function setContent($content){
		$this->templateContent = $content;
	}

	/**
	 * Returns template content.
	 * @return string
	 */
	public function getOutput(){
		return $this->templateContent;
	}

	/**
	 * Parse template content.
	 */
	public function parse(){
		$this->hashTemplate = md5(json_encode($this->templateParams).$this->templateContent);
		$cacheContent = Cache::loadTemplate($this->hashTemplate);
		if(!$cacheContent){
			$this->templateContent = $this->parseLoops($this->templateContent,$this->templateParams);
			$this->templateContent = $this->parseVariables($this->templateContent,$this->templateParams);
			$this->templateContent = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/","\n",$this->templateContent);
			Cache::saveTemplate($this->hashTemplate,$this->templateContent);
		}
		else $this->templateContent = $cacheContent;
	}

	/**
	 * Parse loops.
	 * @param string content to parse
	 * @param array variables to parse
	 * @return string parsed content
	 */
	private function parseLoops($content,$params){
		while(preg_match('|\[#([a-z]+[a-z0-9_\-\[\]]*)\]|i',$content,$matches)){
			$parsedContent = null;
			$loopName = $matches[1];
			$this->templateParamsTmp = $params;
			$loopObject = $this->getVariableTagContent($matches,true);

			/** Check opening and closing tag */
			preg_match('|\[#'.preg_quote($loopName).'\](.*?)\[/#'.preg_quote($loopName).'\]|is',$content,$matches);
			if(count($matches) == 0) throw new Exception("Mismatched loop tag '$loopName'.");

			/** Outer and inner loop content */
			$outsideLoop = $matches[0];
			$insideLoop = $matches[1];

			/** Parse loop content */
			if(isset($loopObject) && is_array($loopObject) && !empty($loopObject)){
				foreach($loopObject AS $loopContent){
					$inlineLoopTmp = $this->parseLoops($insideLoop,$loopContent);
					$parsedContent .= $this->parseVariables($inlineLoopTmp,(is_array($loopContent) ? array_merge($loopContent,$this->templateParams) : $loopContent));
				}
			}

			$content = str_replace($outsideLoop,$parsedContent,$content);
		}
		return $content;
	}

	/**
	 * Parse variables.
	 * @param string content to parse
	 * @param array variables to parse
	 * @return string parsed content
	 */
	private function parseVariables($content,$params){
		$this->templateParamsTmp = $params;
		return preg_replace_callback('|\{#[a-z0-9_\-\[\]\|:,\.\'\s]+\}|i',array($this,'getVariableTagContent'),$content);
	}

	/**
	 * Parse a found variable, detect indexes and filters.
	 * @param array found variable
	 * @param bool is variable from [array] tag
	 * @return string parsed variable
	 */
	private function getVariableTagContent($contentTag,$isArray = false){
		$contentObject = null;
		$contentName = preg_replace('|^\\'.($isArray ? "[" : "{").'#([a-z0-9_\-]+)(.*)$|i','\\1',$contentTag[0]);

		/** Parse array indexes */
		preg_match_all('|\[([a-z0-9_\-]+)\]|i',$contentTag[0],$dimensions);
		$dimensions = $dimensions[1];

		/** Find object */
		if(!is_object($this->templateParamsTmp) && !isset($this->templateParamsTmp[$contentName])) $contentObject = null;
		else {
			if(!empty($dimensions)){
				$contentObject = $this->templateParamsTmp[$contentName];
				foreach($dimensions AS $idx => $dimension){
					$contentObject = $contentObject[$dimension];
				}
			}
			else $contentObject = $this->templateParamsTmp[$contentName];
		}

		if(!$isArray){
			/** Parse filters */
			preg_match_all('|\|([a-z0-9_\-:,\.\'\s]+)|i',$contentTag[0],$filters);
			$filters = $filters[1];

			/** Apply filters */
			if(!empty($filters)){
				foreach($filters AS $filter){
					$contentObject = Filters::applyFilter($contentObject,$filter);
				}
			}
		}

		return $contentObject;
	}
}