<?php
class actions_calendar_event_resized {
	function handle($params){
		
		$app = Dataface_Application::getInstance();
		
		try {
			if ( !@$_POST['-calendar-event-id'] ){
				throw new Exception("No calendar event id provided", 500);
			}
			
			$record = df_get_record_by_id($_POST['-calendar-event-id']);
			if ( !$record ){
				throw new Exception("The specified event could not be found", 404);
				
			}
			
			if ( !$record->checkPermission('edit') ){
				throw new Exception("Failed to move the event because you don't have sufficient permissions.", 400);
				
			}
			
			import('Dataface/Ontology.php');
		
			Dataface_Ontology::registerType('CalendarEvent', dirname(__FILE__).'/../ontologies/CalendarEvent.php', 'Dataface_Ontology_CalendarEvent');
			$ontology =& Dataface_Ontology::newOntology('CalendarEvent', $record->_table->tablename);
			
			
			
			$endAtt = $ontology->getFieldname('end');
			if ( PEAR::isError($endAtt) ) throw new Exception('Failed to find start time field in this event.', 500);
			
			if ( !$record->checkPermission('edit', array('field'=>$endAtt)) ){
				throw new Exception('Failed to move record because you don\'t have permission to change the end time field.', 400);
				
			}
			
				
				
			if ( @$_POST['-calendar-day-delta'] ){
				$dateDelta = intval($_POST['-calendar-day-delta']);
				$deltaSign = '+';
				if ( $dateDelta<0 ) $deltaSign='-';
				$dateDelta = abs($dateDelta);
				
				
				
				$currEnd = $record->strval($endAtt);
				$newEnd = date('Y-m-d H:i:s', strtotime($deltaSign.$dateDelta.' day', strtotime($currEnd)));
				//echo $deltaSign.$dateDelta.' ('.$newDate.')';exit;
				//$record->setValue($dateAtt, $newDate);
				$record->setValues(array(
					$endAtt=>$newEnd
				));
				
				
			}
			
			
			if ( @$_POST['-calendar-minute-delta'] ){
				$minuteDelta = intval($_POST['-calendar-minute-delta']);
				$deltaSign = '+';
				if ( $minuteDelta<0 ) $deltaSign = '-';
				$minuteDelta = $deltaSign.(abs($minuteDelta));
				
				
				
				
				$currEnd = $record->strval($endAtt);
				
				$newEnd = date('Y-m-d H:i:s', strtotime($minuteDelta.' minutes', strtotime($currEnd)));
				
				$record->setValues(array(
					$endAtt=>$newEnd
				));
				
			}
			
			//echo $endAtt;exit;
			
			$res = $record->save(null, true);
			if ( PEAR::isError($res) ){
				error_log($res->toString());
				throw new Exception("Failed to update event due to an error while saving.  Please see server error log for details.", 500);
			}
			
			
			// At this point, presumeably the event has been updated
			$this->out(array(
				'code'=>200,
				'message'=>'Event updated successfully'
			));
			exit;
			
			
			
		
		} catch (Exception $ex){
		
			$this->out(array(
				'message'=>$ex->getMessage(),
				'code'=>$ex->getCode()
			));
			exit;
		}
		
		
	}
	
	
	function out($params){
		header('Content-type: text/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
		echo json_encode($params);
	}
}