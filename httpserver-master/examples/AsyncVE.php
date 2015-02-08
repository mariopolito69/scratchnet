<?php
/*
    Copyright 2015 Mario Polito
	This file is part of Scratchnet.

    Scratchnet is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Scratchnet is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with scratchnet.  If not, see <http://www.gnu.org/licenses/>.
*/

/* THIS DEAMON is part of ScratchLocalServer. HAS THE DUTY of getting variables from a SCRATCH local server and send it to a Central Server
and vice versa  */ 

$GLOBALS['localport'] = 0; 
$GLOBALS['remoteport'] = 0; 
$GLOBALS['connectiontest'] = "";
$GLOBALS['output'] = ""; 
$GLOBALS['input'] = ""; 

// Search in command line the port for local scratch and central server
$i = 0; 
while ($i < count($argv))
{
//	print $argv[$i] . "\n";
	if(strstr($argv[$i], "lp:")) //local server listening port              
	{
		$GLOBALS['localport'] = substr($argv[$i],3,strlen($argv[$i]) - 3);
		print "Getting Scratch Local Server on port: " . $GLOBALS['localport'] . "\n";
	}
	else if (strstr($argv[$i], "rp:"))      //central server listening port 
	{
		$GLOBALS['remoteport'] = substr($argv[$i],3,strlen($argv[$i]) - 3);
		print "Communicating whith Central Server on port: " . $GLOBALS['remoteport'] . "\n";
	}
	else if (strstr($argv[$i], "ct:"))   // central server connection test script
	{
		$GLOBALS['connectiontest'] = substr($argv[$i],3,strlen($argv[$i]) - 3);
		print "The test script for connection is: " . $GLOBALS['connectiontest'] . "\n";
	}
	else if (strstr($argv[$i], "os:"))   // central server output script
	{
		$GLOBALS['output'] = substr($argv[$i],3,strlen($argv[$i]) - 3);
		print "The output script is: " . $GLOBALS['output'] . "\n";
	}
	else if (strstr($argv[$i], "is:"))   // central server input script
	{
		$GLOBALS['input'] = substr($argv[$i],3,strlen($argv[$i]) - 3);
		print "the input script is: " . $GLOBALS['input'] . "\n";
	}
	$i++;
}

if($GLOBALS['localport'] == 0 || $GLOBALS['remoteport'] == 0)
{
	print "ports not set ... pass them using php AsyncVe.php lp:XXXX - rp:YYYY  ... where XXXX is the port of local Scratch server and YYYY is the port for Central Server ";
	die;
}

print "start listening from Scratch local server on port: " . $GLOBALS['localport'] . "\n";
	$localHost = "http://localhost:";
	$remoteServer = "";

while ($remoteServer == null) 
{
	
	//get central server to connect with from Scratch local server
	$remoteServer = file_get_contents($localHost . $GLOBALS['localport'] . "/whichserver");
	if ( $remoteServer != null)
	{
		print "start sending to central server " . $remoteServer . " on port: " . $GLOBALS['remoteport'] . "\n";
		// TODO Read a simple GET from remote and return /connectionStatus to ScratchLS
		$conStat = "Unable to read from Server"; 
		$conStat = file_get_contents("http://" . $remoteServer . ":" . $GLOBALS['remoteport'] . "/" . $GLOBALS['connectiontest']) ;
		if ($conStat == null)
		{
			$conStat = "Unable to read from Server";
			$remoteServer = null;
		}
		// The string "OK" is the correct response from Central Server ... otherwhise continue to try to do a correct connection test
		if ($conStat != "OK")
		{
			$remoteServer = null;
		}
		$conStat =  urlencode($conStat);
		$url = "localhost:" . $GLOBALS['localport'] . "/connectionStatus/" . $conStat;
		print "sending to scratch LS connction test result: " . $url . "\n";
		file_get_contents("http://" . $url) ;
		
		// -----------------------------------------------
	}		
	else
	{
		print "Central Server not set ... doing nothing\n";
	    sleep(5);
	}
	
}

while (true)
{
	$user = ""; 

	//get user from local server if null does't do anything else 
	$user = file_get_contents($localHost . $GLOBALS['localport'] . "/whichuser");
	if	( $user == null)
	{
		print "Local User not set not set ... doing nothing\n";
		sleep(5);
	}
	else
	{
		print "User set at: " . $user . "\n";
	}
	 
	// get commands from Scratch local server and get variables from central server and routes both
	if ($user != "") 
	{
		$commands = file_get_contents($localHost . $GLOBALS['localport'] . "/dumpcommands"); 
		// prepare commands to be posted in the format for Central server 	
		$commands = "source=" . $user . "&" . "commands=" . $commands;
		print "sending to Central Server: " . $commands . "\n";
		if ($GLOBALS['output'] != null)
		{
		  //send commands
			$commandResponse = do_post_request("http://" . $remoteServer . "/" . $GLOBALS['output'], $commands);
			print "result from sending commands: " . $commandResponse . "\n";
		}
		
		// now must get input from CS  
		if ($GLOBALS['input'] != null)
		{
		  //  get variables .. 
		  print "getting variables from: " . "http://" . $remoteServer . ":". $GLOBALS['remoteport'] . "/" . $GLOBALS['input'] . "?destination=" . $user . "\n";
		  $variables = file_get_contents("http://" . $remoteServer . ":". $GLOBALS['remoteport'] . "/" . $GLOBALS['input'] . "?destination=" . $user ); 
		  
		  //$variables = "caterina/string1/ciao sono caterina\ncaterina/number1/85612\nbeatrice/string1/ciao sono beatrice\ncaterina/number2/13"; 
		  $variables = preparePush($variables);
		  print "sending to ScratchLS: " . $variables . "\n";
		  file_get_contents($localHost . $GLOBALS['localport'] . $variables);
		}
		  
		  // send variables to SCratcLS
		  
		
		}
		
		
}
	
 
	
 
function do_post_request($url, $data, $optional_headers = null)
{
  $params = array('http' => array(
              'method' => 'POST',
              'content' => $data
            ));
  if ($optional_headers !== null) {
    $params['http']['header'] = $optional_headers;
  }
  $ctx = stream_context_create($params);
  $fp = @fopen($url, 'rb', false, $ctx);
  if (!$fp) {
    throw new Exception("Problem with $url, $php_errormsg");
  }
  $response = @stream_get_contents($fp);
  if ($response === false) {
    throw new Exception("Problem reading data from $url, $php_errormsg");
  }
  return $response;
}

class varBag
{
	
	public $varName; 
	public $varValue;
}

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
	$s = "/pushVariables/";
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
	return substr($s, 0, strlen($s) - 1); 
	
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