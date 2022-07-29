<?php


$config['cache']   = dirname(__FILE__) . '/cache';
$config['BHL_API_KEY'] = '0d4f0303-712e-49e0-92c5-2113a5959159';


//----------------------------------------------------------------------------------------
function get($url)
{
	$data = '';
	
	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 
	curl_setopt ($ch, CURLOPT_HEADER,		  1);  
	
	// timeout (seconds)
	curl_setopt ($ch, CURLOPT_TIMEOUT, 120);

	curl_setopt ($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST,		  0);  
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER,		  0);  
	
	$curl_result = curl_exec ($ch); 
	
	if (curl_errno ($ch) != 0 )
	{
		echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
	}
	else
	{
		$info = curl_getinfo($ch);
		
		// print_r($info);		
		 
		$header = substr($curl_result, 0, $info['header_size']);
		
		// echo $header;
		
		//exit();
		
		$data = substr($curl_result, $info['header_size']);
		
	}
	return $data;
}



//----------------------------------------------------------------------------------------
function get_title($TitleID)
{
	global $config;
	
	$filename = $config['cache'] . '/title-' . $TitleID . '.json';

	if (!file_exists($filename))
	{
		$parameters = array(
			'op' 		=> 'GetTitleMetadata',
			'titleid'	=> $TitleID,
			'items'		=> 't',
			'apikey'	=> $config['BHL_API_KEY'],
			'format'	=> 'json'
		);
	
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);

		$json = get($url);
		file_put_contents($filename, $json);
	}

	$json = file_get_contents($filename);

	$title_data = json_decode($json);
	
	return $title_data;
}


//----------------------------------------------------------------------------------------
function get_item($ItemID, $force = false)
{
	global $config;
	
	// get BHL item
	$filename = $config['cache'] . '/item-' . $ItemID . '.json';

	if (!file_exists($filename) || $force)
	{
		$parameters = array(
			'op' 		=> 'GetItemMetadata',
			'itemid'	=> $ItemID,
			'parts'		=> 't',
			'pages'		=> 't',
			'apikey'	=> $config['BHL_API_KEY'],
			'format'	=> 'json'
		);
	
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
	
		echo $url . "\n";

		$json = get($url);
		file_put_contents($filename, $json);
	}

	$json = file_get_contents($filename);
	$item_data = json_decode($json);
	
	return $item_data;
}



//----------------------------------------------------------------------------------------
function get_part($PartID, $force = false)
{
	global $config;
	
	// get BHL item
	$filename = $config['cache'] . '/part-' . $PartID . '.json';

	if (!file_exists($filename) || $force)
	{
		$parameters = array(
			'op' 		=> 'GetPartMetadata',
			'partid'	=> $PartID,
			'apikey'	=> $config['BHL_API_KEY'],
			'format'	=> 'json'
		);
	
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
				
		echo $url . "\n";

		$json = get($url);
		file_put_contents($filename, $json);
	}

	$json = file_get_contents($filename);
	$part_data = json_decode($json);
	
	return $part_data;

}


//----------------------------------------------------------------------------------------
function get_page($PageID, $force = false)
{
	global $config;
	
	// get BHL item
	$filename = $config['cache'] . '/page-' . $PageID . '.json';

	if (!file_exists($filename) || $force)
	{
		$parameters = array(
			'op' 		=> 'GetPageMetadata',
			'pageid'	=> $PageID,
			'names'		=> 't',
			'ocr'		=> 't',
			'apikey'	=> $config['BHL_API_KEY'],
			'format'	=> 'json'
		);
	
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
				
		echo $url . "\n";

		$json = get($url);
		file_put_contents($filename, $json);
	}

	$json = file_get_contents($filename);
	$page_data = json_decode($json);
	
	return $page_data;
}



?>
