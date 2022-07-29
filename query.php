<?php

error_reporting(E_ALL);

require_once('vendor/autoload.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

$config['sparql_endpoint'] = 'http://localhost:7878/query';

	
//----------------------------------------------------------------------------------------
function post($url, $data = '', $accept = 'application/rdf+xml')
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	
	// data needs to be a string
	if ($data != '')
	{
		if (gettype($data) != 'string')
		{
			$data = json_encode($data);
		}	
	}	
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
	$headers = array();
	
	$headers[] = "Content-type: application/sparql-query";
	
	if ($accept != '')
	{
		$headers[] = "Accept: " . $accept;
	}
	
	if (count($headers) > 0)
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
		
	curl_close($ch);
	
	return $response;
}	

//----------------------------------------------------------------------------------------
function do_query($query, $accept)
{
	global $config;
	
	$response = post($config['sparql_endpoint'], $query, $accept);
	
	return $response;
	
}

//----------------------------------------------------------------------------------------
// construct to get text for a page
function get_page_text($PageID)
{
	$query = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	CONSTRUCT
	{
	  ?page schema:text ?text .
	}
	WHERE
	{
	  VALUES ?page { <https://www.biodiversitylibrary.org/page/' . $PageID . '> } .
	  ?page rdf:type ?type .
	  ?page schema:text ?text .
	}';

	$triples = do_query($query, 'application/n-triples');

	// convert to JSON-LD so we can work with it
	$context = new stdclass;
	$context->{'@vocab'} = 'http://schema.org/';
	$context->rdf =  "http://www.w3.org/1999/02/22-rdf-syntax-ns#";

	// Use same libary as EasyRDF but access directly to output ordered list of authors
	$nquads = new NQuads();

	// And parse them again to a JSON-LD document
	$quads = $nquads->parse($triples);		
	$doc = JsonLD::fromRdf($quads);

	$obj = JsonLD::compact($doc, $context);
	
	if (is_array($obj->text))
	{
		$text = $obj->text[0];
	}
	else
	{
		$text = $obj->text;
	}
	
	return $text;
}

//----------------------------------------------------------------------------------------



$text = get_page_text(14779340);


echo $text;





?>



