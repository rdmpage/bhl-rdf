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

$TitleID = 11516;	// Transactions of the Entomological Society of London

$TitleID = 7414; 	// journal of the Bombay Natural History Society
$TitleID = 58221; 	// List of the specimens of lepidopterous insects in the collection of the British Museum
$TitleID = 53882; 	// Bulletin of the British Museum (Natural History) Entomology
$TitleID = 112965; 	// Muelleria: An Australian Journal of Botany
$TitleID = 157010; 	// Telopea: Journal of plant systematics
//$TitleID = 128759; // Nuytsia: journal of the Western Australian Herbarium

$TitleID = 44963; 	// Proceedings of the Zoological Society of London
$TitleID = 45481; 	// Genera insectorum

// to do 
$TitleID = 116503; 	// Annals of the Transvaal Museum
$TitleID = 12260; 	// Deutsche entomologische Zeitschrift Iris

$TitleID = 6525; 	// Proceedings of the Linnean Society of New South Wales
$TitleID = 168319; 	// Transactions of the Royal Society of South Australia

$TitleID = 79076; 	// Nota lepidopterologica

// bulk
$titles = array(
87655,	// Horae Societatis Entomologicae Rossicae, variis sermonibus in Rossia usitatis editae
2510,	// Proceedings of the Entomological Society of Washington
8630, // Stettiner Entomologische Zeitung
8641, // Entomologische Zeitung
47036, //Jahresbericht des Entomologischen Vereins zu Stettin
8646, // The Entomologist's monthly magazine
6928, // Annals of the South African Museum
10088, // Tijdschrift voor entomologie
);

$titles = array(
14019, 		// The Proceedings of the Royal Society of Queensland
8187, 		// Bulletin de la Société entomologique de France
82093, 		// Lepidopterorum catalogus
7422, 		// Canadian entomologist
46204, 		// Berliner entomologische Zeitschrift 
46203, 48608, 48608, //  Deutsche entomologische Zeitschrift

60455, 		// Atti della Società italiana di scienze naturali
16255, 		// Atti Soc. ital. sci. nat., Mus. civ. stor. nat. Milano
2356, 		// Entomological news
);

$titles=array(
68619 , 	// Insects of Samoa *
8089, 		// Journal of the New York Entomological Society *
16211, 		// Bulletin of the Brooklyn Entomological Society
8981, 		// Revue suisse de zoologie
49392, 49174, 43750, // Stuttgarter Beiträge zur Naturkunde

// think about doing issue mapping for this journal
7519, 		// Proceedings of the United States National Museum

);

$titles = array(
3882, // Novitates zoologicae *
);

$titles = array(
//15774, // Annals and magazine of natural history*
62014, // Die Grossschmetterlinge der Erde
);

$titles = array(
706, //Curtis
307, // bot mag
);


$deep = false;
$deep = true;

$force = false;

foreach ($titles as $TitleID)
{
	$dir = $config['cache'] . '/' . $TitleID;

	if (!file_exists($dir))
	{
		$oldumask = umask(0); 
		mkdir($dir, 0777);
		umask($oldumask);
	}


	$title = get_title($TitleID, $dir);

	print_r($title);

	foreach ($title->Result->Items as $title_item)
	{
		$item = get_item($title_item->ItemID, $force, $dir);

		foreach ($item->Result->Parts as $part)
		{
			get_part($part->PartID, $force, $dir);
		}
	
		// don't get pages if we have lots 
		if ($deep)
		{
			foreach ($item->Result->Pages as $page)
			{
				get_page($page->PageID, $force, $dir);
			}
		}

	}
}

// to help debug volume parsibng
/*
$input = array();

$title = get_title($TitleID);
foreach ($title->Result->Items as $title_item)
{
	$item = get_item($title_item->ItemID);

	$input[] = '"' . $item->Result->Volume . '"';
}

echo "\n";
echo '$input=array(' . "\n";
echo join(",\n", $input);
echo "\n);\n";

*/

?>

