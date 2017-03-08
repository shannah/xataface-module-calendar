<?php
class actions_calendar_display {
	function handle($params){
		$app = Dataface_Application::getInstance();
		$app->prefs['disable_master_detail'] = 1;
		$query = $app->getQuery();
		
		// Now work on our dependencies
		$mt = Dataface_ModuleTool::getInstance();
		
		$mod = $mt->loadModule('modules_calendar');
		
		// We require the XataJax module
		// The XataJax module activates and embeds the Javascript and CSS tools
		$mt->loadModule('modules_XataJax', 'modules/XataJax/XataJax.php');
		
		$jt = Dataface_JavascriptTool::getInstance();
		$jt->addPath(dirname(__FILE__).'/../js', $mod->getBaseURL().'/js');
		
		$ct = Dataface_CSSTool::getInstance();
		$ct->addPath(dirname(__FILE__).'/../css', $mod->getBaseURL().'/css');
		
		// Add our javascript
		$jt->import('xataface/modules/calendar/calendar_display.js');
		
		$context = array();
		
		df_register_skin('modules_calendar', dirname(__FILE__).'/../templates');
		df_display($context, 'xataface/modules/calendar/calendar_display.html');
	}
}