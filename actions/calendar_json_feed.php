<?php
class actions_calendar_json_feed {
	


	function handle($params){
		session_write_close();
		header('Connection: close');
		
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		
		import('Dataface/Ontology.php');
		
		Dataface_Ontology::registerType('CalendarEvent', dirname(__FILE__).'/../ontologies/CalendarEvent.php', 'Dataface_Ontology_CalendarEvent');
		$ontology =& Dataface_Ontology::newOntology('CalendarEvent', $query['-table']);
			
		$dateAtt = $ontology->getFieldname('start');
		if ( PEAR::isError($dateAtt) ) die($dateAtt->getMessage());
		
		$repeatAtt = $ontology->getFieldname('repeat');
		if ( PEAR::isError($repeatAtt) ){
			$repeatAtt = null;
		}
		
		$q = $query;
		
		//print_r($query);
		
		$q[$dateAtt] = date('Y-m-d H:i:s', strtotime($query['-calendar-start'])).'..'.date('Y-m-d H:i:s', strtotime($query['-calendar-end']));
		
		$q['-skip'] =0;
		$q['-limit'] = 500;
		//print_r($q);exit;
		
		
		
		$records =& df_get_records_array($query['-table'], $q);
		$out = array();
		if ( $records ){
			$del = $records[0]->table()->getDelegate();
			$getColorExists = (isset($del) and method_exists($del, 'getColor'));
			$getBgColorExists = (isset($del) and method_exists($del, 'getBgColor'));
			
		}
		foreach ($records as $r){
			if ( !$r->checkPermission('view') ) continue;
			$i = $ontology->newIndividual($r);
			$starttime = $i->strval('start');
			$endtime = $i->strval('end');
			$allDay = intval($i->val('allday'));
			
			//echo "[ALLDAY: ".$allDay.']';
			$startdate = strtotime($starttime);
				
			$enddate = strtotime($endtime);
			$evt = array(
				'start' => $startdate,
				'end'=>$enddate,
				'title'=>$r->getTitle(),
				'description'=>$r->getDescription(),
				'xfid'=>$r->getId(),
				'editable'=> $r->checkPermission('edit'),
				'repeat'=> ($repeatAtt ? $i->val('repeat') : null),
				'allDay'=> $allDay,
				'serverOffset'=>intval(date('Z'))
			);
			
			
			if ( $getColorExists ){
				$color = $del->getColor($r);
				if ( $color ){
					$evt['textColor'] = $color;
				}
				
			}
			if ( $getBgColorExists ){
				$color = $del->getBgColor($r);
				if ( $color ) $evt['color'] = $color;
				
			}	
			
			
			
			
			$del = $r->table()->getDelegate();
			if ( isset($del) and method_exists($del, 'calendar__decorateEvent') ){
				$del->calendar__decorateEvent($r, $evt);
			}
			$out[] = $evt;
		}
		
		header('Content-type: text/json; charset="'.$app->_conf['oe'].'"');
		echo json_encode(array(
			'code'=>200,
			'message'=>'success',
			'events'=>$out
		));
		exit;
	
	}
}