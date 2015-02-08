<?php

//$GLOBALS['tette'] = array(); 
$GLOBALS['culo'] = array();
class varBag
{
	public function __construct()
	{
		print "costruttore\n";
	}
	public $uno = "rurru\n";
	public $due = "cuccu\n"; 
}
function test()
{
	$ar = array();
	$vb = new varBag; 
	$ar[2] = $vb; 
	$vb = new varBag; 
	$ar[4] = $vb;
	$vb = new varBag; 
	$ar[5] = $vb;
	$ar = array_slice($ar,1,2);
	$arKeys = array_keys($ar);
	print "prima" . $arKeys[0] . "seconda:" . $arKeys[1]; 
}

class SLSInputQueue 
{
	private $arrQueue = array();
	
	public function setArray(array &$ar)
	{
		$this->arrQueue = &$ar; 
	}
	public function get()
	{
		$o = &$this->arrQueue[0];
		$this->arrQueue = array_slice( $this->arrQueue, 1 , count($this->arrQueue) - 1 );
		if count ($this->arrQueue > 0)
		{
			return $o;
		}
		else
		{
			return null;
		}
	}
	public function Put( &$objQueueItem  )
	{
		$this->arrQueue[count($this->arrQueue)] = $objQueueItem; 
		return true;
	}
	public function isEmpty()
	{
		if (count($this->arrQueue) == 0 ) 
			{return true;}
		else
			{
				return false;
			} 
	}
}

function testQueue()
{
	$q = new SLSInputQueue; 
	$vb = new varBag;
	$q->put($vb); 
	$vb = new varBag;
	$q->put($vb); 
	$vb = new varBag;
	$q->put($vb);
	while(!$q->isEmpty())
	{
		$vbret = $q->get(); 
		print $vbret->uno;
	}
}

testQueue();
 
?>