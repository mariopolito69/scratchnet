<?php
/*
	//$soggetto = "caterina/string1/ciao sono caterina\ncaterina/number1/85612\nbeatrice/string1/ciao sono beatrice\ncaterina/number2/13\n";
	print "string iniziale: "  . $soggetto . "\n";
	print "lunghezza stringa iniziale: " . strlen($soggetto) . "\n";
	print "lunghezza /pushVariables: " . strlen("/pushVariables") . "\n";
//	$soggetto = substr($soggetto,strlen("/pushVariables"),strlen($soggetto) - strlen("/pushVariables") + 1);
	print "tette: " . $soggetto . "\n" ; 
	$parsedDests = array();
	$first = array();
//	preg_match("|[^\&]{1,}|",$soggetto,$first);
//	print $first[0] . "\n";
	preg_match_all("|[^\n]{1,}|",$soggetto,$parsedDests);
	$i = 0 ; 
	while (array_key_exists($i, $parsedDests[0]))
	{
		print  $parsedDests[0][$i] . "\n";
		$i++;
	}
*/
print getVariable("caterina/string1/ciao sono caterina") . "\n";
print preparePush("caterina/string1/ciao sono caterina\ncaterina/number1/85612\nbeatrice/string1/ciao sono beatrice\ncaterina/number2/13\n");

function preparePush($s)
{
 // example
		  /*
			caterina/string1/ciao sono caterina
			caterina/number1/85612
			beatrice/string1/ciao sono beatrice
			caterina/number2/13
		  */
		  
	//TODO prepare variables to be pushed
	$parsedVariables = array();
	preg_match_all('|[^\n]{1,}|',$s,$parsedVariables);
	$i = 0; 
	$s = "pushVariables/";
	$firstTime = true;
	while ($i < count($parsedVariables[0]))
	{
		
		if ($parsedVariables[0][$i] != "READ") // found a new counterpaart sending variables 
		{
			$tmpCounterPart = getCounterpart($parsedVariables[0][$i]);
			if($firstTime == false )
			{				
				$s .= "&";
			}
			else 
			{
				$firstTime = false;
			}
			$s .= 	getCounterpart($parsedVariables[0][$i]) . "/" . getVariable($parsedVariables[0][$i]) . "/" ;
			// mark in order not to be read another time 
			$parsedVariables[0][$i] = "READ";
		}
		
		// now findiding the other occurrences of the same counterpart 
		$j = 0; 
		while($j<count($parsedVariables[0]))
		{
			if  ($tmpCounterPart == getCounterpart($parsedVariables[0][$j]) )
			{
				$s .= getVariable($parsedVariables[0][$j]) . "/";
				// mark in order not to be read another time 
				$parsedVariables[0][$j] = "READ";
			}
			$j++;
		}
		$i++;
	}
	return $s; 
	
}

function markAsRead($s) 
{
	// substitute the counterpart part of a variable string to a fixed value
	return READ . "/" . getVariable($s);
 
}

function getCounterpart($s)
{
	// rempve the  whole variable section in a string made as "caterina/number1/2134"
	// in the aove example returns number1/2134
	$tmpAr = array();
	preg_match_all('|[^\/]{1,}|',$s,$tmpAr);
	return urlencode($tmpAr[0][0]);
}

function getVariable($s)
{
	// rempve the  counterpart section in a string made as "caterina/number1/2134"
	// in the aove example returns number1/2134
	$tmpAr = array();
	preg_match_all('|[^\/]{1,}|',$s,$tmpAr);
	return urlencode($tmpAr[0][1]) . "/" . urlencode($tmpAr[0][2]);
}

?>