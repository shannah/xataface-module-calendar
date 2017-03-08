<?php
import('PHPUnit.php');

class modules_calendar_RepeatEventTest extends PHPUnit_TestCase {

	private static $TABLENAME = 'xftest_calendar_events';
	private $mod = null;
	
	function modules_calendar_RepeatEventTest( $name = 'modules_calendar_RepeatEventTest'){
		$this->PHPUnit_TestCase($name);
		
	}

	function setUp(){
		
		$this->mod = Dataface_ModuleTool::getInstance()->loadModule('modules_calendar');
		self::q('drop table if exists `'.self::$TABLENAME.'` ');
		$this->mod->dropRepeatTable();
		
		Dataface_Table::setBasePath(self::$TABLENAME, dirname(__FILE__));
		self::q(file_get_contents(Dataface_Table::getBasePath(self::$TABLENAME).'/tables/'.self::$TABLENAME.'/create.sql'));
		
				
	
	}
	
	
	
	
	
	function tearDown(){
		
		

	}
	
	
	function testRepeats(){
		
		$record = new Dataface_Record(self::$TABLENAME, array());
		$record->setValues(array(
			'start_time'=> '2011-01-05 13:30',
			'end_time'=> '2011-01-05 1400',
			'room_id'=>1
			
			
		));
		$res = $record->save();
		if ( PEAR::isError($res) ){
			throw new Exception($res->getMessage(), $res->getCode());
		}
		$repeat = $this->mod->newRepeat($record);
		$this->assertEquals('start_time', $repeat->getStartAtt(), 'Start att');
		$this->assertEquals('end_time', $repeat->getEndAtt(), 'End att');
		$this->assertEquals('repeat_id', $repeat->getRepeatAtt(), 'Repeat att');
		$this->assertEquals('booking_id', $repeat->getRepeatSeedAtt(), 'Repeat seed att');
		$this->assertEquals('2011-01-05 13:30:00', $repeat->getSourceStart(), 'Start time');
		$this->assertEquals('2011-01-05 14:00:00', $repeat->getSourceEnd(), 'End time');
		$this->assertEquals(null,$repeat->getFrequency(),  'Frequency initialized');
		$this->assertEquals( null,$repeat->getExpiryDate(), 'Expiry date initialized');
		$this->assertEquals(null, $repeat->getStartDate(), 'Start date initialized');
		$this->assertEquals(null, $repeat->getRepeatID(), 'Initialized repeat id');
		$this->assertEquals(false, $repeat->isSaved(), 'Initialized isSaved()');
		$this->assertEquals($record, $repeat->getSourceRecord(), 'Source record');
		
		
		$ex = null;
		try {
			$repeat->saveRepeatInfo();
		} catch (Exception $e){
			$ex = $e;
		}
		$this->assertEquals('Exception', get_class($ex), 'Check for frequency exception on save');
		if ( $ex ){
			$this->assertEquals(modules_calendar_RepeatEvent::$EX_INVALID_FREQUENCY, $ex->getCode(), 'Attempt to save before setting frequency');
		}
		
		$repeat->setFrequency('Weekly');
		$repeat->saveRepeatInfo();
		$this->assertEquals('Weekly', $repeat->getFrequency(), 'Checking frequency after save');
		
		
		$record2 = df_get_record_by_id($record->getId());
		$this->assertEquals($record->val('booking_id'), $record2->val('booking_id'), '2nd record booking id');
		$repeat2 = $this->mod->newRepeat($record2);
		$this->assertEquals($repeat->getRepeatID(), $repeat2->getRepeatID(), 'Repeat id of reloaded repeat');
		$this->assertEquals('Weekly', $repeat2->getFrequency(), 'Checking frequency of reloaded repeat');
		$this->assertEquals('2011-01-05 13:30:00', $repeat2->getStartDate(), 'Checking start date of reloaded repeat');
		
		$expectedExpiry = date(
			'Y-m-d H:i:s',
			strtotime(
				$repeat->getDefaultExpiryDifferential(),
				strtotime('2011-01-05 13:30:00')
			)
		);
		$this->assertEquals($expectedExpiry, $repeat2->getExpiryDate(), 'Checking expiry date of reloaded repeat');
		
		
		// Now let's get to the nitty gritty.
		$repeatRecords = $repeat->getRepeatRecords();
		
		$this->assertEquals(0, count($repeatRecords), 'Number or repeat records before repeat added');
		$repeatRecords2 = $repeat2->getRepeatRecords();
		$this->assertEquals(0, count($repeatRecords2), 'Number or repeat records in duplicate before repeat added');
		
		
		$errs = $repeat->fillRepeats();
		$this->assertEquals(0, count($errs), 'Should be 0 errors in fillRepeats()');
		$repeatRecords = $repeat->getRepeatRecords();
		$this->assertEquals(13, count($repeatRecords), 'Number of repeat records before repeat added');
		//echo df_utc_offset();
		foreach ($repeatRecords as $rr){
			//echo "here";
			$this->assertEquals(
				date('H:i:s', strtotime($repeat->getSourceStart())),
				date('H:i:s', strtotime($rr->strval($repeat->getStartAtt()))),
				'Start time should be the same for repeat: '.$rr->getId()
			);
		}
		
		
		
		$errors = $repeat->clearRepeats();
		$this->assertEquals(0, count($errors), 'Should be no errors after clearing repeats');
		$repeatRecords = $repeat->getRepeatRecords();
		$this->assertEquals(0, count($repeatRecords), 'Should be no repeats left after clearing repeats');
		
		$errs = $repeat->fillRepeats();
		$this->assertEquals(0, count($errs), 'Should be 0 errors in fillRepeats() after clearing');
		$repeatRecords = $repeat->getRepeatRecords();
		$this->assertEquals(13, count($repeatRecords), 'Should be 13 repeats after repeat re-filled after clearing them');
		//echo df_utc_offset();
		foreach ($repeatRecords as $rr){
			//echo "here";
			$this->assertEquals(
				date('H:i:s', strtotime($repeat->getSourceStart())),
				date('H:i:s', strtotime($rr->strval($repeat->getStartAtt()))),
				'Start time should be the same for repeat: '.$rr->getId()
			);
		}
		
		
		$errors = $repeat->clearRepeats();
		$this->assertEquals(0, count($errors), 'Should be no errors after clearing repeats');
		$repeatRecords = $repeat->getRepeatRecords();
		$this->assertEquals(0, count($repeatRecords), 'Should be no repeats left after clearing repeats');
		
		
		$repeat->setFrequency('Daily');
		$errs = $repeat->fillRepeats();
		$this->assertEquals(0, count($errs), 'Should be 0 errors in fillRepeats() after clearing');
		$repeatRecords = $repeat->getRepeatRecords();
		$this->assertEquals(90, count($repeatRecords), 'Should be 90 repeats after changing frequency to daily');
		//echo df_utc_offset();
		foreach ($repeatRecords as $rr){
			//echo "here";
			$this->assertEquals(
				date('H:i:s', strtotime($repeat->getSourceStart())),
				date('H:i:s', strtotime($rr->strval($repeat->getStartAtt()))),
				'Start time should be the same for repeat: '.$rr->getId()
			);
		}
		
		
		
		
		$record3 = new Dataface_Record(self::$TABLENAME, array());
		$record3->setValues(array(
			'start_time'=> '2011-01-05 18:30',
			'end_time'=> '2011-01-05 19:00',
			'room_id'=>1
			
			
		));
		$res = $record3->save();
		if ( PEAR::isError($res) ){
			throw new Exception($res->getMessage(), $res->getCode());
		}
		$repeat->setSourceRecord($record3);
		$this->assertEquals(false, $repeat->isSaved(), 'isSaved() should be false after changing source record to one with no repeat id.');
		$this->assertEquals(null, $repeat->getFrequency(), 'frequency should be null after changing source record to one with no repeat yet.');
		$this->assertEquals(null, $repeat->getStartDate(), 'start date should be null after changing the source record.');
		$this->assertEquals(null, $repeat->getExpiryDate(), 'expiry date should be null after changing the source record.');
		
		$repeat->setFrequency('Daily');
		$record3->setValue($repeat->getStartAtt(), date('Y-m-d H:i:s'));
		$repeat->setExpiryDate(date('Y-m-d H:i:s', strtotime('+5 days')));
		
		$errors = $repeat->commit(array(), 0, 0);
		$this->assertEquals(0, count($errors), 'There should be no errors on commit');
		
		$repeatID = $repeat->getRepeatID();
		$this->assertTrue(@$repeatID, 'The repeat id should be set after commit');
		$this->assertTrue(@$record3->val($repeat->getRepeatAtt()), 'The repeat id should be set in the record after commit also.');
		$this->assertEquals($repeatID, $record3->val($repeat->getRepeatAtt()), 'The getRepeatID() should be the same as the repeatID returned from the record.');
		
		
		$repeatRecords = $repeat->getRepeatRecords();
		$this->assertEquals(5, count($repeatRecords), 'Should be 5 repeats after committing the repeat');
		//echo df_utc_offset();
		foreach ($repeatRecords as $rr){
			//echo "here";
			$this->assertEquals(
				date('H:i:s', strtotime($repeat->getSourceStart())),
				date('H:i:s', strtotime($rr->strval($repeat->getStartAtt()))),
				'Start time should be the same for repeat: '.$rr->getId()
			);
		}
		
		
		
		
		
		
		
		
		
		

		
		
		
		
			
	}
	static function q($sql){
		$res = mysql_query($sql, df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		return $res;
	}
	
		


}


// Add this test to the suite of tests to be run by the testrunner
Dataface_ModuleTool::getInstance()->loadModule('modules_testrunner')
		->addTest('modules_calendar_RepeatEventTest');
