<?php

error_reporting(E_ALL);

///----------------------------------------------------------------------------------------
// Parse BHL volume info into structured data (cf. BioStor classic)
function parse_volume($text)
{
	// pattern fragments
	
	// volume
	$volume_prefix = '[V|v](ol)?\.?\s*';
	$part_prefix = '[P|p]t\.\s*';
	
	$volume_number_pattern = '(?<volume1>\d+)(-(?<volume2>\d+))?';	
	
	$volume_pattern_1 = $volume_prefix . $volume_number_pattern;
	
	// volume is a year
	$volume_pattern_3 = '^(?<volume1>[0-9]{4})$';
	
	// volume is a part
	//$volume_part_pattern_1 = '^' . $part_prefix . '\s*' . $volume_number_pattern;
	//$volume_part_pattern_2 = '^' . $part_prefix . '\s*' . $volume_number_range_pattern;

	// issue
	$issue_pattern_1 = '(no|pt)\.(?<issue>\d+(-\d+)?)';

	// separator
	$volume_issue_separator = '[:]';

	// dates
	// year in parentheses
	$date_pattern_1 = '(\s*\((?<date>(?<y1>[0-9]{4}))\))';

	// pair of years 
	$date_pattern_2 = '(\s*\((?<date>(?<y1>[0-9]{4})-(?<y2>[0-9]{4}))\))';

	// year with month
	$date_pattern_3 = '(\s*\((?<date>(?<y1>[0-9]{4}):(?<m1>\w+\.))\))';


	// combinations of patterns
	$patterns = array(
		'/' . $part_prefix . $volume_number_pattern . $date_pattern_1 . '/',
		'/' . $part_prefix . $volume_number_pattern . $date_pattern_2 . '/',
	
	
		'/' . $volume_pattern_1 . '(' . $volume_issue_separator . $issue_pattern_1 . ')?' . $date_pattern_1 . '/',
		'/' . $volume_pattern_1 . '(' . $volume_issue_separator . $issue_pattern_1 . ')?' . $date_pattern_2 . '/',
		'/' . $volume_pattern_1 . '(' . $volume_issue_separator . $issue_pattern_1 . ')?' . $date_pattern_3 . '/',

		'/' . $volume_pattern_1 . $date_pattern_2. '/',
		
		'/' . $volume_pattern_3 . '/',
		

		// fallback
		'/' . $volume_pattern_1 . '/',	
	);
	
	//print_r($patterns);
	//exit();

	$num_patterns = count($patterns);

	$matched = false;	
	$matches = array();

	$i = 0;

	while ($i < $num_patterns && !$matched)
	{
		if (preg_match($patterns[$i], $text, $matches))
		{
			$matched = true;
		}
		else
		{
			$i++;
		}
	}

	$obj = new stdclass;
	$obj->text = $text;
	
	if (!$matched)
	{
		$obj->parsed = false;
	}
	else
	{
		$obj->parsed = true;
			
		foreach ($matches as $k => $v)
		{
			if (is_numeric($k))
			{
		
			}
			else
			{
				if ($v != '')
				{
					switch ($k)
					{
						case 'issue':
							$obj->{$k} = array($v);
							break;
						
						case 'volume':
							if (!isset($obj->volume))
							{
								$obj->volume = array();
							}							
							$obj->volume[] = $v;
							break;
													
						case 'volume1':
							if (!isset($obj->volume))
							{
								$obj->volume = array();
							}							
							$obj->volume[] = $v;
							break;
													
						case 'volume2':
							// generate range of volume values 
							$from = $obj->volume[0];
							$to = $v;
							
							if (is_numeric($from) && is_numeric($to))
							{
								for ($i = $from + 1; $i <= $to; $i++)
								{
									$obj->volume[] = (String)$i;
								}
							}
							else
							{	// not a numerical range so just add value												
								$obj->volume[] = $v;
							}
							break;						
						
						case 'm1':
							if (!isset($obj->issued))
							{
								$obj->issued = new stdclass;
								$obj->issued->{'date-parts'} = array();
							}
							$time = strtotime($v);
							if ($time === false)
							{							
							}
							else
							{
								$month = date("n", $time);
								$obj->issued->{'date-parts'}[0][1] = (Integer)$month;
							}
							break;
						
						case 'y1':
							if (!isset($obj->issued))
							{
								$obj->issued = new stdclass;
								$obj->issued->{'date-parts'} = array();
							}
							$obj->issued->{'date-parts'}[0][0] = (Integer)$v;
							break;						
					
						case 'y2':
							if (!isset($obj->issued))
							{
								$obj->issued = new stdclass;
								$obj->issued->{'date-parts'} = array();
							}
							$obj->issued->{'date-parts'}[1][0] = (Integer)$v;
							break;
						
						default:
							break;
					}
				}
			}
		}
	}
	
	return $obj;
}
	
//----------------------------------------------------------------------------------------
// "tests"
if (0)	
{

	$input = array(
	/*
		'v.101 (2004)',
		'v.106:no.3 (2009)',
		'v.28:pt.3-4 (1922)',
		'v.104:no.3 (2007:Dec.)',
		'v.31:pt.3-4 (1926-1927)',
		'v.106:no.2 (2009:Aug.)',
		'v.106:no.1 (2009:Apr.)',
	*/


	"v.45 (1944-1945)",
	"Index:v.25-30 (1928)",
	"v.34:pt.1-2 (1930)",
	"v.26 (1918-1921)",
	"v.105:no.1 (2008:Apr.)",
	"v.97 (2000)",
	"v.71 (1974)",
	"v.99 (2002)",
	"v.106:no.3 (2009)",
	"v.106:no.2 (2009:Aug.)",
	"v.108-109 (2011-2012)",
	"v.41:pt.1-2 (1939)",
	"v.58 (1961)",
	"v.62 (1965)",
	"v.84 (1987)",
	"v.9 (1894-1895)",
	"v.35:pt.3-4 (1932)",
	"v.43 (1942-1943)",
	"v.83 (1986:Dec.;Suppl.:1886-1986)",
	"v.68 (1971)",
	"v.14 (1902-1903)",
	"v.59 (1962)",
	"v.18 (1907-1908)",
	"v.105:no.3 (2008:Dec.)",
	"v.104:no.3 (2007:Dec.)",
	"v.36:pt.1-2 (1932-1933)",
	"v.106:no.1 (2009:Apr.)",
	"v.66 (1969)",
	"v.87 (1990)",
	"v.102:no.1 (2005:Apr.)",
	"v.21:pt.1-2 (1912)",
	"v.89 (1992)",
	"v.16 (1904-1906)",
	"v.40:pt.3-4 (1938-1939)",
	"v.52 (1954-1955)",
	"v.13 (1900-1901)",
	"v.40:pt.1-2 (1938)",
	"v.10 (1895-1897)",
	"v.57 (1960)",
	"v.38:pt.3-4 (1936)",
	"v.107 (2010)",
	"v.76 (1979)",
	"v.83 (1986:Apr-Aug)",
	"v.5 (1890)",
	"v.3 (1888)",
	"v.31:pt.3-4 (1926-1927)",
	"v.38:pt.1-2 (1935)",
	"v.65 (1968)",
	"v.63 (1966)",
	"v.33:pt.1-2 (1929)",
	"v.34:pt.3-4 (1930-1931)",
	"v.72 (1975)",
	"v.98 (2001)",
	"v.79 (1982)",
	"v.95 (1998)",
	"v.11 (1897-1898)",
	"v.1 (1886)",
	"v.80 (1983)",
	"v.7 (1892)",
	"v.60 (1963)",
	"v.92 (1995)",
	"v.27 (1920-1922)",
	"v.93 (1996)",
	"v.69;Index:v.43-53 (1972)",
	"v.31:pt.1-2 (1926)",
	"v.44 (1943-1944)",
	"v.42:pt.3-4 (1942)",
	"v.81 (1984)",
	"Index:v.37-42 (1949)",
	"v.70 (1973)",
	"v.33:pt.3-4 (1929)",
	"v.23 (1914)",
	"v.88 (1991)",
	"v.91:no.3 (1994)",
	"v.42:pt.1-2 (1942)",
	"v.49 (1950-1951)",
	"v.75 (1978:Apr.-Aug.)",
	"v.39:pt.1-2 (1936-1937)",
	"v.105:no.2 (2008:Aug.)",
	"Index:v.25-30 (1928)",
	"v.91:no.1 (1994)",
	"v.25 (1917-1918)",
	"v.56 (1959)",
	"v.48 (1948-1949)",
	"v.20 (1910-1911)",
	"v.64 (1967)",
	"v.29:pt.1-2 (1923)",
	"v.32:pt.1-2 (1927)",
	"Index:v.57,59-60 (1960,1962-1963)",
	"v.101 (2004)",
	"v.67 (1970)",
	"v.36:pt.3-4;Index:v.31-36 (1936)",
	"v.32:pt.3-4 (1928)",
	"v.41:pt.3-4 (1940)",
	"v.100 (2003)",
	"v.37:pt.3-4 (1934-1935)",
	"v.96 (1999)",
	"v.75 (1978:Dec.)",
	"v.6 (1891)",
	"v.102:no.3 (2005:Dec.)",
	"v.104:no.1 (2007:Apr.)",
	"v.55 (1958)",
	"v.30:pt.3-4 (1925)",
	"v.54:no.1-2 (1956-1957)",
	"v.77 (1980)",
	"v.54:no.3-4 (1957)",
	"v.24 (1915-1917)",
	"v.104:no.2 (2007:Aug.)",
	"v.39:pt.3-4 (1937)",
	"v.17 (1906-1907)",
	"v.12 (1898-1900)",
	"v.29:pt.3-4 (1924)",
	"v.19 (1909-1910)",
	"v.21:pt.3-5 (1912)",
	"v.22 (1913)",
	"v.53 (1955-1956)",
	"v.94 (1997)",
	"v.30:pt.1-2 (1924-1925)",
	"v.86 (1989)",
	"v.4 (1889)",
	"v.90 (1993)",
	"v.61 (1964)",
	"v.28:pt.1-2 (1921-1922)",
	"v.102:no.2 (2005:Aug.)",
	"v.28:pt.3-4 (1922)",
	"v.47 (1947-1948)",
	"v.35:pt.1-2 (1931)",
	"v.82 (1985)",
	"v.85 (1988)",
	"v.73 (1976)",
	"v.15 (1903-1904)",
	"v.74 (1977)",
	"v.51 (1952-1953)",
	"v.46 (1946-1947)",
	"v.2 (1887)",
	"v.50 (1951-1952)",
	"v.37:pt.1-2 (1934)",
	"v.8 (1893)",
	"v.78 (1981)"
	
	);

/*
	$input = array(
	'v.75 (1978:Apr.-Aug.)',
	'1922',
	"v.101 (2004)",
	"v.30:pt.1-2 (1924-1925)",
	
	"v.28:pt.1-2 (1921-1922)",
    "v.102:no.2 (2005:Aug.)",
    
    'pt.29-30 (1864)',
    'pt.36 (1866)',
    'pt.1-2 (1854-1855)',
	);
*/

	$input = array(

    'pt.29-30 (1864)',
    'pt.36 (1866)',
    'pt.1-2 (1854-1855)',
	);


	$failed = array();

	foreach ($input as $text)
	{
		$result = parse_volume($text);
		
		if ($result->parsed)
		{
			print_r($result);
		}
		else
		{
			$failed[] = $text;
		}
	}
	

	echo "Failed:\n";
	print_r($failed);
}

?>

