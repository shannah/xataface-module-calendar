<?php
/**
 * An ontology to represent a person.  Generally people have:
 * Name or First name and last name
 * Email
 * Phone
 * Address
 * City
 * State
 * Postal Code
 * Country
 */

class Dataface_Ontology_CalendarEvent extends Dataface_Ontology {
	function buildAttributes(){
		$this->fieldnames = array();
		$this->attributes = array();
		
		$start = null;
		$end = null;
		$location = null;
		$category = null;
		$allday = null;
		$repeat = null;
		$repeat_seed = null;
		
		// First let's find the email field.
		
		// First we'll check to see if any fields have been explicitly 
		// flagged as email address fields.
		foreach ( $this->table->fields(false,true) as $field ){
			if ( @$field['event.start'] ){
				$start = $field['name'];
				
			}
			if ( @$field['event.end'] ){
				$end = $field['name'];
			
			}
			if ( @$field['event.location'] ){
				$location = $field['name'];
			} 
			if ( @$field['event.category'] ){
				$category = $field['name'];
			}
			
			if ( @$field['event.allday'] ){
				$allday = $field['name'];
			}
			
			if ( @$field['event.repeat'] ){
				$repeat = $field['name'];
			}
			
			if ( @$field['event.repeat_seed'] ){
				$repeat_seed = $field['name'];
			}
			
			
		}
		
		if ( !isset($start) ){
			// Next lets see if any of the fields actually contain the word
			// email in the name
			$candidates = preg_grep('/(date|start|time)/i', array_keys($this->table->fields(false,true)));
			foreach ( $candidates as $candidate ){
				if ( $this->table->isDate($candidate) ){
					$start = $candidate;
					break;
				}
			}
		}
		
		if ( !isset($end) ){
			// Next lets see if any of the fields actually contain the word
			// email in the name
			$candidates = preg_grep('/(time|end|finish|fin|close|stop|date)/i', array_keys($this->table->fields(false,true)));
			foreach ( $candidates as $candidate ){
				if ( $this->table->isDate($candidate) ){
					if ( $candidate == $start ) continue;
					$end = $candidate;
					break;
				}
			}
		}
		
		
		
		if ( !isset($location) ){
			// Next lets see if any of the fields actually contain the word
			// email in the name
			$candidates = preg_grep('/(location|place|addr|venue)/i', array_keys($this->table->fields(false,true)));
			foreach ( $candidates as $candidate ){
				if ( in_array($this->table->getType($candidate), array('enum','varchar','char','int') ) ){
					if ( $this->table->isInt($candidate) ){
						$field =& $this->table->getField($candidate);
						if ( @$field['vocabulary'] ){
							$location = $candidate;
							break;
						}
					} else {
						$location = $candidate;
						break;
					}
				}
			}
		}
		
		if ( !isset($category) ){
			// Next lets see if any of the fields actually contain the word
			// email in the name
			$candidates = preg_grep('/(cat|type)/i', array_keys($this->table->fields(false,true)));
			foreach ( $candidates as $candidate ){
				if ( in_array($this->table->getType($candidate), array('enum','varchar','char','int') ) ){
					if ( $this->table->isInt($candidate) ){
						$field =& $this->table->getField($candidate);
						if ( @$field['vocabulary'] ){
							$category = $candidate;
							break;
						}
					} else {
						$category = $candidate;
						break;
					}
				}
			}
		}
		
		
		if ( !isset($allday) ){
			// Next lets see if any of the fields actually contain the word
			// email in the name
			$candidates = preg_grep('/(all|full|todo)/i', array_keys($this->table->fields(false,true)));
			foreach ( $candidates as $candidate ){
				if ( in_array($this->table->getType($candidate), array('tinyint','boolean') ) ){
					$allday = $candidate;
					break;
					
				}
			}
		}
		
		
		if ( !isset($repeat) ){
			// Next lets see if any of the fields actually contain the word
			// email in the name
			$candidates = preg_grep('/(repeat|recurr|)/i', array_keys($this->table->fields(false,true)));
			foreach ( $candidates as $candidate ){
				if ( in_array($this->table->getType($candidate), array('int','mediumint') ) ){
					$repeat = $candidate;
					break;
					
				}
			}
		}
		
		if ( !isset($repeat_seed) ){
			$repeat_seed = $this->table->getAutoIncrementField();
			
		}
		if ( !isset($repeat_seed) ){
			$keys = array_keys($this->table->keys());
			if ( !isset($keys[1]) and $this->table->isInt($keys[0]) ){
				$repeat_seed = $keys[0];
			}
		}
		
		$atts = array('start'=>$start, 'end'=>$end, 'location'=>$location, 'category'=>$category, 'allday'=>$allday, 'repeat'=>$repeat, 'repeat_seed'=>$repeat_seed);
		foreach ($atts as $key=>$val ){
			if ( isset($val) ){
				$field =& $this->table->getField($val);
				$this->attributes[$key] =& $field;
				unset($field);
				$this->fieldnames[$key] = $val;
			}
		}
		
		return true;
		
	}
	
	
}
