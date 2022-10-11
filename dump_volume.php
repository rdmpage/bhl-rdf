<?php

// dump volume info for an item so we can figure out how to parse it

require_once(dirname(__FILE__) . '/bhl.php');

$TitleID = 7519;
$TitleID = 3882;
$TitleID = 15774;
$TitleID = 62014;
$TitleID = 307;
$TitleID = 6928;

$TitleID = 119516;

/*
119777, // vol 1
119421, // vol 2
119424, // vol 3
119597, // vol 4
119515, // vol 5
119516, // vol 6
*/

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
 