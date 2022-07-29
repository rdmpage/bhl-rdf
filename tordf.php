<?php


// Generate RDF
require_once('bhl.php');
require_once('rdf-utils.php');


function item_to_rdf($ItemID)
{
	$item_data = get_item($ItemID);

	// Construct a graph of the results	
	// Note that we use the URL of the object as the name for the graph. We don't use this 
	// as we are outputting triples, but it enables us to generate fake bnode URIs.	
	$graph = new \EasyRdf\Graph($item_data->Result->ItemUrl);

	$item = $graph->resource($item_data->Result->ItemUrl, 'schema:CreativeWork');
	
	// Items are volumes
	$item->addResource('rdf:type','schema:PublicationVolume');

	$item->addResource('schema:url', $item_data->Result->ItemUrl );
	
	// volume name (may need to parse this)
	if (isset($item_data->Result->Volume) && ($item_data->Result->Volume != ''))
	{
		$item->add('schema:name', 			$item_data->Result->Volume);		
		$item->add('schema:volumeNumber', 	$item_data->Result->Volume);
	}	
	
	// pages -----------------------------------------------------------------------------
	foreach ($item_data->Result->Pages as $page_summary)
	{
		$page = $graph->resource($page_summary->PageUrl, 'schema:CreativeWork');
		
		// pages belong to items
		$page->addResource('schema:isPartOf', $item);
		
		// page numbers
		if (isset($page_summary->PageNumbers[0]))
		{
			if (isset($page_summary->PageNumbers[0]->Number) && ($page_summary->PageNumbers[0]->Number != ''))
			{
				$value = $page_summary->PageNumbers[0]->Number;
				$value = preg_replace('/Page%/', '', $value);
				$value = preg_replace('/(Pl\.?(ate)?)%/', '$1 ', $value);
			
				$page->add('schema:name', $value);
			}	
		}
		
		// image
		$page->add('schema:thumbnailUrl', $page_summary->ThumbnailUrl);
				
		// need actual page data for text and taxonomic names
		$page_data = get_page($page_summary->PageID);
		
		//print_r($page_data);
		
		// OCR text (?)
		/*
		if ($page_data->Result->OcrText != '')
		{
			$text = $page_data->Result->OcrText;
			
			$text = str_replace("\n", "•", $text);
			
			$page->add('schema:text', $text);
		}
		*/
		
		foreach ($page_data->Result->Names as $Name)
		{
			print_r($Name);
			
			// how to hanfle these, they are both things ands strings
		
		
		}
				

	}		
	
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
			$part->addResource('schema:sameAs', 'https://doi.org/' . $part_summary->Doi);
		}
		
		// to get more info we need the actual part data
		
		// get pages in this part
		$part_data = get_part($part_summary->PartID);
		
		foreach($part_data->Result->Pages as $page_data)
		{
			$page = $graph->resource($page_data->PageUrl, 'schema:CreativeWork');
			$page->addResource('schema:isPartOf', $part);
		}		
		
	}

	
	output($graph);
	
	// dump
	



}

function title_to_rdf($TitleID)
{
	$title_data = get_title($TitleID);

	$graph = new \EasyRdf\Graph($title_data->Result->TitleUrl);

	$title = $graph->resource($title_data->Result->TitleUrl, 'schema:CreativeWork');
	
	// specific kind of work
	switch ($title_data->Result->BibliographicLevel)
	{
		case 'Serial':
			$title->addResource('rdf:type','schema:Periodical');
			break;
	
		default:
			break;	
	}		
	
	$title->add('schema:name', $title_data->Result->FullTitle);
	
	foreach ($title_data->Result->Identifiers as $identifier)
	{
		switch ($identifier->IdentifierName)
		{
			case 'ISSN':
				$title->add('schema:issn', $identifier->IdentifierValue);

				$title->addResource('schema:sameAs','http://www.worldcat.org/issn/' . $identifier->IdentifierValue);
				
				// https://portal.issn.org/resource/ISSN/2589-3831?format=json
				$title->addResource('schema:sameAs','http://issn.org/resource/ISSN/' . $identifier->IdentifierValue);
				break;
				
			case 'OCLC':
				// http://experiment.worldcat.org/oclc/2334186.jsonld
				$title->add('http://purl.org/library/oclcnum', $identifier->IdentifierValue);

				$title->addResource('schema:sameAs','http://www.worldcat.org/oclc/' . $identifier->IdentifierValue);
				break;
				
			default:
				break;
		
		}	
	}
	
	// items are parts of the title
	foreach ($title_data->Result->Items as $item_summary)
	{
		$item = $graph->resource($item_summary->ItemUrl, 'schema:CreativeWork');
		$item->addResource('schema:isPartOf', $title);
	}

	
	echo output_triples($graph);

}

$ItemID = 51227;

//item_to_rdf($ItemID)

$TitleID = 11516;

title_to_rdf($TitleID)
 
 ?>
 