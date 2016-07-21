<?php
/**
 * @file
 * Contains \Drupal\hzd_customizations\Controller\Inactiveusers.
 */
namespace Drupal\hzd_customizations\Controller;

use Drupal\Core\Controller\ControllerBase;
// use Drupal\problem_management\HzdStorage;
// use Drupal\hzd_services\HzdservicesHelper;
// use Drupal\problem_management\HzdproblemmanagementHelper;

// use Drupal\Core\Url;

/**
 * Class Inactiveusers
 * @package Drupal\hzd_customizations\Controller
 */
class Inactiveusers extends ControllerBase {
/*
 * Callback for the read excel file
 * Use the function for the cron run
 */
  function inactive_users() {
/**
   $header_values = array(
		  'sno', 'status', 'service', 'function', 'release', 'title' ,
		  'problem_text', 'diagnose', 'solution', 'workaround', 'version', 'priority', 
		  'taskforce', 'comment', 'processing', 'attachment', 'eroffnet', 
                  'closed', 'problem_status', 'ticketstore_count', 'ticketstore_link'
		  );
   
   $path = \Drupal::config('problem_management.settings')->get('import_path');
   if ($path) {
     if(file_exists($path)) {
      $output = HzdproblemmanagementHelper::importing_problem_csv($path, $header_values);
     }
     else {
       $mail = \Drupal::config('problem_management.settings')->get('import_mail');
       $subject = 'Error while import';
       $body = t("There is an issue while importing of the file" . $path . ". The filename does not exist or it could have been corrupted.");
       HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
       $status = t('Error');
       $msg = t('No import file found');
       HzdStorage::insert_import_status($status, $msg);
       $output = 'File Does Not EXIST';
     }
   }
   else {
     $mail = \Drupal::config('problem_management.settings')->get('import_mail');
     $subject = 'Error while import';
     $body = t("There is an issue while importing of the problems from file" . $path . "Check whether the format of problems is in proper CSV format or not.");

     HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
     // $output =  t('Set the import path at') . \Drupal::l('set import path', Url::fromRoute('admin/config/problem'));  
    }
    $response = array(
      '#markup' => $output
     );
    return $response;

*/

//    variable_set('inactive_user_timestamp', time());
  $this->config('inactive_user.settings')
      ->set('inactive_user_timestamp', time());
  
    $user_list = '';

    // reset notifications if recent user activity
    $users = db_fetch_object(db_query('SELECT uid FROM {inactive_users} WHERE uid <> 1'));

    if ($users) {
      foreach ($users as $uid) {
        $u = db_fetch_object(db_query('SELECT access, name FROM {users} WHERE uid = %d', $uid));

        if ($u->access > time() - 604800) {
          // user activity in last week, remove from inactivity table
          db_query('DELETE FROM {inactive_users} WHERE uid = %d', $uid);

          watchdog('user', 'recent user activity: %user removed from inactivity list', 
           array('%user' => $u->name), WATCHDOG_NOTICE, l(t('edit user'), "user/$uid/edit", 
           array('query' => 
            array('destination' => 'admin/user/user')
           )
          )
         );
        }
      }
    }

    // notify administrator of inactive user accounts
    notify_admin_inactive_accounts();

    // notify users that their account has been inactive
    notify_user_inactive_accounts();

    // warn users when they are about to be blocked
    warn_to_block_inactive_accounts();
    
    // block user
    block_inactive_accounts();
    
    // warn users when they are about to be deleted
    warn_to_delete_inactive_accounts();

    // automatically delete users    
    delete_inactive_accounts();

    return t("Checked inactive users");

  }
}
