<?php
class modules_calendar {

	
	/**
	 * @brief Caches the base URL of this module.
	 */
	private $baseURL=null;
	
	private $ontologies = array();


	public function __construct(){
		import('Dataface/Ontology.php');
			
		Dataface_Ontology::registerType('CalendarEvent', dirname(__FILE__).'/ontologies/CalendarEvent.php', 'Dataface_Ontology_CalendarEvent');
			
		// Now work on our dependencies
		$mt = Dataface_ModuleTool::getInstance();
		
		//$mod = $mt->loadModule('modules_calendar');
		
		// We require the XataJax module
		// The XataJax module activates and embeds the Javascript and CSS tools
		$mt->loadModule('modules_XataJax', 'modules/XataJax/XataJax.php');
		
		$app = Dataface_Application::getInstance();
		$app->registerEventListener('beforeHandleRequest', array($this, 'beforeHandleRequest'));
	}

	
	
	public function loadOntology($table){
		if ( !isset($this->ontologies[$table]) ){
	
			$this->ontologies[$table] = Dataface_Ontology::newOntology('CalendarEvent', $table);
		}
		return $this->ontologies[$table];
	}
	
	public function beforeHandleRequest(){
	
		$app = Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		if ( !intval(@$query['--calendar-new-default-all-day']) and @$query['--calendar-new-default-start-date'] ){
			// We have passed the new default start date (intended usually for the
			// new record form.
			// We need to translate this into the correct value for
			// the start date field
			$ontology = $this->loadOntology($query['-table']);
				
			$dateAtt = $ontology->getFieldname('start');
			if ( !PEAR::isError($dateAtt) ){
			
				
				$_GET[$dateAtt] = $query[$dateAtt] = date('Y-m-d H:i:s', floor(floatval($query['--calendar-new-default-start-date'])));
			}
			
			
		}
		
		if ( @$query['--calendar-new-default-all-day'] ){
			// We have passed the new default start date (intended usually for the
			// new record form.
			// We need to translate this into the correct value for
			// the start date field
			
			$ontology = $this->loadOntology($query['-table']);	
			$allDayAtt = $ontology->getFieldname('allday');
			if ( !PEAR::isError($allDayAtt) ){
			
				$_GET[$allDayAtt] = $query[$allDayAtt] = intval($query['--calendar-new-default-all-day']);
			}
			
			
		}
		
		//print_r($query);
	}
	
	
	
	
	/**
	 * @brief Returns the base URL to this module's directory.  Useful for including
	 * Javascripts and CSS.
	 *
	 * @return string The Base URL to this module directory.
	 *
	 */
	public function getBaseURL(){
		if ( !isset($this->baseURL) ){
			$this->baseURL = Dataface_ModuleTool::getInstance()->getModuleURL(__FILE__);
		}
		return $this->baseURL;
	}
	
	
	
	
	
	/**
	 * @brief Gets the name of the repeat settings field for a particular table.
	 * The repeat settings field is a transient field that uses the calendar_repeat_options
	 * widget.
	 *
	 * @param string $tablename The name of the table to search for a repeat settings field.
	 * @return string The name of the field that is used for the repeat options.  Returns null
	 * 	if none is found.
	 *
	 * @see Dataface_Table::transientFields()
	 */
	public function getRepeatSettingsField($tablename){
		$fields = Dataface_Table::loadTable($tablename)->transientFields(true);
		foreach ($fields as $name=>$def){
			if ( $def['widget']['type'] == 'calendar_repeat_options' ){
				return $name;
			}
		}
		return null;
		
	}
	
	
	
	
	
	/**
	 * @brief Handler for beforeSave event.  Basically tallies up all of the
	 * tagger fields that need to be processed.
	 *
	 * @param array $params Parameters.  First element is the record, 2nd element is the IO object.
	 */
	function beforeSave($params){
		$record = $params[0];
		if ( $record ){
			
			foreach ($record->transientFields(true) as $fld){
			
				// Go through all fields in the table to see if any of them
				// are tagger widgets.... we collect them all and record
				if ( @$fld['widget']['type'] == 'calendar_repeat_options' and $record->valueChanged($fld['name']) ){
					
					$ontology = $this->loadOntology($fld['tablename']);
					$repeatAtt = $ontology->getFieldname('repeat');
					if ( PEAR::isError($repeatAtt) ){
						error_log('Attempt to use calendar_repeat_options widget on table with no repeat field identified.  Please mark one of your fields with event.repeat=1 in the fields.ini file.');
						throw new Exception("Failed to update the repeat settings for event because the table has no marked repeat id field.");
						
					}
					
					$repeatId = $record->val($repeatAtt);
					$snapshot = $record->getSnapshot();
					
					$changedFields = array();
					if ( $repeatId ){
						foreach ($record->table()->fields() as $f){
							if ( $record->valueChanged($f['name']) ){
								$changedFields[] = $f['name'];
							}
						}
					}
					
					
					
					
					// Only mark the field for handling if the widget:type=tagger
					// and a relationship is specified and the value has changed.
					if ( !@$record->pouch['calendar_repeat_options__fields'] ){
						$record->pouch['calendar_repeat_options__fields'] = array();
					}
					
					// We store the fields in the record pouch so that we can access them'
					// in the afterSave handler
					$record->pouch['calendar_repeat_options__fields'][] = array(
						'name'=>$fld['name'],
						'snapshot'=> $snapshot[$fld['name']],
						'repeatAtt'=>$repeatAtt,
						'changedFields' => $changedFields
						
					);
					
					break;
				}
			}
		}
	}
	
	
	public function parseRepeatField($val){
		parse_str($val, $out);
		return $out;
	}
	
	public function encodeRepeatField($val){
		return http_build_query($val);
	}
	
	
	/**
	 * @brief Handler for the afterSave() event.  This goes through all changed calendar_repeat_options
	 *  fields and saves any changes to the database.
	 *
	 * 
	 *
	 * @param array $params Array of parameters.  First element is the record, 2nd element is the IO object.
	 */
	function afterSave($params){
		$record = $params[0];
		$io = $params[1];
		
		// The tagger__fields array was populated in the beforeSave handler
		// with the fields that have changed - and use the tagger widget.
		if ( @$record->pouch['calendar_repeat_options__fields'] ){
			foreach ($record->pouch['calendar_repeat_options__fields'] as $struct){
				$f = $struct['name'];
				$oldval = $this->parseRepeatField($struct['snapshot']);
				$repeatAtt = $struct['repeatAtt'];
				$changedFields = $struct['changedFields'];
				
				$repeatId = $record->val($repeatAtt);
				
				$ontology = $this->loadOntology();
				$startAtt = $ontology->getFieldname('start');
				$endAtt = $ontology->getFieldname('end');
				
				
				$tfield =& $record->_table->getField($f);
				
				$val = $this->parseRepeatField($record->val($f));
				
				// The format of this field should be able to reflect:
				//	1. Whether to make changes to all future items
				//  2. The frequency of the repeat
				//list($future, $freq) = explode(' ', $val);
				$future = intval($val['future']);
				$freq = $val['freq'];
				//list($oFuture, $oFreq) = explode(' ', $oldval);
				$oFuture = intval($oldval['future']);
				$oFreq = $oldval['freq'];
				
				if ( $future ){
					if ( $repeatId ){
						// This was already a repeat
						//
						if ( $oFreq != $freq ){
							// We are changing the frequency of the repeat
							$this->updateRepeatFrequency($record, $freq);
						} else if ( $changedFields ){
							// The record has changed fields
							$changes = $record->vals($changedFields);
							
							
							$startTime = $record->val($startAtt);
							$endTime = $record->val($endAtt);
							
							if ( !isset($changes[$startAtt]) ) $startTime = null;
							if ( !isset($changes[$endAtt]) ) $endTime = null;
							
							unset($changes[$startAtt]);
							unset($changes[$endAtt]);
							
							
							
							$this->updateRepeatValues($record, $changes, $startTime, $endTime, $val['expires']);
							
							
						}
					
					} else {
					
						$autofield = $record->table()->getAutoIncrementField();
						if ( !$autofield ){
							throw new Exception("Cannot process repeat events in table because it has no autoincrement fields defined.");
							
						}
						$this->initRepeat($record, $freq, $record->val($autofield), $val['expires'] );
						
						
					
					
					}
				} else {
					// User opted not to make changes to all future items.
				}
				
				
					
			}
			
			unset($record->pouch['calendar_repeat_options__fields']);
		}
	}
	
	
	function newRepeat($sourceObject){
		import(dirname(__FILE__).'/classes/RepeatEvent.class.php');
		return new modules_calendar_RepeatEvent($sourceObject);
	}
	
	public static function dropRepeatTable(){
		import(dirname(__FILE__).'/classes/RepeatEvent.class.php');
		modules_calendar_RepeatEvent::dropRepeatTable();
	}
}