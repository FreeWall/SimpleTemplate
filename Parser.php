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
			$this->templateContent = $this->markConditions($this->templateContent);
			$this->templateContent = $this->parseLoops($this->templateContent,$this->templateParams);
			$this->templateContent = $this->parseVariables($this->templateContent,$this->templateParams);
			$this->templateContent = $this->parseConditions($this->templateContent,$this->templateParams);
			$this->templateContent = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/","\n",$this->templateContent);
			Cache::saveTemplate($this->hashTemplate,$this->templateContent);
		}
		else $this->templateContent = $cacheContent;
	}

	/**
	 * Parse [#loops].
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
					$inlineLoopTmp = $this->parseConditions($inlineLoopTmp,$loopContent);
					$inlineLoopTmp = $this->parseVariables($inlineLoopTmp,(is_array($loopContent) ? array_merge($loopContent,$this->templateParams) : $loopContent));
					$parsedContent .= $inlineLoopTmp;
				}
			}

			$content = str_replace($outsideLoop,$parsedContent,$content);
		}
		return $content;
	}

	/**
	 * Parse {#variables}.
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

	/**
	 * Mark opening and closing {if} tag with numbers
	 * @param string content to parse
	 * @return string parsed content
	 */
	private function markConditions($content){
		$ifcount = 0;
		while(preg_match('|\{if #|i',$content,$matches)){
			$ifcount ++;
			$content = preg_replace_callback('|\{if #([#a-z0-9_\-\[\]\s=><!]+)\}(((?!\{if #).)*)\{/if\}|isU',function($tag) use ($ifcount){
				return "{if-".$ifcount." #".$tag[1]."}".$tag[2]."{/if-".$ifcount."}";
			},$content);
		}
		return $content;
	}

	/**
	 * Parse {if} conditions.
	 * @param string content to parse
	 * @param array variables to parse
	 * @return string parsed content
	 */
	private function parseConditions($content,$params){
		$this->templateParamsTmp = $params;

		/** Regular {if} conditions */
		while(preg_match('|\{if-(\d+) #([a-z0-9_\-\[\]]+)\}|i',$content,$matches) || preg_match('|\{if-(\d+) #([a-z0-9_\-\[\]]+)(\s?)([=><!]+)(\s?)([#a-z0-9_\-\[\]]+)\}|i',$content,$matches)){
			$conditionID = $matches[1];
			$conditionName = $matches[2].$matches[3].$matches[4].$matches[5].$matches[6];
			$conditionOperator = $matches[4];
			$conditionOperand = (preg_match('|#([a-z0-9_\-\[\]]+)|i',$matches[6],$matchesTmp) ? $this->getVariableTagContent(array("{".$matches[6]."}")) : $matches[6]);
			$contentObject = $this->getVariableTagContent(array("{#".$matches[2]."}"));

			/** Check opening and closing tag */
			preg_match('|\{if-'.$conditionID.' #'.preg_quote($conditionName).'\}(.*?)\{/if-'.$conditionID.'\}|is',$content,$matches);
			if(count($matches) == 0) throw new Exception("Mismatched if tag '$conditionName'.");

			/** Outer and inner if content */
			$outsideCondition = $matches[0];
			$insideCondition = $matches[1];

			/** Delete body if condition is false */
			if(empty($conditionOperator)){
				if(!isset($contentObject) || empty($contentObject)){
					$insideCondition = null;
				}
			} else {
				switch($conditionOperator){
					case '=':
					case '==':
						if(!($contentObject == $conditionOperand)) $insideCondition = null;
						break;
					case '!=':
						if(!($contentObject != $conditionOperand)) $insideCondition = null;
						break;
					case '<':
						if(!($contentObject < $conditionOperand)) $insideCondition = null;
						break;
					case '>':
						if(!($contentObject > $conditionOperand)) $insideCondition = null;
						break;
					case '<=':
						if(!($contentObject <= $conditionOperand)) $insideCondition = null;
						break;
					case '>=':
						if(!($contentObject >= $conditionOperand)) $insideCondition = null;
						break;
				}
			}

			$content = str_replace($outsideCondition,$insideCondition,$content);
		}

		/** Ternary operators */
		$conditionType = array(0 => 0,1 => 0);
		while($conditionType[0] = preg_match('|\{#([a-z0-9_\-\[\]]+)(\s?)\?(\s?)(.+)(\s?):(\s?)(.+)\}|i',$content,$matches)
		   || $conditionType[1] = preg_match('|\{#([a-z0-9_\-\[\]]+)(\s?)([=><!]+)(\s?)([#a-z0-9_\-\[\]]+)(\s?)\?(\s?)(.+)(\s?):(\s?)(.+)\}|i',$content,$matches)){

			$contentObject = $this->getVariableTagContent(array("{#".$matches[1]."}"));
			$conditionOperator = null;
			if($conditionType[1] == 1){
				$conditionOperator = $matches[3];
				$conditionOperand = (preg_match('|#([a-z0-9_\-\[\]]+)|i',$matches[5],$matchesTmp) ? $this->getVariableTagContent(array("{".$matches[5]."}")) : $matches[5]);
				$contentResult[0] = trim(preg_match('|#([a-z0-9_\-\[\]]+)|i',$matches[8],$matchesTmp) ? $this->getVariableTagContent(array("{".$matches[8]."}")) : $matches[8]);
				$contentResult[1] = trim(preg_match('|#([a-z0-9_\-\[\]]+)|i',$matches[11],$matchesTmp) ? $this->getVariableTagContent(array("{".$matches[11]."}")) : $matches[11]);
			} else {
				$contentResult[0] = trim(preg_match('|#([a-z0-9_\-\[\]]+)|i',$matches[4],$matchesTmp) ? $this->getVariableTagContent(array("{".$matches[4]."}")) : $matches[4]);
				$contentResult[1] = trim(preg_match('|#([a-z0-9_\-\[\]]+)|i',$matches[7],$matchesTmp) ? $this->getVariableTagContent(array("{".$matches[7]."}")) : $matches[7]);
			}
			$conditionType = array(0 => 0,1 => 0);

			/** Outer condition content */
			$outsideCondition = $matches[0];
			$parsedContent = null;

			if(empty($conditionOperator)){
				if(!isset($contentObject) || empty($contentObject)) $parsedContent = $contentResult[1];
				else $parsedContent = $contentResult[0];
			} else {
				switch($conditionOperator){
					case '=':
					case '==':
						if(!($contentObject == $conditionOperand)) $parsedContent = $contentResult[1];
						else $parsedContent = $contentResult[0];
						break;
					case '!=':
						if(!($contentObject != $conditionOperand)) $parsedContent = $contentResult[1];
						else $parsedContent = $contentResult[0];
						break;
					case '<':
						if(!($contentObject < $conditionOperand)) $parsedContent = $contentResult[1];
						else $parsedContent = $contentResult[0];
						break;
					case '>':
						if(!($contentObject > $conditionOperand)) $parsedContent = $contentResult[1];
						else $parsedContent = $contentResult[0];
						break;
					case '<=':
						if(!($contentObject <= $conditionOperand)) $parsedContent = $contentResult[1];
						else $parsedContent = $contentResult[0];
						break;
					case '>=':
						if(!($contentObject >= $conditionOperand)) $parsedContent = $contentResult[1];
						else $parsedContent = $contentResult[0];
						break;
				}
			}

			$content = str_replace($outsideCondition,$parsedContent,$content);
		}
		return $content;
	}
}