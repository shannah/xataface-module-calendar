<?php
/**
 * @brief A dummy interface to define the methods that can be implemented in the table
 * delegate class to hook into the repeat methods.
 *
 */
interface modules_calendar_RepeatDelegate {
	
	
	/**
	 * @brief Hook executed before a repeat record is saved (i.e. either updated or added).
	 * @param Dataface_Record $event The event record that is to be inserted as a repeat.
	 * @param modules_calendar_RepeatEvent $repeat The repeat record containing all of the details
	 *		about the repeat including a reference to the source record.
	 * @param array $changes Optional associative array of changes that have been made and
	 *		are to be updated.
	 * @return void
	 * @throw Exception Throwing an exception from this method signals the save to fail.
	 *
	 * @attention The beforeSave() delegate method is still called as usual
	 *	when repeat records are inserted.  This is an additional hook that is called before
	 *  the standard hooks.
	 */
	public function beforeSaveRepeat(
		Dataface_Record $event, 
		modules_calendar_RepeatEvent $repeat, 
		array $changes=array());
		
	
	/**
	 * @brief Hook executed after a repeat record is saved (u.e. either updated or added).
	 * @param Dataface_Record $event The event record that has been saved.  This is the repeat.
	 * @param modules_calendar_RepeatEvent $repeat The repeat record containing all of the details
	 *		about the repeat including a reference to the source record.
	 * @param array $changes Associative array of values that were changed in the repeat.
	 *
	 * @return void
	 *
	 * @attention The afterSave() delegate method is still called as usual
	 *	when repeat records are inserted.  This is an additional hook that is called after
	 *  the standard hooks.
	 */
	public function afterSaveRepeat(Dataface_Record $event, modules_calendar_RepeatEvent $repeat, array $changes=array());
	
	
	/**
	 * @brief Hook executed before a repeat record is updated.
	 * @param Dataface_Record $event The event record that has been saved.  This is the repeat.
	 * @param modules_calendar_RepeatEvent $repeat The repeat record containing all of the details
	 *		about the repeat including a reference to the source record.
	 * @param array $changes Associative array of values that were changed in the repeat.
	 * @return void
	 * @throw Exception Throwing an exception from this method signals the save to fail.
	 *
	 * @attention The beforeSave() and beforeUpdate() delegate methods are still called as usual
	 *	when repeat records are inserted.  This is an additional hook that is called before
	 *  the standard hooks.
	 */
	public function beforeUpdateRepeat(Dataface_Record $event, modules_calendar_RepeatEvent $repeat, array $changes=array());
	
	
	/**
	 * @brief Hook executed after a repeat record is updated.
	 * @param Dataface_Record $event The event record that has been saved.  This is the repeat.
	 * @param modules_calendar_RepeatEvent $repeat The repeat record containing all of the details
	 *		about the repeat including a reference to the source record.
	 * @param array $changes Associative array of values that were changed in the repeat.
	 * @return void
	 *
	 * @attention The afterSave() and afterUpdate() delegate methods are still called as usual
	 *	when repeat records are inserted.  This is an additional hook that is called after
	 *  the standard hooks.
	 */
	public function afterUpdateRepeat(Dataface_Record $event, modules_calendar_RepeatEvent $repeat, array $changes=array());
	
	/**
	 * @brief Hook executed before a repeat record is added.
	 * @param Dataface_Record $event The event record that has been saved.  This is the repeat.
	 * @param modules_calendar_RepeatEvent $repeat The repeat record containing all of the details
	 *		about the repeat including a reference to the source record.
	 * @return void
	 * @throw Exception Throwing an exception from this method signals the save to fail.
	 *
	 * @attention The beforeSave() and beforeInsert() delegate methods are still called as usual
	 *	when repeat records are inserted.  This is an additional hook that is called before
	 *  the standard hooks.
	 */
	public function beforeAddRepeat(Dataface_Record $event, modules_calendar_RepeatEvent $repeat);
	
	/**
	 * @brief Hook executed after a repeat record is added.
	 * @param Dataface_Record $event The event record that has been saved.  This is the repeat.
	 * @param modules_calendar_RepeatEvent $repeat The repeat record containing all of the details
	 *		about the repeat including a reference to the source record.
	 * @return void
	 *
	 * @attention The afterSave() and afterInsert() delegate methods are still called as usual
	 *	when repeat records are inserted.  This is an additional hook that is called after
	 *  the standard hooks.
	 */
	public function afterAddRepeat(Dataface_Record $event, modules_calendar_RepeatEvent $repeat);
	
	/**
	 * @brief Hook executed before a repeat record is deleted.
	 *
	 * @param Dataface_Record $event The repeat record that is to be deleted.
	 * @param modules_calendar_RepeatEvent $repeat The repeat object that stores all of the information
	 *		about the repeat including reference to the original record, the frequency, etc..
	 * @throw Exception Throwing an exception from this method signals the delete to fail.
	 *
	 * @attention The beforeDelete()  delegate method is still called as usual
	 *	when repeat records are inserted.  This is an additional hook that is called before
	 *  the standard hooks.
	 */
	public function beforeDeleteRepeat(Dataface_Record $event, modules_calendar_RepeatEvent $repeat);
	
	
	/**
	 * @brief Hook executed after a repeat record is deleted.
	 *
	 * @param Dataface_Record $event The repeat record that is to be deleted.
	 * @param modules_calendar_RepeatEvent $repeat The repeat object that stores all of the information
	 *		about the repeat including reference to the original record, the frequency, etc..
	 *
	 * @attention The afterDelete() delegate method is still called as usual
	 *	when repeat records are inserted.  This is an additional hook that is called after
	 *  the standard hooks.
	 */
	public function afterDeleteRepeat(Dataface_Record $event, modules_calendar_RepeatEvent $repeat);
	

}