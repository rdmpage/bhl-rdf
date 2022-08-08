<?php


// Generate RDF
require_once(dirname(__FILE__) . '/bhl.php');
require_once(dirname(__FILE__) . '/parse-volume.php');
require_once(dirname(__FILE__) . '/rdf-utils.php');


//----------------------------------------------------------------------------------------
// Add a DOI
function add_doi($graph, $thing, $doi_string)
{
	// ORCID style property-value pair
	$identifier = create_bnode($graph, 'schema:PropertyValue');
	$identifier->add('schema:propertyID', 'doi');
	$identifier->add('schema:value', strtolower($doi_string));	
	$thing->addResource('schema:identifier', $identifier);
	
	// simple text value so we can query directly by DOI
	$thing->add('http://purl.org/ontology/bibo/doi', strtolower($doi_string));
	
	// sameAs DOI
	$thing->addResource('schema:sameAs', 'https://doi.org/' . strtolower($doi_string));
}

//----------------------------------------------------------------------------------------
// Add (cleaned) page number
function add_page_number($page, $PageNumbers)
{
	if (isset($PageNumbers[0]->Number) && ($PageNumbers[0]->Number != ''))
	{
		$value = $PageNumbers[0]->Number;
		$value = preg_replace('/Page%/', '', $value);
		$value = preg_replace('/(Pl\.?(ate)?)%/', '$1 ', $value);

		$page->add('schema:name', $value);
	}	
}

//----------------------------------------------------------------------------------------
// Triples for a page. If $standalone == true then we are calling this independently
// of any item or title, and so we need to flesh out some extra triples that would 
// otherwise already be generated.
function page_to_rdf($PageID, $standalone = true, $basedir = '')
{
	$page_data = get_page($PageID, false, $basedir);
	
	$graph = new \EasyRdf\Graph($page_data->Result->PageUrl);
	
	$page = $graph->resource($page_data->Result->PageUrl, 'schema:CreativeWork');

	// fabio:Page
	$page->addResource('rdf:type', 'http://purl.org/spar/fabio/Page');
	
	// If we are generating RDF for the oage independently of its item then we
	// need some more details
	if ($standalone)
	{
		// page numbers
		if (isset($page_data->Result->PageNumbers[0]))
		{
			add_page_number($page, $page_data->Result->PageNumbers);
		}
	
		// image
		$page->addResource('schema:thumbnailUrl', $page_data->Result->ThumbnailUrl);
	}
			
	// OCR text	
	if ($page_data->Result->OcrText != '')
	{
		$text = $page_data->Result->OcrText;		
		$text = mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text));

		// remove double end of lines
		$text = preg_replace('/\n\n/', "\n", $text);
		
		$page->add('schema:text', $text);
	}
	
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
	
	echo output_triples($graph);
}

//----------------------------------------------------------------------------------------
// Triples for a a part. If $standalone == true then we are calling this independently
// of any item or title, and so we need to flesh out some extra triples that would 
// otherwise already be generated.
function part_to_rdf($PartID, $standalone = true, $basedir = '')
{
	$part_data = get_part($PartID, false, $basedir);

	$graph = new \EasyRdf\Graph($part_data->Result->PartUrl);
	$part = $graph->resource($part_data->Result->PartUrl, 'schema:CreativeWork');
		
	// specific kind of work
	switch ($part_data->Result->GenreName)
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
	$part->addResource('schema:isPartOf', 'https://www.biodiversitylibrary.org/item/' . $part_data->Result->ItemID);
	
	// part name
	$part->add('schema:name', $part_data->Result->Title);
	
	// do we have a DOI?
	if ($part_data->Result->Doi != '')
	{
		add_doi($graph, $part, $part_data->Result->Doi);
	}
	
	// link pages to this part
	foreach($part_data->Result->Pages as $page_data)
	{
		$page = $graph->resource($page_data->PageUrl, 'schema:CreativeWork');
		$page->addResource('schema:isPartOf', $part);
		
		// if adding this as a standalone we need some page info as well
		if ($standalone)
		{
			
		}
	}		
	
	echo output_triples($graph);
}

//----------------------------------------------------------------------------------------
function item_to_rdf($ItemID, $deep = false, $basedir = '')
{
	$item_data = get_item($ItemID, false, $basedir);

	// Construct a graph of the results	
	// Note that we use the URL of the object as the name for the graph. We don't use this 
	// as we are outputting triples, but it enables us to generate fake bnode URIs.	
	$graph = new \EasyRdf\Graph($item_data->Result->ItemUrl);

	$item = $graph->resource($item_data->Result->ItemUrl, 'schema:CreativeWork');
	
	// Items are volumes
	$item->addResource('rdf:type','schema:PublicationVolume');
	
	// pages -----------------------------------------------------------------------------
	// Get basic information for pages such that we can query them by name even if we
	// don't load the pages themselves into the triple store.
	foreach ($item_data->Result->Pages as $page_summary)
	{
		$page = $graph->resource($page_summary->PageUrl, 'schema:CreativeWork');
		// fabio:Page
		$page->addResource('rdf:type', 'http://purl.org/spar/fabio/Page');
		
		// pages belong to items
		$page->addResource('schema:isPartOf', $item);
		
		// generate core RDF for pages as we might not have pages themselves
	
		// page numbers
		if (isset($page_summary->PageNumbers[0]))
		{
			add_page_number($page, $page_summary->PageNumbers);
		}
	
		// image
		$page->addResource('schema:thumbnailUrl', $page_summary->ThumbnailUrl);
				
		if ($deep)
		{
			// do pages, can result in lots of triples including text
			page_to_rdf($page_summary->PageID, false);
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

	echo output_triples($graph);


}

//----------------------------------------------------------------------------------------
function title_to_rdf($TitleID, $basedir = '')
{
	$title_data = get_title($TitleID, $basedir);

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
	
	// title
	$title->add('schema:name', $title_data->Result->FullTitle);
	
	// think about adding alternative titles
	
	// identifiers
	foreach ($title_data->Result->Identifiers as $identifier)
	{
		switch ($identifier->IdentifierName)
		{
			case 'ISSN':
				$title->add('schema:issn', $identifier->IdentifierValue);

				// We can get RDF for ISSN by resolving the corresponding OCLC
				$title->addResource('schema:sameAs','http://www.worldcat.org/issn/' . $identifier->IdentifierValue);
				
				// https://portal.issn.org/resource/ISSN/2589-3831?format=json
				$title->addResource('schema:sameAs','http://issn.org/resource/ISSN/' . $identifier->IdentifierValue);
				break;
				
			case 'OCLC':
				// Resolve at http://experiment.worldcat.org/oclc/2334186.jsonld
				$title->add('http://purl.org/library/oclcnum', $identifier->IdentifierValue);

				$title->addResource('schema:sameAs','http://www.worldcat.org/oclc/' . $identifier->IdentifierValue);
				break;
				
			default:
				break;		
		}	
	}
	
	// do we have a DOI?
	if ($title_data->Result->Doi != '')
	{
		add_doi($graph, $part, $title_data->Result->Doi);
	}
	
	// items are parts of the title
	foreach ($title_data->Result->Items as $item_summary)
	{
		$item = $graph->resource($item_summary->ItemUrl, 'schema:CreativeWork');
		$item->addResource('schema:isPartOf', $title);
		
		// add core metadata
		$item->addResource('schema:url', $item_summary->ItemUrl );
	
		// volume name (may need to parse this)
		if (isset($item_summary->Volume) && ($item_summary->Volume != ''))
		{
			// name as is
			$item->add('schema:name', $item_summary->Volume);	
		
			// parse into clean metadata
			$parse_result = parse_volume($item_summary->Volume);
			if ($parse_result->parsed)
			{
				if (isset($parse_result->volume))
				{
					foreach ($parse_result->volume as $volume)
					{
						$item->add('schema:volumeNumber', 	$volume);					
					}
				}
				if (isset($parse_result->issued))
				{
					foreach ($parse_result->issued->{'date-parts'} as $date_parts)
					{
						$item->add('schema:datePublished', 	(String)$date_parts[0]);					
					}
				}		
			}
			else
			{
				// just use unparsed text
				$item->add('schema:volumeNumber', $item_summary->Volume);
			}
		}				
	}
	
	echo output_triples($graph);
}

/*
$ItemID = 51227; // 1914

//item_to_rdf($ItemID); // 1914

$TitleID = 11516;

//title_to_rdf($TitleID)

page_to_rdf(14779340); // Zalithia euphracta, n. sp.
*/

if (0)
{
	item_to_rdf(121890); //

}

if (1)
{
	// Do all files
	
	$TitleID = 68619;
	$TitleID = 8089;
	
	$basedir = $config['cache'] . '/' . $TitleID;
	
	$files = scandir($basedir);

	foreach ($files as $filename)
	{
		if (preg_match('/title-(?<id>\d+)\.json$/', $filename, $m))
		{	
			title_to_rdf($m['id'], $basedir);
		}	
		
		if (preg_match('/item-(?<id>\d+)\.json$/', $filename, $m))
		{	
			item_to_rdf($m['id'], false, $basedir);
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
 