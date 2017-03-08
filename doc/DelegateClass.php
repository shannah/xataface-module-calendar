<?php

/**
 * @brief This interface defines the delegate class methods that are supported by the calendar
 * module.  This interface is not *real* it is only used for documentation purposes.
 *
 * @see <a href="http://xataface.com/dox/core/latest/interface_delegate_class.html">Xataface Table Delegate Class</a> for the full interface of
 *  available delegate classes in Xataface.
 * @see <a href="http://xataface.com/documentation/tutorial/getting_started/delegate_classes">Delegate Classes section</a> of the Xataface Getting Started 
 * tutorial if you are not familiar with delegate classes.
 * @see <a href="http://xataface.com/wiki/Delegate_class_methods">The Delegate Classes</a> wiki page
 * for more information about delegate classes.
 */
interface DelegateClass {
	
	/**
	 * @brief Returns the foreground color for a record when it is rendered in the 
	 * calendar.
	 * 
	 * @param Dataface_Record The record that is to be rendered.
	 * @returns string The foreground color as a string.  This should be in a form that
	 *  can be accepted by CSS (e.g. as #ededed, or an english name e.g. "blue", or 
	 *  an RGB value e.g. rgb(255,255,255)
	 *
	 * @par Example
	 * @code
	 * function getColor($record){
	 *     if ( $record->val('status') == 'Approved' ) return 'black';
	 *     else return '#ccc';
	 * }
	 * @endcode
	 */
	public function getColor(Dataface_Record $record);
	
	/**
	 * @brief Returns the background color for a record when it is rendered in the 
	 * calendar.
	 *
	 * @param Dataface_Record The record that is to be rendered.
	 * @returns string The background color as a string.  This should be in a form
	 * that can be accepted by CSS (e.g. as hex, an english name, or as an RGB value).
	 *
	 * @par Example
	 * @code
	 * function getBgColor($record){
	 *     if ( $record->val('country') == 'Canada') return 'red';
	 *     else return 'rgb(0,0,255)';
	 * }
	 * @endcode
	 */
	public function getBgColor(Dataface_Record $record);
	
	/**
	 * @brief Modifies the event data for a record just before it is sent to the calendar
	 * for rendering.  The event data will be converted to JSON and returned to the 
	 * calendar for rendering.  This data follows the structure of, and will ultimately
	 * be wrapped by, the <a href="http://arshaw.com/fullcalendar/docs/event_data/Event_Object/">full calendar event object</a>.
	 * @returns void There is no output for this method.  The @p $eventData array should
	 * be passed by reference so that data can be modified in place.
	 *
	 * @par Example
	 * Adding a CSS class to each event so that events can be referenced by tool id in
	 * javascript: @code
	 * function calendar__decorateEvent(Dataface_Record $record, &$event){
	 *	   if ( !$event['className'] ) $event['className'] = '';
	 *	   $event['className'] .= 'event-for-tool-'.$record->val('tool_id');
	 * }
	 * @endcode
	 *
	 *
	 */
	public function calendar__decorateEvent(Dataface_Record $record, array &$eventData);

}