<?php
namespace SimpleTemplate;

class Parser {
	private $templateParams;
	private $templateParamsTmp;
	private $templateContent;
	private $templateContentTmp;
	private $hashTemplate;

	public function setParams($params){
		$this->templateParams = $params;
	}

	public function setContent($content){
		$this->templateContent = $content;
	}

	public function getOutput(){
		return $this->templateContent;
	}

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

	private function parseVariables($content,$params){
		$this->templateParamsTmp = $params;
		return preg_replace_callback('|\{#[a-z0-9_\-\[\]\|:,\.\'\s]+\}|i',array($this,'getVariableTagContent'),$content);
	}

	private function getVariableTagContent($contentTag,$isArray = false){
		$contentObject = null;
		$contentName = preg_replace('|^\\'.($isArray ? "[" : "{").'#([a-z0-9_\-]+)(.*)$|i','\\1',$contentTag[0]);

		/** Parse array indexes */
		preg_match_all('|\[([a-z0-9_\-]+)\]|i',$contentTag[0],$dimensions);
		$dimensions = $dimensions[1];

		/** Parse filters */
		preg_match_all('|\|([a-z0-9_\-:,\.\'\s]+)|i',$contentTag[0],$filters);
		$filters = $filters[1];

		/** Find object in params */
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

		/** Apply filters */
		if(!empty($filters)){
			foreach($filters AS $filter){
				$contentObject = Filters::applyFilter($contentObject,$filter);
			}
		}

		return $contentObject;
	}
}
?>