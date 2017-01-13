<?php

// Match specimen codes to GBIF occurrences

require_once (dirname(__FILE__) . '/src/reconciliation_api.php');

require_once (dirname(__FILE__) . '/src/fingerprint.php');
require_once (dirname(__FILE__) . '/src/lcs.php');
require_once (dirname(__FILE__) . '/src/lib.php');


//----------------------------------------------------------------------------------------
class FindnciteService extends ReconciliationService
{
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'findncite';
		
		$this->identifierSpace 	= 'http://findncite.org/';
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		$this->Types();
		
		$view_url = '';

		$preview_url = '';	
		$width = 430;
		$height = 300;
		
		if ($view_url != '')
		{
			$this->View($view_url);
		}
		if ($preview_url != '')
		{
			$this->Preview($preview_url, $width, $height);
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function Types()
	{
		$type = new stdclass;
		$type->id = 'https://schema.org/CreativeWork';
		$type->name = 'CreativeWork';
		$this->defaultTypes[] = $type;
	} 	
		
	//----------------------------------------------------------------------------------------------
	// Handle an individual query
	function OneQuery($query_key, $text, $limit = 1, $properties = null)
	{
		global $config;
		
		// clean text
		$text = str_replace(':', '', $text);
		$text = str_replace('"', '', $text);
		
		// BioStor search API
		$url = 'http://localhost/~rpage/findncite-o/api.php?q=' . urlencode($text);
		
		//file_put_contents('/tmp/q.txt', $url, FILE_APPEND);
		
		$json = get($url);
		
		//file_put_contents('/tmp/q.txt', $json, FILE_APPEND);

		if ($json != '')
		{
			$obj = json_decode($json);
			
			if (isset($obj->groups))
			{
				$n = count($obj->groups);
				$n = min($n, 3);
				for ($i = 0; $i < $n; $i++)
				{
					$row = $obj->groups[$i]->rows[0];
					// check 
					
					$v1 = finger_print($text);
					$v2 = finger_print($row->fields->default);
					
					$lcs = new LongestCommonSequence($v1, $v2);
					$d = $lcs->score();
					
					// echo $d;
					
					$score = min($d / strlen($v1), $d / strlen($v2));
					
					if ($score > 0.80)
					{
						$hit = new stdclass;
						$hit->id 	= $row->id;
				
						//$hit->name 	= $row->doc->title;
						$hit->name 	= $row->fields->default;
				
						$hit->score = $score;
						$hit->match = true;
						$this->StoreHit($query_key, $hit);
					}				
				
				
				}
			}
		}
		

		
	}
	
	
}

$service = new FindnciteService();


if (0)
{
	file_put_contents('/tmp/q.txt', $_REQUEST['queries'], FILE_APPEND);
}


$service->Call($_REQUEST);

?>