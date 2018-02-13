<?php

namespace Drupal\hzd_notifications;

/**
 * Dispatches the notification for actions performed on an entity.
 */
interface NotificationDispatcherInterface {

  /**
   * Dispatches the notification. This method does not assume any delivery
   * mechanism and depends on implementations. Might be mail, sms or mobile
   * notifications.
   *
   * @param array $dispatch_data
   *  The data to be used for dispatch.
   *
   * @return mixed
   *  Returns data about success or failure for the dispatch. The return data
   *  vary depending on the implementation service.
   */
  public function dispatch(array $dispatch_data);

  /**
   * Gets all the required data for passed notifications and dispatches them.
   */
  public function getNotificationsAndDispatch();

  /**
   * Returns the pending notifications.
   *
   * @return mixed
   *  The data for pending notifications.
   */
  public function getPendingNotifications();

  /**
   * Marks the notifications as read. The action can be anything like status update
   * or record deletion from database.
   *
   * @param array $notifications
   *  The notification ID's which needs to be marked as dispatched.
   */
  public function markAsDispatched(array $notifications);

  /**
   * Marks the notifications as failed. The action can be anything like status update
   * or record deletion from database.
   *
   * @param array $notifications_data
   *  The array of notification ID as key and  value as 'key' => 'value' sub array
   *  which has additional data be marked as failed.
   *
   * Structure of $notifications_data:
   *
   * [
   *   '1' => [
   *     'field1' => 'value',
   *     'field2' => 'value',
   *   ],
   *   '2' => [
   *     'field1' => 'value',
   *     'field2' => 'value',
   *   ],
   * ]
   */
  public function markAsFailed(array $notifications_data);
}