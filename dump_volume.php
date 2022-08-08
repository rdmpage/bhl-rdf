<?php

// dump volume info for an item so we can figure out how to parse it

// Generate RDF
require_once(dirname(__FILE__) . '/bhl.php');
//require_once(dirname(__FILE__) . '/parse-volume.php');

$TitleID = 7519;

$basedir = $config['cache'] . '/' . $TitleID;

$files = scandir($basedir);

$output = array();

foreach ($files as $filename)
{
	if (preg_match('/item-(?<id>\d+)\.json$/', $filename, $m))
	{	
		$json = file_get_contents($basedir . '/' . $filename);
		$item_data = json_decode($json);
		$output[] =  '"' . $item_data->Result->Volume . '"';
	}			
}

echo '$input=array(' . "\n";
echo join(",\n", $output);
echo "\n);\n";
 
?>
 