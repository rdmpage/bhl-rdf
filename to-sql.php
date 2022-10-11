<?php

// Generate SQL for BHL metadata

error_reporting(E_ALL);


// Generate RDF
require_once(dirname(__FILE__) . '/bhl.php');
require_once(dirname(__FILE__) . '/parse-volume.php');
require_once(dirname(__FILE__) . '/find-issues.php');

/*
//----------------------------------------------------------------------------------------
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
*/	

//----------------------------------------------------------------------------------------
// Add page from page data
function page_to_sql($PageID, $basedir = '')
{
	$page_data = get_page($PageID, false, $basedir);
	
	$number = '';

	// page numbers
	if (isset($page_data->Result->PageNumbers[0]))
	{
		$number = get_page_number($page_data->Result->PageNumbers);			
	}
	
	$keys = array();
	$values = array();
	
	$keys[] = 'PageID';
	$values[] = $page_data->Result->PageID;
	
	$keys[] = 'ItemID';
	$values[] = $page_data->Result->ItemID;		
	
	if ($number != '')
	{
		$keys[] = 'number';
		$values[] = $number;				
	}
	
	if ($page_data->Result->OcrText != '')
	{
		$text = $page_data->Result->OcrText;		
		$text = mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text));

		// remove double end of lines
		$text = preg_replace('/\n\n/', "\n", $text);
		
		//$text = preg_replace("/\n/", '\n', $text);
		
		$keys[] = 'text';
		$values[] = '"' . str_replace('"', '""', $text) . '"';				
	}
	
	echo 'REPLACE INTO bhl_page(' . join(',', $keys) . ') VALUES (' . join(',', $values) . ');' . "\n";
	
	/*
	// what is the best way to model this?
	// can we make these annotations?
	foreach ($page_data->Result->Names as $Name)
	{
		// Taxonomic name 
		$uri = '';
		
		if ($uri == '')
		{
			if ($Name->NameBankID != '')
			{
				$uri = 'urn:lsid:ubio.org:namebank:' . $Name->NameBankID;
			}
		}	
		
		if ($uri != '')
		{
			$taxonName = $graph->resource($uri, 'schema:TaxonName');			
		}
		else
		{
			$taxonName = create_bnode($graph,  'schema:TaxonName');
		}
		
		// name strings
		$taxonName->add('schema:name', $Name->NameFound);			
		if ($Name->NameConfirmed != '')
		{
			if ($Name->NameFound != $Name->NameConfirmed)
			{
				$taxonName->add('schema:alternateName', $Name->NameConfirmed);
			}
		}
		
		// page is about this name		
		$page->addResource('schema:about', $taxonName);		
		
		// page is about this taxon (EOL)
		if ($Name->EOLID != '')
		{
			$uri = 'https://eol.org/pages/' . $Name->EOLID;
			$page->addResource('schema:about', $uri);				
		}
	
	}
	*/
}


//----------------------------------------------------------------------------------------
// get page and part data
function item_to_sql($ItemID, $deep = false, $basedir = '')
{
	$item_data = get_item($ItemID, false, $basedir);
	
	// sequence/volume/issue
	
	// map pages to chunk
	
	// maybe need a table [title, item, sequence, sequence_name, pageid]
	// this is what we would do the lookup ON
	// by default, only one sequence per item, labelled by the Volume
	// if more than 1 sequences then each is labelled by indivual name(
	// if everything in table indexed by item we can edit to handle cases where things go wrong.
	
	// get tuples representing page sequences in an item
	$item_series = find_sequences($item_data);
			
	if (isset($item_data->Result->Volume) && ($item_data->Result->Volume != ''))
	{		
		// parse into clean metadata
		$parse_result = parse_volume($item_data->Result->Volume);

		// get labels for series that might exit in item
		if ($parse_result->parsed)
		{
			// print_r($parse_result);
			
			$sequence_labels = array();
			
			// 1. Assume series are in volumes
			if (isset($parse_result->volume))
			{
				foreach ($parse_result->volume as $volume)
				{
					$sequence_labels[] = $volume;
				}
			}
						
			// do we do a sanity check?

			// add labels			
			$item_series->sequence_labels = $sequence_labels;
		}
	}
	
	// output sequence(s) of pages
	tuples_to_sql($item_series);
	
	// pages -----------------------------------------------------------------------------
	// Get basic information for pages
	foreach ($item_data->Result->Pages as $page_summary)
	{
		$number = '';
	
		// page numbers
		if (isset($page_summary->PageNumbers[0]))
		{
			$number = get_page_number($page_summary->PageNumbers);			
		}
				
		if ($deep)
		{
			// do pages in detail, including text
			page_to_sql($page_summary->PageID, $basedir);
		}
		else
		{
			// we will just add this level of detail
			$keys = array();
			$values = array();
			
			$keys[] = 'PageID';
			$values[] = $page_summary->PageID;
			
			$keys[] = 'ItemID';
			$values[] = $page_summary->ItemID;		
			
			if ($number != '')
			{
				$keys[] = 'number';
				$values[] = $number;				
			}
		
			echo 'REPLACE INTO bhl_page(' . join(',', $keys) . ') VALUES (' . join(',', $values) . ');' . "\n";
		}
	}	
	
	/*
	// parts ----------------------------------------------------------------------------
	foreach ($item_data->Result->Parts as $part_summary)
	{
		$part = $graph->resource($part_summary->PartUrl, 'schema:CreativeWork');
		
		// specific kind of work
		switch ($part_summary->GenreName)
		{
			case 'Article':
				$part->addResource('rdf:type','schema:ScholarlyArticle');
				break;

			case 'Chapter':
				$part->addResource('rdf:type','schema:Chapter');
				break;
		
			default:
				break;		
		}		
		
		// a part is a part of an item
		$part->addResource('schema:isPartOf', $item);
		
		// part name
		$part->add('schema:name', $part_summary->Title);
		
		// do we have a DOI?
		if ($part_summary->Doi != '')
		{
			add_doi($graph, $part, $part_summary->Doi);
		}
		
		// to get more info we need the actual part data
		
		// link pages in this part to the part (they are already linked to item)
		$part_data = get_part($part_summary->PartID, false, $basedir);
		
		foreach($part_data->Result->Pages as $page_data)
		{
			$page = $graph->resource($page_data->PageUrl, 'schema:CreativeWork');
			$page->addResource('schema:isPartOf', $part);
		}		
		
	}
	*/

	
}

//----------------------------------------------------------------------------------------
function title_to_sql($TitleID, $basedir = '')
{
	$title_data = get_title($TitleID, $basedir);
	
	// basic title info
	
	$keys = array();
	$values = array();
	
	
	$keys[] = 'TitleID';
	$values[] = $TitleID;

	$keys[] = 'title';
	$values[] = '"' . str_replace('"', '""', $title_data->Result->FullTitle) . '"';
	
	// think about adding alternative titles
	
	// identifiers
	foreach ($title_data->Result->Identifiers as $identifier)
	{
		switch ($identifier->IdentifierName)
		{
			case 'ISSN':
				$keys[] = 'issn';
				$values[] = '"' . str_replace('"', '""', $identifier->IdentifierValue) . '"';
				break;
				
			case 'OCLC':
				$keys[] = 'oclc';
				$values[] = '"' . str_replace('"', '""', $identifier->IdentifierValue) . '"';
				break;
				
			default:
				break;		
		}	
	}
	
	// do we have a DOI?
	if ($title_data->Result->Doi != '')
	{
		$keys[] = 'doi';
		$values[] = '"' . str_replace('"', '""', $title_data->Result->Doi) . '"';
	}
	
	echo 'REPLACE INTO bhl_title(' . join(',', $keys) . ') VALUES (' . join(',', $values) . ');' . "\n";
	
	
	// items are parts of the title
	foreach ($title_data->Result->Items as $item_summary)
	{
		$keys = array();
		$values = array();
		
		$keys[] = 'ItemID';
		$values[] = $item_summary->ItemID;		
	
		$keys[] = 'TitleID';
		$values[] = $TitleID;
		
		// and volume string as "title" of this volume"
		if (isset($item_summary->Volume) && ($item_summary->Volume != ''))
		{
		
			$keys[] = 'title';
			$values[] = '"' . str_replace('"', '""', $item_summary->Volume ) . '"';
		}		
		
		echo 'REPLACE INTO bhl_item(' . join(',', $keys) . ') VALUES (' . join(',', $values) . ');' . "\n";		
	}
}


if (1)
{
	
	// Generic names of moths (one title plus item)
	$TitleID = 119777; // v1	
	$basedir = $config['cache'] . '/' . $TitleID;	
	$files = scandir($basedir);
	
	
	$TitleID = 79076; // Nota Lep

	// $TitleID = 9241; // Exotic
	$basedir = $config['cache'] . '/' . $TitleID;	
	$files = scandir($basedir);
	/*
	$files = array(
		'title-' . $TitleID . '.json',
		'item-129153.json',
		);
	*/
	
	// parts
	$TitleID = 79076; // Nota Lep
	$files = array(
		'title-' . $TitleID . '.json',
		'item-179934.json',
		);
	
	
	$deep = false;
	$deep = true; // include text and names
	
	/*
	$files = array(
		'title-15774.json',
		//'item-84522.json',
	);
	*/

	foreach ($files as $filename)
	{
		// title
		if (preg_match('/title-(?<id>\d+)\.json$/', $filename, $m))
		{	
			title_to_sql($m['id'], $basedir);
		}	
		
		// item
		if (preg_match('/item-(?<id>\d+)\.json$/', $filename, $m))
		{	
			item_to_sql($m['id'], $deep, $basedir);
		}			
	}
}

if (0)
{
	//page_to_rdf(22099786); 
	
	// part_to_rdf(21039);
	
	part_to_rdf(20816);
	

}



 
 ?>
 