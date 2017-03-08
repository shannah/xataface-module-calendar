CREATE TABLE IF NOT EXISTS `xftest_calendar_events` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `booking_title` varchar(100) NOT NULL,
  `booked_by` int(11) DEFAULT NULL,
  `all_day` tinyint(1) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `repeat_id` int(11) DEFAULT NULL,
  `description` text,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `date_created` datetime DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`booking_id`),
  KEY `room_id` (`room_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=20 ;
