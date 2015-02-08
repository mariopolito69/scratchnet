<?php

/*
 * Implement response to Scratch 2.0 Http extension. Implememnets a protocol to exchange variables and commands 
 * Scratch 2.0 via a Central Server 
 *
 */

$GLOBALS['serverName'] = ''; 
$GLOBALS['centralServerConnectionStatus'] = '';
$GLOBALS['counterPart'] = ''; // contains the user i'm speaking with 
$GLOBALS['identifier'] = ''; // identify myself on the scratch network 
$GLOBALS['temporaryStorage'] = array(); //temporary storage used to store duplicate for next polling
$GLOBALS['inputqueue'] =array() ; // queue array used to store variables for poll by scratch
  // SINGLE queue  used to store variables for get by AyncVE 
$respondedText = "";


require_once dirname(__DIR__) . '/httpserver.php';
require_once dirname(__DIR__) . '/SimpleQueue.php';
$GLOBALS['busyqueue'] = new SimpleQueue; 
$GLOBALS['outputqueue'] = new SimpleQueue;

class ScratchLocalServer extends HTTPServer
{
    function __construct()
    {
        parent::__construct(array(
            'port' => 8000,
        ));
    }

    function route_request($request)
    {
        $uri = $request->uri;
        
        $doc_root = __DIR__ . '/example_www';

        if (preg_match('#/$#', $uri))
        {
            $uri .= "index.php";
        }
        
        if (preg_match('#\.php$#', $uri))
        {
			return $this->get_php_response($request, "$doc_root$uri");
        }
        else
        {
//I'm just passing the URI to be processed for i'm not interested in paths
			return $this->get_static_response($request, $uri);
        }                
    }


	function get_static_response($request, $local_path)
    {   
//		print "strpos /stringvalue " . strpos($local_path, "stringvalue") ;
		if (
			($local_path == "/reset_all" ) or		// from Scratch stopping
			($local_path == "/poll" ) or         	// from Scratch polling variables
			($local_path == "/whichserver" )  or 	//from asyncVE requesting the server to set up 
			($local_path == "/whichdest" )  or 		//from asyncVE requesting the counterpart in order to ask from CS ando push the correct variables 
			($local_path == "/whichuser" )  or 		//from asyncVE requesting the identifier of the local user 
			($local_path == "/dumpcommands") or	//FROM asyncVE requesting commandsa to be pushed to the central server 
			($local_path == "/release")	or          //from Scratch - no parameters - allows switch of conuterPart		
			(strpos($local_path, "user") )  or   	//from Scratch setting the user SUPPOSED not to change during a single execution 
			(strpos($local_path, "connectionStatus") )  or   	//from asyncVE pushing OK for connection or error message
			(strpos($local_path, "pushVariables") )  or   	//from asyncVE pushing variables 
			(strpos($local_path, "server") )  or 	//from scratch setting the server  		
			(strpos($local_path, "dest") )  or 		//from scratch setting the counterpart for communication  CAUTION THIS COMMAND WAITS
			(strpos($local_path, "stringvalue") )  or 		//from scratch commanding to pass a string
			(strpos($local_path, "numericvalue") )
			)
        {        
			$headers = array(
                    'Content-Type' => 'text/plain',  // ORIGINAL CODE WAS static::get_mime_type($local_path),
                    'Cache-Control' => "max-age=8640000",
                    'Accept-Ranges' => 'bytes',
            );        
			
			// Starting the call interpreter ---------------------------
			if ($local_path == "/reset_all" )
			{
				$respondedText = "";
				$GLOBALS['identifier'] = ""; 
				$GLOBALS['counterPart'] = ""; 
				$GLOBALS['busyqueue']->Clear();
				$GLOBALS['outputqueue']->Clear();
				$GLOBALS['temporaryStorage'] = array();
				$GLOBALS['inputqueue'] = array();
			}
			else if ($local_path == "/poll" )
			{
				//empty the input queue and package response
				$respondedText = "";	
				$respondedText = pollResponse(); 
			}
			else if ($local_path == "/whichserver" )
			{ 
				$respondedText = $GLOBALS['serverName'];				
			}
			else if ($local_path == "/whichdest" )
			{ 
				$respondedText = $GLOBALS['counterPart'];				
			}
			else if ($local_path == "/whichuser" )
			{ 
				$respondedText = $GLOBALS['identifier'];				
			}
			else if ($local_path == "/dumpcommands")
			{
				//Empty the output queue and respond whith variables 
				$respondedText = getAllOutputQueue();
			}
			else if ($local_path == "/release" )
			{
				
				// get the first dest from queue of dests and assign to GLOBALS['counterPart']
				$tmpBag = new varBag;				
				$tmpQueue = $GLOBALS['busyqueue'];

				if(!$GLOBALS['busyqueue']->isEmpty())
				{
					$tmpBag = $GLOBALS['busyqueue']->get();
					$GLOBALS['counterPart'] = $tmpBag->varName;
				}
				else
				{
					$GLOBALS['counterPart'] = "";
				}
				
 			}
			else if (strpos($local_path, "user"))
			{	
				//set up the global variable representing the identifier of the user that will be used by whichuser request from asyncVE 
				$respondedText = "";
				$local_path = urldecode($local_path);
				$lastSlash = strrpos($local_path,"/");
				// example $local_path =  /user/mario
				$GLOBALS['identifier'] = substr($local_path, $lastSlash+1, strlen($local_path)-strlen("/user/")) ;
			}
			else if (strpos($local_path, "connectionStatus"))
			{	
				//says OK for successful connection or error message for connection refused from central server 
				// example $local_path =  /connectionStatus/Connection%20refused
				$local_path = urldecode($local_path);
				$lastSlash = strrpos($local_path,"/");
				$GLOBALS['centralServerConnectionStatus'] = substr($local_path, $lastSlash+1, strlen($local_path)-strlen("/connectionStatus/")) ; 
				$respondedText = "";
			}
			else if (strpos($local_path, "pushVariables"))
			{	
				//feed the input queue 
				pushVariables(urldecode($local_path));
				$respondedText = "";
			}
			else if (strpos($local_path, "server"))
			{	
				//set up the global variable that will be used by whichserver request from asyncVE 
				$respondedText = "";
				$lastSlash = strrpos($local_path,"/");
				// example $local_path =  /server/123.123.123.21
				$GLOBALS['serverName'] = substr($local_path, $lastSlash+1, strlen($local_path)-strlen("/server/")) ; 
			}
			else if (strpos($local_path, "dest"))
			{	
				//set up the global variable representing the counterpart of the communication  
				// TODO To be changed .. must fill the conunterparts queue
				// example /dest/1345/caterina
				$tmpBusy = array();
				$local_path = urldecode($local_path);
				//remove the first slash
				
				$local_path = substr($local_path, 1, strlen($local_path)-1) ; 

				preg_match_all('|[^\/]{1,}|',$local_path,$tmpBusy);
				$tmpBag = new varBag;
				$tmpBag->varName  = $tmpBusy[0][2];
				$tmpBag->varValue = $tmpBusy[0][1];
				
				if ($GLOBALS['counterPart'] == "" )
				{
					$GLOBALS['counterPart'] = $tmpBag->varName;
				}
				else
				{
					$GLOBALS['busyqueue']->put($tmpBag);
				}

				$respondedText = "";
			}
			else if (strpos($local_path, "stringvalue") || strpos($local_path, "numericvalue"))
			{	
				
				// feed the output queue 
				$local_path = urldecode($local_path);
				$respondedText = "";
				//example /stringvalue/string1/string1value
				//remove the first slash
				$local_path = substr($local_path, 1, strlen($local_path)-1) ;
				$tmpBag = new varCommandBag;
				if ($GLOBALS['counterPart'] == "")
					$respondedText = "No counterpart receiving command: set the counterpart";
				else
				{
					$tmpBusy = array();
					preg_match_all('|[^\/]{1,}|',$local_path,$tmpBusy);
					$tmpBag->dest = $GLOBALS['counterPart'];
					$tmpBag->varName  = $tmpBusy[0][1];
					$tmpBag->varValue = $tmpBusy[0][2];
					$GLOBALS['outputqueue']->put($tmpBag);
				}
			}
			else
			{
				$respondedText = "Bad Command - not from scratch or async VE";
			}
// Must absolutely be determined the varialble $file_size
// ORIGINAL CODE WAS --- $file_size = filesize($local_path); ---- 
			if (isset($respondedText))
			{
				$file_size = strlen($respondedText);
			}
			else
			{
				$respondedText = "";
				$file_size = 0;
			}
            
			if ($request->method === 'HEAD')
            {
                $headers['Content-Length'] = $file_size;
                return $this->response(200, '', $headers);
            }
            else if ($request->method == 'GET')
            {
				$range = $request->get_header('range');                      
//              $file = fopen($local_path, 'rb');

                if ($range && preg_match('#^bytes=(\d+)\-(\d*)$#', $range, $match))
                {        
                    $start = (int)$match[1];
                    $end = (int)$match[2] ?: ($file_size - 1);
                                   
                    if ($end >= $file_size || $end < $start || $start < 0 || $start >= $file_size)
                    {
                        $response = $this->text_response(416, 'Invalid request range');
                    }
                    
                    $len = $end - $start + 1;
                    
                    $headers['Content-Length'] = $len;
                    $headers['Content-Range'] = "bytes $start-$end/$file_size";
                    
                    fseek($file, $start);
                    
                    if ($end == $file_size - 1)
                    {
                        return $this->response(206, $file, $headers);
                    }
                    else
                    {
                        $chunk = fread($file, $len);
                        return $this->response(206, $chunk, $headers);
                    }
                }
                else
                {
                    $headers['Content-Length'] = $file_size;
                    // hopefully file size doesn't change before we're done writing the file            
                    $response = $this->text_response(200, $respondedText);
                }    
            }
            else
            {
                return $this->text_response(405, "Invalid HTTP method {$request->method}");
            }
        
            return $response;
        }
        else if (is_dir($local_path))
        {
            return $this->text_response(403, "Directory listing not allowed");
        }
        else
        {
            return $this->text_response(404, "File not found");
        }    
    }        

}


/* ------------------------  FUNCTIONS CALLED TO MANAGE QUEUES ------------------------------*/
function pollResponse()
{
// this function empty the input queue till the second occurrence of the same variable. 


	$poll = ""; 
	// the following must store each varname in order to avoid duplicates in polling
	$tmpAr = array();
	
	if ($GLOBALS['centralServerConnectionStatus'] == "OK")
	{ 
		$poll .= "servername " . $GLOBALS['serverName'] ."\n";    
	}
	else
	{
		$poll.= "_problem " . $GLOBALS['centralServerConnectionStatus'] . "\n";  
		return $poll;
	}
//  TODO DEBUG manage whith _Busy communication channels 
	$tmpBusy = array();  
	$tmpBusy = $GLOBALS['busyqueue']->getArray();
	$i = 0; 
	for($i == 0; $i < count($tmpBusy); $i++)
	{
		$tmpBag = new varBag; 
		$tmpBag = $tmpBusy[$i];
		$poll .= "_busy " . $tmpBag->varValue . "\n";
	}

	$poll .= "counterpart " . $GLOBALS['counterPart'] . "\n";
	
	// ------------------------------------------------------------------------------------------*/
	$objQueue = new SimpleQueue;
	if(!array_key_exists($GLOBALS['counterPart'],$GLOBALS['inputqueue']))
	{
		$poll .= " no variables queue for the above counterpart \n" ;
		return $poll;
		
	}
	
	$objQueue->setArray($GLOBALS['inputqueue'][$GLOBALS['counterPart']]);
 
	if (array_key_exists($GLOBALS['counterPart'],$GLOBALS['temporaryStorage']))
	{
		$poll.= $GLOBALS['temporaryStorage'][$GLOBALS['counterPart']]->varName . " "  . $GLOBALS['temporaryStorage'][$GLOBALS['counterPart']]->varValue . "\n";
		$tmpAr[$GLOBALS['temporaryStorage'][$GLOBALS['counterPart']]->varName]="";
		unset($GLOBALS['temporaryStorage'][$GLOBALS['counterPart']]);
	}
	
	while(!$objQueue->isEmpty())
	{
		$bag = new varBag;
		$bag = $objQueue->get();

		if(array_key_exists($bag->varName,$tmpAr)) // then variable already polled ... STOP and save in temporary storage for the next poll
		{
			$GLOBALS['temporaryStorage'][$GLOBALS['counterPart']] = new varBag;
			$GLOBALS['temporaryStorage'][$GLOBALS['counterPart']]->varName = $bag->varName; 
			$GLOBALS['temporaryStorage'][$GLOBALS['counterPart']]->varValue = $bag->varValue;
			return $poll;
		}
		$tmpAr[$bag->varName]="";
		$poll .= $bag->varName . " " . " " . $bag->varValue . "\n";
	}
	return  $poll;
}

function pushVariables($strCommands)
{
	
	// example /pushVariables/caterina/frase1/frase1value/frase2/frase2value/frase1/frase1value&beatrice/frase1/fras1value/frase2/frase2value&caterina/frase1/frase1value/frase2/frase2value/frase1/frase1value
	// this removes the part "/pushVariables/"
	$strCommands = substr($strCommands,strlen("/pushVariables/"),strlen($strCommands) - strlen("/pushVariables/") + 1);
	
	$i = 0;
	$parsedCounterPartSet = array();
	// the following regex split in one string per counterPart variable set

	preg_match_all("|[^\&]{1,}|",$strCommands,$parsedCounterPartSet);


	while(array_key_exists($i, $parsedCounterPartSet[0]))
	{
		// the following regex split the variable set in an array of single words ... the first is the conunterpart from which the variable come
		$parsedCommands = array(); 
		preg_match_all("|[^\/]{1,}|", $parsedCounterPartSet[0][$i] ,$parsedCommands);	
		$counterPart = $parsedCommands[0][0];
		//the following sets the array for queue to an empty array if it not yet exists 
		if (!array_key_exists($counterPart, $GLOBALS['inputqueue'] ) ) 
			{
				$GLOBALS['inputqueue'][$counterPart] = array();
			}
//		print "array settato? " . is_array($GLOBALS['inputqueue'][$counterPart]) . "\n";
//		print "counterpart: " . $counterPart . "---\n" ;
		$objQueue = new SimpleQueue;
		$objQueue->setArray($GLOBALS['inputqueue'][$counterPart]);
//		print "array settato dopo il setAtrray della coda? " . is_array($GLOBALS['inputqueue'][$counterPart]) . "\n";

		// the following fills the queue
		$j = 1; 
		while(array_key_exists($j, $parsedCommands[0]))
		{
			$objVarBag = new varBag; 
			$objVarBag->varName = $parsedCommands[0][$j];
			$objVarBag->varValue = $parsedCommands[0][$j+1];
//			print "Counterpart:" . $counterPart .  "---oggetto:" . $objVarBag->varName . " " . $objVarBag->varValue . "\n"; 
			$objQueue->put($objVarBag);
			$j+=2;	
		}
		$i++;

	}

}

function getAllOutputQueue()
{
	$result = "";

	while (!$GLOBALS['outputqueue']->isEmpty())
	{
		$tmpBag = new varCommandBag;
		$tmpBag = $GLOBALS['outputqueue']->get();
		$result .= $tmpBag->dest . "/" . $tmpBag->varName . "/" . $tmpBag->varValue . "\n";
	}

	return $result;
}



class varBag
{
	
	public $varName; 
	public $varValue;
}
 
class varCommandBag
{
	
	public $dest;
	public $varName; 
	public $varValue;
}

/*TEST of pushVariables
$s = "/pushVariables/caterina/frase1/1/frase2/2/frase1/3&beatrice/frase1/fras1value/frase2/frase2value&caterina/frase1/4/frase2/5/frase1/6";
pushVariables($s);
// now empty the queue and print results
$objQueue = new SimpleQueue; 
$objQueue->setArray($GLOBALS['inputqueue']['caterina']);
$result = $objQueue->Get();
while ($result = $objQueue->Get())
{
	print $result->varName . " " . $result->varValue . "\n"; 
} 
//----------------------------------------------------------------------------------------------*/

//enable th following two lines in order to enable the http server

$server = new ScratchLocalServer();
$server->run_forever();
