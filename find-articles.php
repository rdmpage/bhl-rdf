<?php

// Look for patterns in page numbers and text

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
		$value = preg_replace('/(No\.)%/', '$1 ', $value);
		$value = preg_replace('/(Tab\.)%/', '$1 ', $value);
	}
		
	return $value;
}

//----------------------------------------------------------------------------------------
// Get page type
function get_page_type($PageType)
{
	$value = array();
		
	foreach ($PageType as $p)
	{
		$value[] = $p->PageTypeName;
	}
		
	return $value;
}

//----------------------------------------------------------------------------------------
function get_current_page($PageID, $basedir)
{
/*
	$page = new stdclass;
	$page->label 	= get_page_number($item_data->Result->Pages[$i]->PageNumbers);					
	$page->type 	= get_page_type($item_data->Result->Pages[$i]->PageTypes);
	$page->id 		= $item_data->Result->Pages[$i]->PageID;
	$page->text 	= $item_data->Result->Pages[$i]->OcrText;

	print_r($item_data->Result->Pages[$i]);*/
	
	$page_data = get_page($PageID, false, $basedir);
	
	//print_r($page_data);
	
	
	$page = new stdclass;
	$page->label 	= get_page_number($page_data->Result->PageNumbers);					
	$page->type 	= get_page_type($page_data->Result->PageTypes);
	$page->id 		= $page_data->Result->PageID;
	//$page->text 	= $page_data->Result->OcrText;	
	
	//print_r($page);
	
	//exit();

	return $page;
}


//----------------------------------------------------------------------------------------
//
function find_breaks($item_data, $basedir)
{
	$state = 0;
	
	$n = count($item_data->Result->Pages);
	
	$i = 0;
	
	$parts = array();
	$part_counter = 0;
	
	
	$page = null;
	
	while ($state != 100)
	{
		echo "state=$state, i=$i\n";
	
	
		switch ($state)
		{
				// start tolken
			case 0:
				if ($i == $n)
				{
					$state = 100;
				}
				else
				{
					$page = get_current_page($item_data->Result->Pages[$i]->PageID, $basedir);	
					
					echo $page->label . "\n";
									
					$i++;
					
					if ($i == $n)
					{
						$state = 100;
					}
					else
					{
						$state = 4;
					}
				}
				break;
				
			case 1:
				echo "emit previous part\n";
				echo "start new part\n";
				
				$part_counter++;
				$parts[$part_counter] = array();
				$parts[$part_counter][] = $page;
				
				echo $page->label . "\n";
			
				$state = 0;
				break;
				
				// traffic directory
			case 4:
				if ($state == 4)
				{					
//					if (preg_match('/^(Tab\.\s+)?(?<number>\d+)/', $page->label, $m))
					if (preg_match('/^(?<number>\d+)/', $page->label, $m))
					{
						$state = 1; // article start
					}
				}
				
				if ($state == 4)
				{		
					if (in_array("Index", $page->type))
					{
						$state = 3;  // nearly end of item
					}					
				}

				if ($state == 4)
				{		
					if (in_array("Title Page", $page->type))
					{
						$state = 1;  // 
					}					
				}
				
				
				if ($state == 4)
				{		
					if (in_array("Text", $page->type))
					{
						$state = 2;  // a text page
					}					
				}			


				if ($state == 4)
				{		
				
					$state = 0;
				}
				break;
				
			case 2:
				echo "Add to current part\n";
				$parts[$part_counter][] = $page;
				$state = 0;
				break;
				
			case 3:
				// done(
				echo "emit last article\n";
				echo "we are done\n";
				$state = 100;
				break;
				
			default:
				break;
		}	
	}
	
	print_r($parts);

}




 
$TitleID = 706;

$basedir = $config['cache'] . '/' . $TitleID;

$files = scandir($basedir);

$files = array('item-14253.json');

foreach ($files as $filename)
{
	if (preg_match('/item-(?<id>\d+)\.json$/', $filename, $m))
	{	
		$json = file_get_contents($basedir . '/' . $filename);

		$item_data = json_decode($json);
		
		
		//print_r($item_data);
		
		find_breaks($item_data, $basedir);
		

		
	}
}

 
?>
 