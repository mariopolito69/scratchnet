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