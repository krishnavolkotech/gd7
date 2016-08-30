<?php

namespace Drupal\problem_management;

use Drupal\problem_management\HzdStorage;
use Drupal\hzd_services\HzdservicesStorage;
use Drupal\hzd_services\HzdservicesHelper;
use Drupal\Core\Url;
use Drupal\Core\Breadcrumb\Breadcrumb;

class HzdproblemmanagementHelper {

    public static function problem_reset_element() {
        return $form['reset'] = array(
            '#type' => 'button',
            '#value' => t('Reset'),
            '#attributes' => array('onclick' => "reset_form_elements()"),
            '#prefix' => " <div class = 'reset_form'><div class = 'reset_all'>",
            '#suffix' => '</div><div style = "clear:both"></div> </div>'
        );
    }

//  Drupal::service('breadcrumb')->get()
// Drupal::service('breadcrumb')->set()
// Drupal::service('breadcrumb')->append()

    static function set_breabcrumbs_problems($type) {
        switch ($type) {
            case 'current' :
                $page = t('Current Problems');
                break;
            case 'archived' :
                $page = t('Archived Problems');
                break;
            default :
                $page = t('Archive');
                break;
        }
        $breadcrumb = array();
        $breadcrumb[] = \Drupal::l(t('Home'), Url::fromUserInput('/'));

        $request = \Drupal::request();
        $route_match = \Drupal::routeMatch();
        $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
        $group = $route_match->getParameter('group');
        if(is_object($group)){
        $group_id = $group->id();
        }else{
          $group_id= $group;
        }
       
        if (isset($group_name)) {
            $breadcrumb[] = \Drupal::l(t($group_name), Url::fromEntityUri(array('group', $group_id)));
            $breadcrumb[] = \Drupal::l($title, Url::fromEntityUri(array('group', $group_id, '/problems')));
        }
        $breadcrumb[] = $page;

        // Breadcrumb::setLinks($breadcrumb);
//  drupal_set_breadcrumb($breadcrumb);      
    }

    /*
     * Function returns the data according to the tabs(type of release)
     */

    static function problems_tabs_callback_data($type) {
        $result = array();
        $group = \Drupal::routeMatch()->getParameter('group');
        if(is_object($group)){
          $groupId = $group->id();
        } else {
          $groupId = $group;
        }
        
        $result['content']['#attached']['library'] = array('problem_management/problem_management',
            'hzd_customizations/hzd_customizations');

        // $result['content']['breadcrumb']['#breadcrumb'] =  $this->set_breabcrumbs_problems($string);
        $result['content']['#attached']['drupalSettings']['search_string'] = t('Search Title, Description, cause, Workaround, solution');
        $result['content']['#attached']['drupalSettings']['group_id'] = $groupId;
        $result['content']['#attached']['drupalSettings']['type'] = $type;

        $output = '';
        $result['content']['#prefix'] = "<div id = 'problem_search_results_wrapper'>";
        $result['content']['problems_filter_element'] = \Drupal::formBuilder()->getForm('\Drupal\problem_management\Form\ProblemFilterFrom', $type);
        $result['content']['problems_reset_element']['#prefix'] = "<div class = 'reset_form'>";
        $result['content']['problems_reset_element']['form'] = self::problem_reset_element();
        $result['content']['problems_reset_element']['#suffix'] = '</div><div style = "clear:both"></div>';
        $sql_where = !empty($_SESSION['sql_where']) ? $_SESSION['sql_where'] : NULL;
        $limit = !empty($_SESSION['limit']) ? $_SESSION['limit'] : NULL;
        $result['content']['problems_default_display'] = HzdStorage::problems_default_display($sql_where, $type, $limit);
        $result['content']['#suffix'] = "</div>";

        return $result;
    }

    /*
     * @path: path of the problems csv file stored,
     * @header_values: header values of problems, which must be equal to the header values of the csv file

     * Reads csv file and saves as nodes
     * Check the file separator,presently we are usng ';' as separator
     * validates the csv file format, and check for the existance of service in the database in the function "validate_csv"
     * function saving_problem_node saves the data as problems node , only if the validation returns true
     * status of the import is stored in the "insert_import_status"
     */

    static public function importing_problem_csv($path, $header_values) {
        setlocale(LC_ALL, 'de_DE.UTF-8');
        $handle = fopen($path, "r");
        if (fopen($path, "r")) {
            $count = 1;
            $readdata = fgetcsv($handle, 5000, ",");
                    
            if ($readdata) {
                while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
                    if ($count == 0) {
                        $heading = $data;
                  
                    } else {
           
                        foreach ($data as $key => $value) {
                            $values[$header_values[$key]] = $data[$key];
                              
                        }
                 
                        if (count($values) == 1) {
                            // $mail = variable_get('import_mail', ' ');
                            $mail = \Drupal::config('problem_management.settings')->get('import_mail');
                            $subject = 'Error while import';
                            $body = t("There is an issue while importing of the file" . $path . ". The details of error is provided below.");
                            HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
                            $status = t('Error');
                            $msg = t('Import file could not be parsed.');
                            HzdStorage::insert_import_status($status, $msg);
                            // watchdog('problem', $message, array(), 'error');
                            \Drupal::logger('problem')->error($msg);
                            return t('Error with file either permissions denied or file corrupted');
                        }   
                        
                 
               
                        $validation = self::validate_csv($values);
                        
                        if ($validation) {
                           
                            $insert = HzdStorage::saving_problem_node($values);
                            if ($insert) {
                                // TO DO :  New Node Inserted 
                                $output = 'New Node Inserted';
                            } else {
                                // $mail = variable_get('import_mail', ' ');
                                $mail = \Drupal::config('problem_management.settings')->get('import_mail');
                                $subject = 'Error while import';
                                $body = t("There is an issue while importing of the file" . $path . ". The details of error is provided below.");
                                HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
                                $status = t('Error');
                                $msg = t('Import file could not be parsed.');
                                HzdStorage::insert_import_status($status, $msg);
                                // watchdog('problem', $message, array(), 'error');
                                \Drupal::logger('problem')->error($msg);
                                return t('Error with file either permissions denied or file corrupted');
                            }
                        } else {
                            // $mail = variable_get('import_mail', ' ');
                            $mail = \Drupal::config('problem_management.settings')->get('import_mail');
                            $subject = 'Error while import';
                            $body = t("There is an issue while importing of the file" . $path . ". The details of error is provided below.");
                            HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
                            $status = t('Error');
                            $msg = t('Import file could not be parsed.');
                            HzdStorage::insert_import_status($status, $msg);
                            // watchdog('problem', $message, array(), 'error');
                            \Drupal::logger('problem')->error($msg);
                            return t('Error with file either permissions denied or file corrupted');
                        }
                    }
                    $count++;
                } 
                $status = t('OK');
                $msg = t('File imported sucessfully.');
                HzdStorage::insert_import_status($status, $msg);
                // watchdog('problem', $message, array(), 'error');
                \Drupal::logger('problem')->error($msg);
                return $msg;
            }
            else {
                // $mail = variable_get('import_mail', ' ');
                $mail = \Drupal::config('problem_management.settings')->get('import_mail');
                $subject = t('Error while import');
                $body = t("There is an issue while importing of the file" . $path . ". No Data Found in imported csv");
                HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
                $status = t('Error');
                $msg = t('No Data Found in imported csv');
                HzdStorage::insert_import_status($status, $msg);
                // watchdog('problem', $message, array(), 'error');
                \Drupal::logger('problem')->error($msg);
                return t('No Data Found in imported csv');         
            }
        } else {
            // $mail = variable_get('import_mail', ' ');
            $mail = \Drupal::config('problem_management.settings')->get('import_mail');
            $subject = t('Error while import');
            $body = t("There is an issue while importing of the file" . $path . ". Error with file either permissions denied or file corrupted or No data in file.");
            HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);

            $status = t('Error');
            $msg = t('Import file could not be parsed.');
            HzdStorage::insert_import_status($status, $msg);
            // watchdog('problem', $message, array(), 'error');
            \Drupal::logger('problem')->error($msg);
            return t('Error with file either permissions denied or file corrupted or No data in file');
        }
        return TRUE;
    }

    /*
     * validates the csv file
     * @values:values of the csv file
     * @returns:satus of the vaslidation
     * checks for the service existance with the service value given in the csv file.
     * returns TRUE if the service exists.
     */

    static public function validate_csv(&$values) {
       
        $type = 'problems';
        $service = $values['service'];
      
        if (!trim($values['sno'])) {
      
            return FALSE;
        }

        if (HzdservicesHelper::service_exist(trim($service), $type)) {
            $services = HzdservicesStorage::get_related_services($type);
            $service_id = array_keys(array_map('strtoupper', $services), strtoupper($service));
            $values['service'] = $service_id[0];
         
            return TRUE;
        } else {
            $mail = \Drupal::config('problem_management.settings')->get('import_mail');
            $subject = 'New service found while importing problems';
            $body = t(" We have found a new service " . $service . " which does not match the service in our database.");
            HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
            return FALSE;
        }
        
   
        return FALSE;
        /*
          $service = db_result(db_query("select nid from {node} where title = '%s' and type = '%s'", $values['service'], 'services'));
          if ($service) {
          $values['service'] = $service;
          return TRUE;
          }
          else {
          $sucess = save_problem_services($values);
          if ($sucess) {
          $nid = db_result(db_query("select max(nid) from node where type = '%s'", 'services'));
          drupal_set_message($nid);
          $values['service'] = $nid;
          return TRUE;
          }
          else {
          return FALSE;
          }
          }
          return FALSE;
         */
    }

    /**
     * Add River flow display of content on group home page
     */
    function _river_flow_content_field(&$form, $default = 0) {
        if (user_access('create group content')) {
            $arg = arg(1);
            if (is_numeric($arg)) {
                $node = node_load($arg);
            }
            // Add fieldset without affecting any other elements there
            $form['river_flow']['#type'] = 'fieldset';
            $form['river_flow']['#title'] = t('Home Page Display');
            $form['river_flow']['#collapsible'] = TRUE;
            $form['river_flow']['river_flow_content'] = array(
                '#type' => 'radios',
                '#options' => array('Default Page', 'Content River Flow'),
                '#default_value' => \Drupal::config('problem_management.settings')->get('og_default_homepage_display_' . $node->nid),
//      '#default_value' => variable_get('og_default_homepage_display_' . $node->nid, 0),
            );
        }
    }

}
