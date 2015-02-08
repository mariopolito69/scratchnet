<?php
//TEST
//print "caterina/string1/ciao sono prova\ncaterina/number1/85612\nbeatrice/string1/ciao sono beatrice\ncaterina/number2/13";
$destination = ""; 
$destination =  $_REQUEST['destination'];

if ($destination == null)
{
	print "incorrect user\n";
	die;
}

$connection = mysql_connect("localhost:3306","root");
if(!$connection)
{
	print "error in connecting to db: " . mysql_error();
	die; 
}

$db = mysql_select_db("scratchnet",$connection);
if (!$db)
{
	print "error in accessing db: " . mysql_error();
	die; 
} 

$response = ""; 
$q = "SELECT source, varname, vartype, varnumber, varstring FROM commands WHERE destination = '" . $destination . "'";
$res = mysql_query($q,$connection);
if (!$res)
{
	die;
}

while($row = mysql_fetch_array($res))
{
	if ($row['vartype'] == "S")
	{
		$response .= $row['source'] . "/" . $row['varname']  . "/" . $row['varstring'] . "\n" ; 
	}
	else
	{
		$response .= $row['source'] . "/" . $row['varname']  . "/" . $row['varnumber'] . "\n" ;
	}	
}



$q = "DELETE FROM commands WHERE destination = '" . $destination . "'"; 
$res = mysql_query($q,$connection);

//now remove the last \n
$response = substr($response,0,strlen($response)-1);
print $response;

?>