<?php
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