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
}