//require <jquery.packed.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
//require-css <fullcalendar.css>
//require-css <xataface/modules/calendar/calendar_display.css>
//require <RecordDialog/RecordDialog.js>
//require <fullcalendar.js>
//require <xatajax.util.js>

(function(){

	var $ = jQuery;
	
	$(document).ready(function(){
		
		$('div.search_form form').append('<input type="hidden" name="-action" value="calendar_display"/>');
		
		var clientOffset = new Date().getTimezoneOffset()*60;
		var serverOffset = clientOffset;
	
		$('#modules-calendar-container').each(function(){
			var tableName = $(this).attr('data-xf-tablename');
			var self = this;
			
			var defaultView = null;
			
			var hashData = getHashData();
			if ( hashData ){
				defaultView = hashData.view;
				
			}
			//alert(defaultView);
			//alert(defaultView);
			//alert(defaultView);
			$(this).fullCalendar({
			    lang : window.XF_LANG || 'en',
				eventResize:eventResized,
				eventDrop: eventDrop,
				events: loadEvents,
				eventClick: function(event){
					if ( event.editable ){
						editEvent(event);
					} else {
						viewEventDetails(event);
					}
				},
				dayClick: handleDayClick,
				header: {
					left: 'prev,next today',
					center: 'title',
					right: 'month,agendaWeek,agendaDay'
				},
				viewDisplay: function(view){
					setHash(view);
				}
			});
			
			if ( defaultView ){
				//alert(defaultView);
				setTimeout((function(){
					$(self).fullCalendar('changeView', defaultView)
				}), 100);
			}
			
			
			
			
			if ( hashData ){
				
				$(this).fullCalendar('gotoDate', hashData.start);
			}
			
			
			function loadEvents(start, end, callback){
				var q = {
					'-action': 'calendar_json_feed',
					'-calendar-start': start.toUTCString(),
					'-calendar-end': end.toUTCString(),
					'-table': tableName,
					'-limit': 500,
					'-skip': 0
				};
				
				q = $.extend(XataJax.util.getRequestParams(), q);
				
				
				
				
				//alert(q['-start']);
				
				
				
				$.get(DATAFACE_SITE_HREF, q, function(result){
					try {
						if ( typeof(result) == 'string' ){
							eval('result='+result+';');
						}
						
						if ( result.code == 200 ){
							$.each(result.events, function(key,item){
								serverOffset = item.serverOffset;
								//alert(item.start);
								//item.start = new Date(1000*(item.start-item.serverOffset+clientOffset));
								item.start = new Date(1000*(item.start));
								//alert(item.start);
								//alert(item.end);
								//item.end = new Date(1000*(item.end-item.serverOffset+clientOffset));
								item.end = new Date(1000*(item.end));
								//alert(item.end);
							});
							callback(result.events);
							return;
						} else {
							if ( result.message ){
								throw result.message;
							} else {
								throw 'Failed to load events due to a server error.  Please check your server error log for details.';
							}
						}
						
					} catch (e){
						alert(e);
					}
				});
				
			
			}
			
			
			
			function eventDrop( event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view ){
				if ( !event.xfid ){
					revertFunc();
					return;
				}
				
				var q = {
					'-action': 'calendar_event_moved',
					'-calendar-day-delta': dayDelta,
					'-calendar-minute-delta': minuteDelta,
					'-calendar-all-day': allDay,
					'-calendar-event-id': event.xfid
				};
				
				$.post(DATAFACE_SITE_HREF, q, function(res){
					try {
						if ( res.code == 200 ){
							// success - we just return
							
						} else {
							throw "Failed to move event.  See error log";
						}
					
					} catch (e){
						revertFunc();
					}
				});
				
			}
			
			
			
			function eventResized( event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view){
				if ( !event.xfid ){
					revertFunc();
					return;
				}
				
				var q = {
					'-action': 'calendar_event_resized',
					'-calendar-day-delta': dayDelta,
					'-calendar-minute-delta': minuteDelta,
					'-calendar-event-id': event.xfid
				};
				
				$.post(DATAFACE_SITE_HREF, q, function(res){
					try {
						if ( res.code == 200 ){
							// success - we just return
							
						} else {
							throw "Failed to move event.  See error log";
						}
					
					} catch (e){
						revertFunc();
					}
				});	
			}
			
			
			function viewEventDetails(event){
			
				var q = {
					'-action': 'ajax_get_event_details',
					'--record_id': event.xfid
				
				};
				
				var url = DATAFACE_SITE_HREF;
				
				$.get(url, q, function(response){
					try {
						if ( typeof(response) == 'string' ){
							eval('response='+response+';');
						}
						
						var div = $(document.createElement('div'))
							.html(response.details);
						$('body').append(div);
						
							div.dialog({
								title: 'Event Details',
								width: Math.floor($(window).width()*.75),
								height: Math.floor($(window).height()*.75)
							});
					
					} catch (e){}
				});
			}
			
			
			/**
			 * Edits an event
			 *
			 * @param {CalendarEvent} event Event object from the calendar that was clicked.
			 */
			function editEvent(event){
				
				var recordID = event.xfid;
				
				
				
				var dlg = new xataface.RecordDialog({
					recordid: recordID,
					table: tableName,
					callback: function(data){
					
						$(self).fullCalendar('refetchEvents');
						
					}
				
				});
				
				dlg.display();
					
				
			}
			
			function addEvent(date, allDay){
				var dlg = new xataface.RecordDialog({
					table: tableName,
					callback: function(data){
					
						$(self).fullCalendar('refetchEvents');
						
					},
					params: {
						//'--calendar-new-default-start-date': Math.floor(((date.getTime()/1000)+serverOffset-clientOffset)),
						'--calendar-new-default-start-date': Math.floor(((date.getTime()/1000))),
						'--calendar-new-default-all-day': allDay?1:0
					
					}
				
				});
				
				dlg.display();
				
			}
			
			function handleDayClick(date, allDay, jsEvent, view ){
				
				if ( view.name == 'month' ){
					$(self)
						.fullCalendar('changeView', 'agendaWeek')
						.fullCalendar('gotoDate', date);
					
				
				} else {
					addEvent(date, allDay);
				}
			}
			
			function getHashData(){
				var hash = window.location.hash;
				if ( !hash || hash.length < 2 ) return null;
				hash = hash.substr(1);
				
				var parts = hash.split('_');
				//alert("Parts 1 "+parts[1]+ " int : "+parseInt(parts[1]) + " Date: "+(new Date(parts[1]))+"  Date int "+(new Date(parseInt(parts[1]))));
				
				return {
					view: parts[0],
					start: new Date(parseInt(parts[1])),
					end: new Date(parseInt(parts[2]))
					
				};
			}
			
			var lastView = null;
			function setHash(view){
				
				
				
				var oldData = getHashData();
				if ( oldData ){
					
					if ( oldData.view == view.name && oldData.start == view.start && oldData.end == view.end ){
						return;
					}
				} else {
					
				}
				
				
				
				location.hash = view.name+'_'+view.start.getTime()+'_'+view.end.getTime();
			
			}
			
		});
		
	});
	
	
})();