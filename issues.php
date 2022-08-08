<?php

// Some BHL items have more than volume, or may have multiple issues, each
// starting at page 1. This means multiple pages in an item can have
// the same number. To add precision we could try and sort pages into
// separate series, and then create links between each page and the correpsonding series.
// For exmaple, https://www.biodiversitylibrary.org/item/32857 has 16 issues in a
// single volume. By finding distinct consecutive serie sof page numbers we can sort
// them into distinct issues.

// To do: these series may be issues within a volume, volumes within an item, or some
// combinaton of all of these!

require_once(dirname(__FILE__) . '/bhl.php');


//----------------------------------------------------------------------------------------
// Get (cleaned) page number
function get_page_number($PageNumbers)
{
	$value = '';
	
	if (isset($PageNumbers[0]->Number) && ($PageNumbers[0]->Number != ''))
	{
		$value = $PageNumbers[0]->Number;
		$value = preg_replace('/Page%/', '', $value);
		$value = preg_replace('/(Pl\.?(ate)?)%/', '$1 ', $value);
	}
		
	return $value;
}
//----------------------------------------------------------------------------------------
//
function find_series($item_data)
{
	$output = array();	// 2D array to hold series
	$labels = array(); 	// list of unique page labels
	$series = 0;		// counter for number of series
		
	foreach ($item_data->Result->Pages as $page_summary)
	{
		$label = get_page_number($page_summary->PageNumbers);
		
		if ($label != '')
		{	
			// create series if it doesn't exist	
			if (!isset($output[$series]))
			{
				$output[$series] = array();
			}
				
			// if we've seen this label before assume it's another series	
			if (isset($output[$series][$label]))
			{
				// new series
				$series++;
				$output[$series] = array();
			}
			
			// add label to series
			$output[$series][$label] = $label;
		
			// keep track of unique labels
			if (!in_array($label, $labels))
			{
				$labels[] = $label;
			}
		}			
	}
	
	// display (debugging)
	if (1)
	{
		foreach ($labels as $label)
		{
			echo str_pad($label, 10, ' ', STR_PAD_LEFT);
			echo " | ";

			for ($i = 0; $i < $series; $i++)
			{
				if (isset($output[$i][$label]))
				{
					echo 'x';		
				}
				else
				{
					echo ' ';
				}
			}	
			echo "\n";
		}	
	}
	
	return $output;
}

 
$TitleID = 7519;
$ItemID = 32857;

$basedir = $config['cache'] . '/' . $TitleID;

$files = scandir($basedir);

$files = array('item-32857.json');

foreach ($files as $filename)
{
	if (preg_match('/item-(?<id>\d+)\.json$/', $filename, $m))
	{	
		$json = file_get_contents($basedir . '/' . $filename);
		$item_data = json_decode($json);
		
		
		$output = find_series($item_data);
			
		
		// what are the correspodning series in the metadata?
		$range = array();	
		if (preg_match('/no.(\d+)-(\d+)/', $item_data->Result->Volume, $m))
		{
			for ($i = $m[1]; $i <= $m[2]; $i++)
			{
				$range[] = $i;
			}
		}
		print_r($range);
		
		if (count($range) == count($output))
		{
			echo "Series match\n";
		}
	
		// add to RDF (to do)
		// need to come up with a URI for the parent (e.g., issue)
		

		
	}
}

 
?>
 