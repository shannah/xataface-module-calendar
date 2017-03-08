<?php
/**

@page installation Installation

Return to @ref toc

@section installation Installation

@par Step 1 Copy into modules directory
After downloading the @p calendar directory, copy it into the @p modules directory of your application.  It should be located at @code
%APPLICATION_PATH%/modules/calendar
@endcode

@par Step 2 Enable the Module In the conf.ini file
Once the @p calendar module is in place, you just need to tell your application to use it by adding the following to the @p [_modules] section of your conf.ini file: @code
[_modules]
    modules_calendar=modules/calendar/calendar.php
@endcode


@par Step 3 Configure Tables
The Calendar widget relies on two main <a href="http://xataface.com/wiki/fields.ini_file">fields.ini file</a> configuration options to interpret records as events that can be rendered in the calendar:
-# event.start
-# event.end

A field that represents the start date/time of an event should include @code
event.start=1
@endcode
In the fields.ini file field definition.
A field that represents the end date/time of an event should include @code
event.end=1
@endcode
in the fields.ini file field definition.

@par Step 4 Try it Out
If you load your application in your web browser you should now notice a "calendar" tab in the set of table tabs (i.e. along side "details", "list", and "find").  
<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-29_at_3.16.40_PM.png?max_width=640"/>

If you're using the new Xataface 2.0 look and feel, you'll instead see a "View" menu in the top left with "Calendar" now as one of the options:

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-29_at_3.16.13_PM.png?max_width=640"/>


- Return to @ref toc

*/
?>