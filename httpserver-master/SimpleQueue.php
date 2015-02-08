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
	class SimpleQueue 
	{
		private $arrQueue = array();
		
		public function setArray(array &$ar)
		{
			$this->arrQueue = &$ar; 
		}
		public function getArray()
		{
			return $this->arrQueue;
		}
		public function get()
		{
			$o = &$this->arrQueue[0];
			$this->arrQueue = array_slice( $this->arrQueue, 1 , count($this->arrQueue) - 1 );
			if (count ($this->arrQueue > 0))
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
		
		public function Clear()
		{
			$this->arrQueue = array();
		}
		
		
	}
?>