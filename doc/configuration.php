<?php
/**

@page configuration Configuration Options

Return to @ref toc

The calendar module can be configured and customized in many ways including:

-# <a href="http://weblite.ca/wiki/fields.ini_file">fields.ini file</a> directives.
-# Overriding and Customizing actions in the <a href="http://weblite.ca/wiki/actions.ini_file">actions.ini file.</a>
-# Implementing Specified Delegate Class Methods
-# Overriding templates

@section fieldsini_options fields.ini File Directives

The first method you should generally attempt to configure the calendar is via the fields.ini file directives.  Any table that uses the calendar module should at least make use of the @p event.start and @p event.end directives as these mark the fields that store the start and end times of the events.  Without both of these fields specified, the calendar will likely show up blank.

fields.ini directives include:

<table>
	<tr>
		<th>Directive</th><th>Description</th><th>Default</th><th>Required</th>
	</tr>
	<tr>
		<td>@p event.start</td>
		<td>If present, marks a field as holding the start datetime for the event.  This is a boolean value so setting it to 1 is the only option.</td>
		<td>0</td>
		<td>Yes</td>
	</tr>
	<tr>
		<td>@p event.end</td>
		<td>If present, marks a field as hold the end datetime for the event.  THis is a boolean value, so setting it to 1 is the only option.</td>
		<td>0</td>
		<td>Yes</td>
	</tr>
	<tr>
		<td>@p event.location</td>
		<td>If present, marks a field as holding the location of the event.</td>
		<td>0</td>
		<td>No</td>
	</tr>
	<tr>
		<td>@p event.category</td>
		<td>If present, marks a field as holding the category of the event.</td>
		<td>0</td>
		<td>No</td>
	</tr>
	<tr>
		<td>@p event.allday</td>
		<td>Indicates that this field is used to indicate whether the event is an all day event.  Typically this should be used with a boolean field like a checkbox because the calendar module will take any value in this field (other than 0) to be affirmation that the event in question is set to run all day.</td>
		<td>0</td>
		<td>No</td>
	</tr>
	<tr>
		<td>@p event.repeat</td>
		<td>Indicates that this field is used to mark whether the event is a repeating event or not.  Generally this option is used with a boolean field like a checkbox.</td>
		<td>0</td>
		<td>No</td>
	</tr>
	<tr>
		<td>@p event.repeat_seed</td>
		<td>Indicates that the field is meant to store the repeat seed of the repeating event.  All events that are part of the same repeat will share the same seed.  The seed field will contain the id of the first event in the repeat as default.  Although this is an implementation detail and cannot be counted upon to remain the same.</td>
		<td>0</td>
		<td>No</td>
	</tr>
</table>


@par Example fields.ini file:
@code
[username]
	widget:type=lookup
	widget:table=users
	
[start_time]
	widget:type=datetimepicker
	widget:interval=30
	event.start=1
	
[end_time]
	;widget:type=durationselector
	;widget:start=start_time
	;widget:interval=30
	widget:type=datetimepicker
	event.end=1

[tool_id]	
	widget:type=depselect
	widget:table=tools
	widget:filters:bookable=1
	event.location=1
	checkbox_filter=1
	vocabulary=tools
	
[project_id]
	widget:type=select
	vocabulary=my_projects
	
[date_created]
	timestamp=insert
	widget:type=hidden
	
[last_modified]
	timestamp=update
	widget:type=hidden
@endcode

The above fields.ini file makes use of three directives:
-# @p event.start (for the @p start_time field)
-# @p event.end (for the @p end_time field)
-# @p event.location (for the tool_id) field.


@section delegate_class_methods Delegate Class Methods

Some customizations are more dynamic in nature and cannot be easily expressed in a static config file like the fields.ini file.  The calendar module can also be customized by the table delegate class.  The following methods are supported:

<table>
	<tr>
		<th>Method Name</th><th>Description</th>
	</tr>
	<tr>
		<td>@p getColor()</td>
		<td>Returns the foreground color for a particular event.  See DelegateClass::getColor()</td>
	</tr>
	<tr>
		<td>@p getBgColor()</td>
		<td>Returns the background color for a particular event. See DelegateClass::getBgColor()</td>
	</tr>
	<tr>
		<td>@p calendar__decorateEvent</td>
		<td>Decorates/modifieds the event data before it is published to the calendar.  See DelegateClass::calendar__decorateEvent()</td>
	</tr>
</table>

@see DelegateClass

*/
?>