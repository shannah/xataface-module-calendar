<?php
/**
 * @brief A class that encapsulates the "repeating" aspects of an event.  It wraps the
 * source record and the repeat info record and provides utility methods to load, save, 
 * and delete repeat instances of the event.
 */
class modules_calendar_RepeatEvent {

	/**
	 * @brief Exception code when an invalid frequency is set.
	 */
	public static $EX_INVALID_FREQUENCY = 501;
	
	/**
	 * @brief Exception code when an invalid repeat id is set.
	 */
	public static $EX_INVALID_REPEAT_ID=502;
	

	/**
	 * @type string 
	 * @brief The name of the table that stores the repeat info.
	 */
	private static $REPEAT_TABLE='dataface__calendar_repeats';
	
	/**
	 * @type modules_calendar
	 * @brief Stores a reference to the calendar module class.
	 */
	private static $module = null;
	
	/**
	 * @type Dataface_Record
	 * @brief Reference to the "first" event record of this repeat.  Also treated as the source.
	 */
	private $sourceRecord;
	
	/**
	 * @type string
	 * @brief Stores the start date of the repeat sequence.  (Not to be confused with the
	 *	the event start date).  Date format is a string in 'Y-m-d H:i:s' format
	 */
	private $startDate;
	
	/**
	 * @type string
	 * @brief Stores the expiry date of this repeat sequence. (Not to be confused with the
	 * event end date).  Date format is a string in 'Y-m-d H:i:s' format.
	 */
	private $expiryDate;
	
	
	/**
	 * @type string
	 * @brief Stores the frequency of the repeat.  Possible values include:
	 *
	 * - Daily
	 * - Weekly
	 * - Biweekly
	 * - Weekdays
	 * - Monthly
	 * - Yearly
	 */
	private $frequency;
	private $frequencyChanged=false;
	
	/**
	 * @type boolean
	 * @brief A flag to indicate whether the repeat info has been saved to the database or not.
	 */
	private $_isSaved;
	
	/**
	 * @type string
	 * @brief Container for the name of the field that stores the start date of the event.
	 *
	 * @see Dataface_Ontology_CalendarEvent
	 */
	private $startAtt;
	
	/**
	 * @type string
	 * @brief Container for the name of the field that stores the end date of the event.
	 *
	 * @see Dataface_Ontology_CalendarEvent
	 */
	private $endAtt;
	
	/**
	 * @type string
	 * @brief Container for the name of the field that stores the repeat id of the event.
	 * @see Dataface_Ontology_CalendarEvent
	 */
	private $repeatAtt;
	
	
	/**
	 * @brief Creates the table used to store repeat details.
	 */
	public static function createRepeatTable(){
		$tname = self::$REPEAT_TABLE;
		$sql = "create table if not exists `$tname` (
			tablename varchar(200) not null,
			repeat_id int(11) not null,
			repeat_type enum('Daily','Weekly','Biweekly','Weekdays','Monthly','Yearly') default 'Weekly',
			repeat_start_date datetime,
			repeat_expire_date datetime,
			primary key (tablename,repeat_id)
			)
			";
			
		return self::query($sql);
		
		
		
		
	}
	
	
	public static function dropRepeatTable(){
		$tname = self::$REPEAT_TABLE;
		$sql = "drop table if exists `$tname`";
		return self::query($sql);
	}
	
	/**
	 * @brief Queries the database.  (Wrapper for mysql_query)
	 * @param string $sql The SQL query to execute.
	 * @throw Exception If there is an error.
	 * @return resource The resulting resource handle.
	 */
	private static function query($sql){
		$res = mysql_query($sql, df_db());
		if ( !$res ){
			throw new Exception(mysql_error(df_db()));
		}
		return $res;
	}
	
	private static function cleanIdent($val){
		return str_replace('`','',$val);
	}
	
	/**
	 * @brief Queries the database, and creates the repeat table if necessary.  This is 
	 * a wrapper for query()
	 *
	 * @param string $sql The SQL query to execute.
	 * @throw Exception If there is an error.
	 * @return resource The resulting resource handle.
	 */
	private static function queryRepeats($sql){
	
		try {
			return self::query($sql);
		} catch (Exception $ex){
			self::createRepeatTable();
			
		}
		return self::query($sql);
	}
	
	/**
	 * @brief Returns a reference to the calendar module class.
	 * @return modules_calendar The calendar module.
	 */
	public static function getModule(){
		if ( !isset(self::$module) ){
			self::$module = Dataface_ModuleTool::getInstance()->loadModule('modules_calendar');
		}
		return self::$module;
	}
	
	/**
	 * @brief Gets a field name from the Dataface_Ontology_CalendarEvent ontology for the current
	 * event table.  This is used to retrieve things like the name of the field where the start
	 * time, end time, and repeat id are stored.
	 *
	 * @param string $name The name of the attribute.  Possible values include:
	 * - start
	 * - end
	 * - repeat
	 *
	 * @return string The name of the repeat field.
	 * @throw Exception If there is no field found an exception will be thrown.
	 *
	 * @see Dataface_Ontology_CalendarEvent For information about the possible properties.
	 *
	 */
	private function getAtt($name){
		$att = self::getModule()
			->loadOntology($this->sourceRecord->table()->tablename)
			->getFieldname($name);
			
		if ( PEAR::isError($att) ){
			throw new Exception("No start attribute found for table.");
		}
		return $att;
	}
	
	/**
	 * @brief Gets the name of the field that stores the start time in the events table.
	 * @return string The name of the field that stores the start time in the events table.
	 * @throw Exception If no start field can be found in the events table.
	 *
	 * The events table is the table where the $sourceRecord record comes from.
	 */
	public function getStartAtt(){
		return $this->getAtt('start');
	}
	
	
	/**
	 * @brief Gets the name of the field that stores the end time in the events table.
	 * @return string The name of the field that stores the end time in the events table.
	 * @throw Exception If no end field can be found in the events table.
	 * 
	 * The events table is the table where the $sourceRecord record comes from.
	 */
	public function getEndAtt(){
		return $this->getAtt('end');
	}
	
	/**
	 * @brief Gets the name of the field that stores the repeat id for the events table.
	 * @return string The name of the field that stores the repeat id in the events table.
	 * @throw Exception If no repeat field can be found in the events table.
	 *
	 * The events table is the table where the $sourceRecord record comes from.
	 */
	public function getRepeatAtt(){
		return $this->getAtt('repeat');
	}
	
	public function getRepeatSeedAtt(){
		return $this->getAtt('repeat_seed');
	}
	
	/**
	 * @brief Gets the start time of the source event record.
	 * @return string The start time of the source event record as a date string 'Y-m-d H:i:s'
	 * @throw Exception If no start field can be found in the events table.
	 */
	public function getSourceStart(){
		return $this->sourceRecord->strval($this->getStartAtt());
	}
	
	/**
	 * @brief Gets the end time of the source event record.
	 * @return string The end time of the source event record as a date string 'Y-m-d H:i:s'
	 * @throw Exception If no end field can be found in the events table.
	 */
	public function getSourceEnd(){
		return $this->sourceRecord->strval($this->getEndAtt());
	}
	
	/**
	 * @brief Gets the repeat ID of the source event record (and all events of this
	 * repeat sequence.
	 * @return int The repeat ID of this repeat sequence.
	 * @throw Exception If no repeat field can be found in the events table.
	 */
	public function getRepeatID(){
		return $this->sourceRecord->val($this->getRepeatAtt());
	}
	
	/**
	 * @brief Creates a new RepeatEvent object to wrap the given source record.
	 * @param Dataface_Record $source The source or original event record that serves
	 * as a seed for this repeat sequence.
	 */
	public function __construct(Dataface_Record $source){
		$this->sourceRecord = $source;
		$this->loadRepeatInfo();
	}
	
	
	
	/**
	 * @brief Loads the repeat info from the database into this record.
	 *
	 * Note that this does not save all of the repeat event records.  It only stores 
	 * the repeat meta-info (e.g. the frequency and expire date).
	 */
	public function loadRepeatInfo(){
		if ( !$this->getRepeatID() ){
			
			return;
		}
		$sql = sprintf("select * from `%s` where `tablename`='%s' and `repeat_id`=%d",
			self::cleanIdent(self::$REPEAT_TABLE),
			addslashes($this->sourceRecord->table()->tablename),
			intval($this->getRepeatID())
		);
		$this->_isSaved = false;
		$res = self::queryRepeats($sql);
		
		while ($row = mysql_fetch_assoc($res) ){
			$this->expiryDate = $row['repeat_expire_date'];
			$this->frequency = $row['repeat_type'];
			$this->startDate = $row['repeat_start_date'];
			$this->_isSaved = true;
		}
		@mysql_free_result($res);
	}
	
	
	public function getDefaultExpiryDifferential(){
		$defaultDiff = '+90 days';
		$settingsField = $this->getModule()->getRepeatSettingsField($this->sourceRecord->table()->tablename);
		if ( $settingsField ){
			$settingsFieldDef =& $this->sourceRecord->table()->getField($settingsField);
			if ( isset($settingsFieldDef['widget']['default_expiry']) ){
				$defaultDiff = $settingsFieldDef['widget']['default_expiry'];
			}
		}
		return $defaultDiff;
	
	}
	
	/**
	 * @brief Saves the repeat info from this object into the database.  Note that this 
	 * does not save all repeat event records.  It only stores the repeat meta-info (e.g. 
	 * the frequency and expire data).
	 *
	 * @throw Exception If the frequency is not set or is invalid.  (CODE=self::$EX_INVALID_FREQUENCY)
	 * @throw Exception If there is no valid repeat id and there is no repeat_seed field from which
	 *		a repeat id can be derived.  (CODE=self::$EX_INVALID_REPEAT_ID)
	 *
	 */
	public function saveRepeatInfo($secure=false){
	
		if ( !$this->frequency ){
			throw new Exception('Frequency is not set for this repeat.', self::$EX_INVALID_FREQUENCY);
		}
	
		if ( !$this->startDate ){
			$this->startDate = $this->getSourceStart();
		}
		
		
		
		if ( !$this->expiryDate ){
			$this->expiryDate = date(
				'Y-m-d H:i:s'
				, strtotime(
					$this->getDefaultExpiryDifferential()
					, strtotime(
						$this->getSourceStart()
					)
				)
			);
			
		}
		
		$repeatID = $this->getRepeatID();
		if ( !$repeatID ){
			// The repeat ID hasn't been set yet
			$seedField = $this->getRepeatSeedAtt();
			$this->sourceRecord->setValue(
				$this->getRepeatAtt(),
				$this->sourceRecord->val($seedField)
			);
			$res = $this->sourceRecord->save(null, $secure);
			if ( PEAR::isError($res) ){
				throw new Exception($res->getMessage(), $res->getCode());
			}
			$repeatID = $this->getRepeatID();
		}
		if ( !isset($repeatID) ){
			throw new Exception("Could not commit repeat sequence because it doesn't have a valid repeat id.", self::$EX_INVALID_REPEAT_ID);
			
		}
		
		
	
		$isql = sprintf("insert into `%s` (`tablename`,`repeat_id`,`repeat_type`, `repeat_start_date`, `repeat_expire_date`)
			values
				('%s',%d,'%s','%s','%s')"
				, self::cleanIdent(self::$REPEAT_TABLE)
				, addslashes($this->sourceRecord->table()->tablename)
				, intval($this->getRepeatID())
				, addslashes($this->frequency)
				, addslashes($this->startDate)
				, addslashes($this->expiryDate)
			);
		$usql = sprintf("update `%s` set
			`repeat_type`='%s',
			`repeat_start_date`='%s',
			`repeat_expire_date`='%s'
			where
				`tablename`='%s' and `repeat_id`=%d"
				, self::cleanIdent(self::$REPEAT_TABLE)
				, addslashes($this->frequency)
				, addslashes($this->startDate)
				, addslashes($this->expiryDate)
				, addslashes($this->sourceRecord->table()->tablename)
				, intval($this->getRepeatID())
			);
			
		if ( $this->isSaved() ){
			self::queryRepeats($usql);
		} else {
			self::queryRepeats($isql);	
		}
				
	}
	
	/**
	 * @brief Gets all of the repeat event records thare are part of this 
	 * repeat sequence and are after the source record.
	 * 
	 * @param int $limit The maximum number of records to return.
	 */
	public function getRepeatRecords($limit=250, $query=array()){
		
		$query[$this->getRepeatAtt()] = '='.$this->getRepeatID();
		$query['-limit'] = $limit;
		
		if ( !isset($query[$this->getStartAtt()] ) ){
			$query[$this->getStartAtt()] = '>'.$this->getSourceStart();
		}
		return df_get_records_array($this->sourceRecord->table()->tablename, $query);
	}
	
	
	/**
	 * @brief Updates all of the repeat records in this sequence with the 
	 * values specified.
	 *
	 * Note that this will only affect repeat records that are later than the source 
	 * record.
	 *
	 * @param array $changes Associative array of values that are to be updated in the
	 * records.
	 * @param int $startDiffSeconds How many seconds the start times should be shifted by each.
	 * @param int $endDiffSeconds How many seconds the end times should be shifted by each.
	 * @param boolean $secure If true then this will respect permissions when trying to
	 * 		save the repeat records.  (i.e. they will each need to grant the 'edit' permission
	 *		in order to save successfully).
	 * @return array(Exception) A list of errors that occurred while updating repeats.  Hopefully
	 *	this is empty.  
	 */
	public function updateRepeats(array $changes, $startDiffSeconds, $endDiffSeconds, $secure=false){
		
		$repeats = $this->getRepeatRecords(999);
		$errors = array();
		$del = $this->sourceRecord->table()->getDelegate();
		
		$beforeUpdateExists = (isset($del) and method_exists($del, 'beforeUpdateRepeat'));
		$afterUpdateExists = (isset($del) and method_exists($del, 'afterUpdateRepeat') );
		$beforeSaveRepeatExists = (isset($del) and method_exists($del, 'beforeSafeRepeat'));
		$afterSaveRepeatExists = (isset($del) and method_exists($del, 'afterSaveRepeat'));
		
		foreach ($repeats as $event){
			try {
				$oldStart = $event->strval($this->getStartAtt());
				$oldEnd = $event->strval($this->getEndAtt());
				
				$newStartTime = strtotime('+'.$startDiffSeconds.' seconds', strtotime($oldStart));
				$newEndTime = strtotime('+'.$endDiffSeconds.' seconds', strtotime($oldEnd));
				
				$changes[$this->getStartAtt()] = date('Y-m-d H:i:s', $newStartTime);
				$changes[$this->getEndAtt()] = date('Y-m-d H:i:s', $newEndTime);
				
				$event->setValues($changes);
				
				if ( $beforeSaveRepeatExists ){
					$del->beforeSaveRepeat($event, $this, $changes);
				}
				
				if ( $beforeUpdateExists ){
					$del->beforeUpdateRepeat($event, $this, $changes);
					
				}
				$res = $event->save(null, $secure);
				if ( PEAR::isError($res) ){
					throw new Exception($res->getMessage(), $res->getCode());
					
				}
				
				
				if ( $afterUpdateExists ){
					$del->afterUpdateRepeat($event, $this, $changes);
				
				}
				
				if ( $afterSaveRepeatExists ){
					$del->afterSaveRepeat($event, $this, $changes);
				}
				
			} catch (Exception $ex){
				$errors[] = $ex;
			}
				
		}
		
		return $errors;
		
		
	}
	
	
	/**
	 * @brief Deletes all repeats in the current sequence that come after the source record.
	 *
	 * @param boolean $secure If true it will cause this to respect the permissions on each
	 * repeat record. (i.e. If a repeat record does not grant the 'delete' permission, then the
	 * delete of that repeat will fail.
	 * @return array(Exception) A list of errors that occurred while attempting to delete
	 *  the repeats.
	 */
	public function clearRepeats($secure=false, $query=array()){
		$del = $this->sourceRecord->table()->getDelegate();
		$repeats = $this->getRepeatRecords(999, $query);
		$errors = array();
		$beforeDeleteExists = (isset($del) and method_exists($del, 'beforeDeleteRepeat'));
		$afterDeleteExists = (isset($del) and method_exists($del, 'afterDeleteRepeat'));
		
		foreach ($repeats as $event){
			try {
				if ( $beforeDeleteExists ){
					
					$del->beforeDeleteRepeat($event, $this);
						
					
				}
					
				$res = $event->delete($secure);
				if ( PEAR::isError($res) ){
					throw new Exception($res->getMessage(), $res->getCode());
				}
					
				if ( $afterDeleteExists ){
					$res = $del->afterDeleteRepeat($event, $this);
					
				}
			} catch (Exception $ex){
				$errors[] = $ex;
			}
			
		}
		
		return $errors;
	}
	
	/**
	 * @brief Creates all of the repeats for the sequence based on the source 
	 * record and repeat settings.
	 *
	 * @param boolean $secure If true this method will respect event permissions.  I.e. if 
	 * the 'new' permission is not granted on the events table, then the inserts will fail.
	 *
	 * @return array(Exception) List of errors that occurred in filling the repeat records.
	 */
	public function fillRepeats($secure=false){
		$del = $this->sourceRecord->table()->getDelegate();
		$errors = array();
		$beforeAddRepeatExists = (isset($del) and method_exists($del, 'beforeAddRepeat'));
		$afterAddRepeatExists = (isset($del) and method_exists($del, 'afterAddRepeat'));
		$beforeSaveRepeatExists = (isset($del) and method_exists($del, 'beforeSafeRepeat'));
		$afterSaveRepeatExists = (isset($del) and method_exists($del, 'afterSaveRepeat'));
		
		
		// Let's get the last existing event in this repeat sequence
		$lastEvent = df_get_record($this->sourceRecord->table()->tablename,
			array(
				$this->getRepeatAtt() => $this->getRepeatID(),
				'-sort' => $this->getStartAtt().' desc'
			)
		);
		
		if ( !$lastEvent ){
			throw new Exception("Could not find last event in repeat sequence.  Please check that the source record of this event sequence has a valid repeat id.");
			
		}
		
		
		/*
		$currTime = strtotime($this->getSourceStart());
		$maxTime = strtotime($this->getExpiryDate());
		$currEnd = strtotime($this->getSourceEnd());
		*/
		$currTime = strtotime(
			date('Y-m-d', 
				strtotime(
					$lastEvent->strval($this->getStartAtt())
				)
			)
			. ' '
			. date('H:i:s', 
				strtotime(
					$this->getSourceStart()
				)
			)
		);
		
		$maxTime = strtotime($this->getExpiryDate());
		$currEnd = strtotime(
			date('Y-m-d', 
				strtotime(
					$lastEvent->strval($this->getEndAtt())
				)
			)
			. ' '
			. date('H:i:s', 
				strtotime(
					$this->getSourceEnd()
				)
			)
		);
		
		$stepsize = null;
		switch ($this->getFrequency()){
		
			case 'Daily': $stepsize = '+1 day';break;
			case 'Weekly': $stepsize = '+1 week';break;
			case 'Biweekly': $stepsize = '+2 week'; break;
			case 'Monthly': $stepsize = '+1 month'; break;
			case 'Yearly': $stepsize = '+1 year'; break;
			case 'Weekdays':
				if ( date('N', $currtime) == 5 ){
					$stepsize = '+3 day';
				} else {
					$stepsize = '+1 day';
				}
				break;
		}
		
		if ( !$stepsize ){
			throw new Exception("Invalid frequency '".$this->getFrequency()."' in repeat.");
		}
		
		$baseVals = $this->sourceRecord->vals();
		$autoField = $this->sourceRecord->table()->getAutoIncrementField();
		if ( !$autoField ){
			throw new Exception("Repeat tables require an auto increment field.");
		}
		unset($baseVals[$autoField]);
		
		$errors = array();
		while ( $currTime < $maxTime ){
			//echo "[Adding $currTime]";
			try {
				$currTime = strtotime($stepsize, $currTime);
				$currEnd = strtotime($stepsize, $currEnd);
				
				$event = new Dataface_Record($this->sourceRecord->table()->tablename, array());
				$event->setValues($baseVals);
				$event->setValue($this->getStartAtt(), date('Y-m-d H:i:s', $currTime));
				$event->setValue($this->getEndAtt(), date('Y-m-d H:i:s', $currEnd));
				
				if ( $beforeSaveRepeatExists ){
					$del->beforeSaveRepeat($event, $this);
				}
				
				if ( $beforeAddRepeatExists ){
					$del->beforeAddRepeat($event, $this);
				}
				
				$res = $event->save(null, $secure);
				if ( PEAR::isError($res) ){
					throw new Exception($res->getMessage(), $res->getCode());
				}
				
				if ( $afterAddRepeatExists ){
					$del->afterAddRepeat($event, $this);
				}
				
				if ( $afterSaveRepeatExists ){
					$del->afterSaveRepeat($event, $this);
				}
				
			} catch (Exception $ex){
				$errors[] = $ex;
			}
			
			
			
			
			
		}
		
		
		return $errors;
	}
	
	
	
	/**
	 * @brief Checks to see if the repeat info has already been saved.
	 * @return boolean True if the repeat info has already been saved to the repeat table.  If false,
	 * then this is likely a new repeat.
	 */
	public function isSaved(){
		if ( !$this->getRepeatID() ) return false;
		if ( !isset($this->_isSaved) ){
			$this->loadRepeatInfo();
		}
		return $this->_isSaved;
	}
	
	
	
	/**
	 * @brief Sets the source event record for this sequence.  This resets all of the 
	 * repeat information (i.e. will need to recalculate) because a different record 
	 * will result in likely a different repeat sequence.
	 *
	 * @return Dataface_Record $record The new source record up on which to base this repeat 
	 * sequence.
	 */
	public function setSourceRecord(Dataface_Record $record){
		$this->expiryDate = null;
		$this->startDate = null;
		$this->frequency = null;
		$this->sourceRecord = $record;
		$this->_isSaved = null;
	}
	
	/**
	 * @brief Gets the source record of this sequence.  The source record is treated as the 
	 * "first" event of a sequence of repeating events.  All other events in this sequence
	 * will be derived from the source record.
	 *
	 * @return Dataface_Record The new source record up which this repeat sequence will be based.
	 */
	public function getSourceRecord(){
		return $this->sourceRecord;
	}
	
	/**
	 * @brief Sets the frequency of this repeat sequence.
	 * @param string $freq The frequency of this repeat sequence.  Possible values include:
	 * 	- Daily
	 *	- Weekly
	 *	- Biweekly
	 * 	- Weekdays
	 *	- Monthly
	 *	- Yearly
	 */
	public function setFrequency($freq){
		if ( $freq != $this->frequency ){
			$this->frequency = $freq;
			$this->frequencyChanged = true;
		}
	}
	
	/**
	 * @brief Gets the frequency of this repeat sequence.
	 *
	 * @return string The frequency of this repeat sequence.  Possible values include:
	 * 	- Daily
	 *	- Weekly
	 *	- Biweekly
	 * 	- Weekdays
	 *	- Monthly
	 *	- Yearly
	 */
	public function getFrequency(){ return $this->frequency;}
	
	/**
	 * @brief Sets the start date of this sequence.
	 * @param string $date A datetime string to serve as the start date of this sequence
	 * (not to be confused with the start time of the event).
	 */
	public function setStartDate($date){ $this->startDate = $date;}
	
	public function getStartDate(){ return $this->startDate; }
	
	/**
	 * @brief Sets the expiry date of this sequence.
	 * @param string $date A datetime string to serve as the expiry date of this sequence.
	 */
	public function setExpiryDate($d){ $this->expiryDate = $d;}
	
	/**
	 * @brief Gets the expiry date of this sequence.
	 * @return string The expiry date of this repeat sequence as a datetime string.  Y-m-d H:i:s
	 */
	public function getExpiryDate(){ return $this->expiryDate;}
	
	
	public function commit(array $changes, $startDiffSeconds, $endDiffSeconds, $secure=false ){
		
		
		
	
		$this->saveRepeatInfo();
		$errors = array();
		if ( $this->frequencyChanged ){
			//If the frequency has changed, we need to clear all of the existing repeat
			// events and build them anew.
			$errors = array_merge($errors, $this->clearRepeats($secure));
			$this->frequencyChanged = false;
		} else {
			// Let's clear all of the repeats that come after the expiry date
			$errors = array_merge($errors, $this->clearRepeats($secure, array(
				$this->getStartAtt()=>'>'.$this->getExpiryDate()
			)));
		}
		
		// Now let's fill all of the repeats
		$errors = array_merge($errors,$this->fillRepeats($secure));
		
		// Now let's update the repeat records.
		// Will be redundant the first time the repeats are filled, but that's ok
		// we may optimize this later.
		if ( $changes or $startDiffSeconds or $endDiffSeconds ){
			$errors = array_merge($errors,
				$this->updateRepeats($changes, $startDiffSeconds, $endDiffSeconds, $secure)
			);
		}
		
		return $errors;
		
		
	}
	
	
}