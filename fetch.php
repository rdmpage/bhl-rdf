<?php

// grab data

require_once('bhl.php');

/*
$ItemID = 51227;

$item = get_item($ItemID);

print_r($item);

foreach ($item->Result->Parts as $part)
{
	get_part($part->PartID);
}

foreach ($item->Result->Pages as $page)
{
	get_page($page->PageID);
}
*/

$TitleID = 11516;

$title = get_title($TitleID);

print_r($title);

foreach ($title->Result->Items as $title_item)
{
	$item = get_item($title_item->ItemID);

	foreach ($item->Result->Parts as $part)
	{
		get_part($part->PartID);
	}
	
	foreach ($item->Result->Pages as $page)
	{
		get_page($page->PageID);
	}	

}



?>

