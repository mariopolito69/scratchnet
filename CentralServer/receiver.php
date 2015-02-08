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
$source = ""; 
$commands = "";

$source =  $_POST['source'];
$commands =  $_POST['commands'];

/* TEST --------------
$source = 'mario';
$commands = "caterina/string1/ciao sono mario\nbeatrice/number1/1942.23\ncaterina/number1/123";
// ---------------------*/
if ($source == null)
{
	print "incorrect user\n";
	die;
}
if ($commands == null)
{
	print "no commands coming from " . $source . "\n"; 
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

$finalRes = ""; 
// now parse commands 
if($commands != null && $source != null)
{ 
	$paparsedCommands = array();
	preg_match_all('|[^\n]{1,}|',$commands,$parsedCommands);
	$i = 0; 
	while ($i < count($parsedCommands[0]) )
	{
		preg_match_all('|[^\/]{1,}|',$parsedCommands[0][$i],$tmpAr);
		if(isset($tmpAr[0][0]) && isset($tmpAr[0][1]) && isset($tmpAr[0][2]))
		{	
			if (is_numeric($tmpAr[0][2]))
			{
				$q = "INSERT INTO commands(source,destination,varname,varnumber,vartype) 
				VALUES ('" . $source . "','" . $tmpAr[0][0] . "','" . $tmpAr[0][1] . "'," . $tmpAr[0][2] . ",'N')";
			}
			else
			{
				$q = "INSERT INTO commands(source,destination,varname,varnumber,varstring,vartype) 
				VALUES ('" . $source . "','" . $tmpAr[0][0] . "','" . $tmpAr[0][1] . "',null,'" . $tmpAr[0][2] . "','S')";
				$finalRes .= $q . "\n";
			}
			$res = mysql_query($q, $connection); 
			if (!$res) 
			{
				print "error inserting command: " . $q . "\n" . mysql_error();
				die;
			}
		}
		else
		{
			print "incorrect command: " . $parsedCommands[0][$i];
		}
		$i++;
	}
}
print "All commands successfully received";

?>