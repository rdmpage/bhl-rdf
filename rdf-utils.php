<?php

error_reporting(E_ALL);

require_once('vendor/autoload.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

$cuid = new EndyJasmi\Cuid;


//----------------------------------------------------------------------------------------
// Create a uniquely labelled node to use instead of a b-node (Skolemisation)
function create_skolemised_node($graph, $type = "")
{
	global $cuid;
	$sknode = null;

	$node_id = $cuid->cuid();
	
	$graph->getUri() . '#' . $node_id;
	
	if ($type != "")
	{
		$sknode = $graph->resource($uri, $type);
	}
	else
	{
		$sknode = $graph->resource($uri);
	}	
	return $sknode;
}

//----------------------------------------------------------------------------------------
// Create a uniquely labelled b node
function create_bnode($graph, $type = "")
{
	global $cuid;
	$bnode = null;

	$node_id = $cuid->cuid();
	
	$uri = '_:' . $node_id; // b-node
	
	// echo $uri . "\n";
	
	if ($type != "")
	{
		$bnode = $graph->resource($uri, $type);
	}
	else
	{
		$bnode = $graph->resource($uri);
	}	
	return $bnode;
}

//----------------------------------------------------------------------------------------
// Make a URI play nice with triple store
function nice_uri($uri)
{
	$uri = str_replace('[', urlencode('['), $uri);
	$uri = str_replace(']', urlencode(']'), $uri);
	$uri = str_replace('<', urlencode('<'), $uri);
	$uri = str_replace('>', urlencode('>'), $uri);

	return $uri;
}



//----------------------------------------------------------------------------------------
// From easyrdf/lib/parser/ntriples
function unescapeString($str)
    {
        if (strpos($str, '\\') === false) {
            return $str;
        }

        $mappings = array(
            't' => chr(0x09),
            'b' => chr(0x08),
            'n' => chr(0x0A),
            'r' => chr(0x0D),
            'f' => chr(0x0C),
            '\"' => chr(0x22),
            '\'' => chr(0x27)
        );
        foreach ($mappings as $in => $out) {
            $str = preg_replace('/\x5c([' . $in . '])/', $out, $str);
        }

        if (stripos($str, '\u') === false) {
            return $str;
        }

        while (preg_match('/\\\(U)([0-9A-F]{8})/', $str, $matches) ||
               preg_match('/\\\(u)([0-9A-F]{4})/', $str, $matches)) {
            $no = hexdec($matches[2]);
            if ($no < 128) {                // 0x80
                $char = chr($no);
            } elseif ($no < 2048) {         // 0x800
                $char = chr(($no >> 6) + 192) .
                        chr(($no & 63) + 128);
            } elseif ($no < 65536) {        // 0x10000
                $char = chr(($no >> 12) + 224) .
                        chr((($no >> 6) & 63) + 128) .
                        chr(($no & 63) + 128);
            } elseif ($no < 2097152) {      // 0x200000
                $char = chr(($no >> 18) + 240) .
                        chr((($no >> 12) & 63) + 128) .
                        chr((($no >> 6) & 63) + 128) .
                        chr(($no & 63) + 128);
            } else {
                # FIXME: throw an exception instead?
                $char = '';
            }
            $str = str_replace('\\' . $matches[1] . $matches[2], $char, $str);
        }
        return $str;
    }

//----------------------------------------------------------------------------------------
function output_triples($graph)
{
	// Triples 
	$format = \EasyRdf\Format::getFormat('ntriples');

	$serialiserClass  = $format->getSerialiserClass();
	$serialiser = new $serialiserClass();

	$triples = $serialiser->serialise($graph, 'ntriples');

	// Remove JSON-style encoding
	$told = explode("\n", $triples);
	$tnew = array();

	foreach ($told as $s)
	{
		$tnew[] = unescapeString($s);
	}

	$triples = join("\n", $tnew);
	
	return $triples;
}

//----------------------------------------------------------------------------------------
function output_jsonld($graph)
{
	$triples = output_triples($graph);

	$context = new stdclass;
	$context->{'@vocab'} = 'http://schema.org/';
	$context->rdf =  "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
	$context->dwc =  "http://rs.tdwg.org/dwc/terms/";
	
	$context->url = new stdclass;
	$context->url->{'@id'} = 'url';
	$context->url->{'@type'} = '@id';

	$context->sameAs = new stdclass;
	$context->sameAs->{'@id'} = 'sameAs';
	$context->sameAs->{'@type'} = '@id';

	$context->isPartOf = new stdclass;
	$context->isPartOf->{'@id'} = 'isPartOf';
	$context->isPartOf->{'@type'} = '@id';
	
	$context->oclcnum = new stdclass;
	$context->oclcnum->{'@id'} = 'http://purl.org/library/oclcnum';


	// Use same libary as EasyRDF but access directly to output ordered list of authors
	$nquads = new NQuads();
	// And parse them again to a JSON-LD document
	$quads = $nquads->parse($triples);		
	$doc = JsonLD::fromRdf($quads);

	$obj = JsonLD::compact($doc, $context);

	return json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

?>
