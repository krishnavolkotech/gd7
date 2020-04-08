<?php

namespace Drupal\hzd_release_management;

use Dompdf\Exception;
use Drupal\Core\Render\Markup;
use Drupal\cust_group\Controller\AccessController;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContent;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\user\Entity\User;


// if(!defined('KONSONS'))
//  define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
// if(!defined('RELEASE_MANAGEMENT'))
//  define('RELEASE_MANAGEMENT', 32);.
if (!defined('KONSONS'))
  define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));

/**
 * $_SESSION['Group_id'] = 339;.
 */
class HzdreleasemanagementStorage {

  /**
   * Reading csv files of Releases.
   *
   * @handle:file object.
   * @header_values: header colums
   * @type:type of the file (release, in progress, locked)
   * @returns the status.
   */
  static public function release_reading_csv($handle, $header_values, $type, $file_path) {
    try {
      setlocale(LC_ALL, 'de_DE.UTF-8');
      // Delete the nodes if the release type is locked or in progress.
      $types = array('released' => 1, 'progress' => 2, 'locked' => 3, 'ex_eoss' => 4, 'archived' => 5);
      $release_type = $types[$type];

      // droy, 20110531, Added AND clause to the following two DELETE statements so that one particular
      // in-progress release will not get deleted.
      if ($release_type == 3) {
        /* db_query("DELETE FROM {node} WHERE nid in (SELECT nid FROM {content_type_release} ctr WHERE ctr.field_release_type_value = '%s' and field_status_value != 'Details bitte in den Early Warnings ansehen')", $release_type);
          db_query("DELETE FROM {content_type_release} WHERE field_release_type_value = '%s' and field_status_value != 'Details bitte in den Early Warnings ansehen'", $release_type); */
        $node_ids = db_select('node__field_release_type', 'nfrt');
        $node_ids->join('node__field_status', 'nfs', 'nfrt.entity_id = nfs.entity_id');
        $node_ids->Fields('nfrt', array('entity_id'));
        $node_ids->condition('nfs.field_status_value', 'Details bitte in den Early Warnings ansehen', '!=');
        $node_ids->condition('nfrt.field_release_type_value',$release_type);
        $nids = $node_ids->execute()->fetchCol();

        //Not sure is this is really necessary here.
        $query = db_select('node', 'n');
        $query->Fields('n', array('nid'));
        $query->condition('nid', $nids, 'IN');
        $locked_values = $query->execute()->fetchAll();
        // $release_management_group_id = $query->execute()->fetchCol();
        foreach ($locked_values as $locked_value) {
          if (isset($locked_value)) {
            $locked_nid_values[] = $locked_value->nid;
          }
        }
        if (fopen($file_path, "r")) {
          $file = fopen($file_path, "r");
          if ($release_type == 3) {
            $header_values['4'] = 'comment';
          }
          self::release_inprogress_reading_csv($file, $header_values, $locked_nid_values, $release_type);
        }
      }

      // Removed releases from "in progress" that do not appear in the release database anymore.
      if ($release_type == 2) {
        $inprogress_values = db_select('node__field_release_type', 'nfrt')
          ->Fields('nfrt', array('entity_id'))
          ->condition('nfrt.field_release_type_value', '2', '=')
          ->execute()->fetchAll();
$inprogress_nid_values = [];
        foreach ($inprogress_values as $inprogress_value) {
          $inprogress_nid_values[] = $inprogress_value->entity_id;
        }
        if (fopen($file_path, "r")) {
          $file = fopen($file_path, "r");
          self::release_inprogress_reading_csv($file, $header_values, $inprogress_nid_values);
        }
      }
      $count = 0;
      $sucess = false;
      while (($data = fgetcsv($handle, 5000, ";")) !== FALSE) {
        $explodedData = explode(',', $data[0]);
//            pr($explodedData);exit;
        if ($count == 0) {
          $heading = $data;
        } else {
          foreach ($explodedData as $key => $value) {
            // droy: removed utf8_encode since it gives problems with data which is already utf8
            // $values[$header_values[$key]] = utf8_encode($data[$key]);.
//                    pr($value);
            $values[$header_values[$key]] = $explodedData[$key];
          }
          $values['type'] = $type;
          if (isset($values['status'])) {
            if (stripos($values['status'], 'Archiv') !== FALSE) {
              $values['type'] = 'archived';
            }
          }
          // $values['type'] = SafeMarkup::checkPlain($type);
          //pr($values);exit;
                    
          if ($values['title']) {
            $validation = HzdreleasemanagementHelper::validate_releases_csv($values);
            if ($validation) {
              $sucess = self::saving_release_node($values);
            }
          }
        }
        $count++;
      }
    }
    catch (Exception $e) {
      \Drupal::logger('hzd_release_management')->error($e->getMessage());
      $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
      $subject = 'Error while releases import';
      $body = $e->getMessage();
      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
    }
    if ($sucess) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  /**
   * Function for saving release node.
   */
  static public function saving_release_node($values) {
    try {
      global $user;
      if (!$values['title']) {
        // @sending mail to user when file need permissions or when file is corrupted
        $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
        $subject = 'Error while import';
        $body = t("Required fields not found");
        HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
        $response = $type . t(' ERROR WHILE READING');
      }

      $query = \Drupal::database()->select('groups_field_data', 'gfd');
      $query->Fields('gfd', array('id'));
      $query->condition('label', 'release management', '=');
      $group_id = $query->execute()->fetchCol();

      $query = db_select('node_field_data', 'n');
      $query->Fields('n', array('nid', 'vid', 'created'));
      $query->condition('n.type', 'release', '=');
      $query->condition('n.title', $values['title'], '=');
      $node_info = $query->execute()->fetchAll();

      foreach ($node_info as $info) {
        $nid = $info->nid;
        $vid = $info->vid;
        $created = $info->created;
      }

      $types = array('released' => 1, 'progress' => 2, 'locked' => 3, 'ex_eoss' => 4, 'archived' => 5);
      //$release_types = [1 => 'released', 2 => 'progress', 3 => 'locked', 4 => 'ex_eoss'];

      if (isset($nid) && $nid) {
        // $field_date_value = db_result(db_query("select field_date_value from {content_type_release} where nid=%d", $nid));.
        $field_date_value_query = db_select('node__field_date', 'ntr')
          ->Fields('ntr', array('field_date_value'))
          ->condition('entity_id', $nid, '=');

        $field_date_value = $field_date_value_query->execute()->fetchAssoc();
        // $field_release_value = db_result(db_query("select field_release_type_value from {content_type_release} where nid=%d", $nid));.
        $field_release_value_query = db_select('node__field_release_type', 'ntr')->Fields('ntr', array('field_release_type_value'))->condition('entity_id', $nid, '=');

        $field_release_value = $field_release_value_query->execute()->fetchAssoc();
        // $field_documentation_link_value = db_result(db_query("SELECT field_documentation_link_value
        //                                                     FROM {content_type_release} WHERE nid=%d", $nid));.
        $field_documentation_link_value_query = db_select('node__field_documentation_link', 'ntr')->Fields('ntr', array('field_documentation_link_value'))->condition('entity_id', $nid, '=');

        $field_documentation_link_value = $field_documentation_link_value_query->execute()->fetchAssoc();
      } else {
        $field_date_value = ['field_date_value' => null];
        $field_release_value = ['field_release_type_value' => null];
        $field_documentation_link_value = ['field_documentation_link_value' => null];
      }
      if (isset($nid) && $nid) {
        $node = Node::load($nid);
        //No need to set vid, uid, created, type field values
        //$node->set("vid", $vid);
        //$node->set("uid", 1);
        //$node->set("created", time());
        //$node->set("type", 'release');
        $existing_node_values['title'] = $node->getTitle();
        $existing_node_values['status'] = $node->get('field_status')->value;
        $existing_node_values['service'] = $node->get('field_relese_services')->referencedEntities()[0]->id();
        $existing_node_values['datum'] = $node->get('field_date')->value;
        if ($values['type'] == 'locked') {
          $existing_node_values['comment'] = $node->get('field_release_comments')->value;
        } else {
          $existing_node_values['link'] = $node->get('field_link')->value;
          $existing_node_values['documentation_link'] = $node->get('field_documentation_link')->value;
        }
       $existing_node_values['type'] = $node->get('field_release_type')->value;
        $csvvalues = array();
        $csvvalues = $values;
        $csvvalues['type'] = $types[$values['type']];
        // unset($csvvalues['type']);
        if (count(array_diff($csvvalues, $existing_node_values)) != 0) {
            $node->setTitle($values['title']);
            $node->set("comment", 2);
            $node->set("field_status", $values['status']);
            $node->set("field_relese_services", $values['service']);
            $node->set("field_date", $values['datum']);
            if ($values['type'] == 'locked') {
              $node->set("field_release_comments", $values['comment']);
            } else {
              $node->set("field_link", $values['link']);
              $node->set("field_documentation_link", $values['documentation_link']);
            }

            $node->set("field_release_type", $types[$values['type']]);
            $node->set("status", 1);
            $node->save();
        }
      } else {
        $node_array = array(
          // 'nid' => ($nid ? $nid : ''),
          // 'vid' => ($vid ? $vid : ''),.
          'uid' => 1,
          'created' => time(),
          'type' => 'release',
          'title' => $values['title'],
          'revision' => 0,
          'op' => 'Save',
          'submit' => 'Save',
          'preview' => 'Preview',
          'form_id' => 'release_node_form',
          'comment' => 2,
          'field_status' => array(
            '0' => array(
              'value' => $values['status'],
            ),
          ),
          'field_relese_services' => array(
            '0' => array(
              'target_id' => $values['service'],
            ),
          ),
          'field_date' => array(
            '0' => array(
              'value' => $values['datum'],
            ),
          ),
          'field_release_type' => array(
            '0' => array(
              'value' => $types[$values['type']],
            ),
          ),
          'field_release_type' => array(
            '0' => array(
              'value' => $types[$values['type']],
            ),
          ),
          /**
           * 'og_initial_groups' => Array(
           * '0' => $release_management_group_id['0'],
           * ),
           * 'og_public' => 0,
           * 'og_groups' => Array(
           * $release_management_group_id => $release_management_group_id['0'],
           * ),
           */
          'notifications_content_disable' => 0,
          'teaser' => '',
          'validated' => 1,
        );

        if ($values['type'] == 'locked') {
          $node_array['field_release_comments'] = array(
            '0' => array(
              'value' => $values['comment'],
            ),
          );
        } else {
          $node_array['field_link'] = array(
            '0' => array(
              'value' => $values['link'],
            ),
          );

          $node_array['field_documentation_link'] = array(
            '0' => array(
              'value' => $values['documentation_link'],
            ),
          );
        }

        $node = Node::create($node_array);
        $node->set("status", 1);
        $node->save();
        $nid = $node->id();
        if ($node->id()) {
          // $group_id = \Drupal::routeMatch()->getParameter('group');
          $group = Group::load($group_id['0']);
          $group_content = GroupContent::create([
            'type' => $group->getGroupType()->getContentPlugin('group_node:release')->getContentTypeConfigId(),
            'gid' => $group_id['0'],
            'entity_id' => $node->id(),
            'request_status' => 1,
            'label' => $values['title'],
            'uid' => 1,
          ]);
          $group_content->save();
        }
      }

      // $title = db_result(db_query("select title from {node} where title = '%s' ", $values['title']));.
      $title_query = db_select('node_field_data', 'nfd')
        ->Fields('nfd', array('title'))
        ->condition('title', $values['title'], '=')->execute()->fetchAssoc();

      $title = $title_query['title'];
      // Downloading the documentation link.
      $params = array(
        "title" => $title,
        "date_value" => $field_date_value['field_date_value'] ?: null,
        "release_value" => $field_release_value['field_release_type_value'] ?: null,
        "doku_link" => $field_documentation_link_value['field_documentation_link_value'] ?: null,
      );

      if ($values['type'] != 'locked') {
        self::documentation_link_download($params, $values);
      }
    }
    catch (Exception $e) {
      \Drupal::logger('hzd_release_management')->error($e->getMessage());
      $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
      $subject = 'Error while releases import';
      $body = $e->getMessage();
      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Reading csv file of inprogress releases.
   *
   * @file:file object.
   * @header_values: header colums
   * @inprogress_nid_values: nids where the release type is progress.
   */
  static public function release_inprogress_reading_csv($file, $header_values, $inprogress_nid_values, $type = '') {
    try {
      setlocale(LC_ALL, 'de_DE.UTF-8');
      $count_data = 0;
      $inprogress_csv_nid_values = [];
      while (($data = fgetcsv($file, 5000, ";")) !== FALSE) {
          $explodedData = explode(',', $data[0]);
        if ($count_data == 0) {
          $heading = $data;
        } else {
          if ($type == 3) {
            $header_values['4'] = 'comment';
          }
          foreach ($explodedData as $key => $value) {
            // droy: removed utf8_encode since it gives issues when data is already in utf8 (setlocale above).
            // $values[$header_values[$key]] = utf8_encode($data[$key]);.
            $values[$header_values[$key]] = $explodedData[$key];
          }

          $query = db_select('node_field_data', 'n');
          $query->Fields('n', array('nid'));
          $query->condition('title', $values['title'], '=');
          $inprogress_csv_nid = $query->execute()->fetchField();
          $inprogress_csv_nid_values[] = $inprogress_csv_nid;
        }        
        $count_data++;
      }
      if ($type == 3) {
        // Unpublish releases that are not present in locked csv file.
        self::locked_release_node($inprogress_csv_nid_values, $inprogress_nid_values);
      } else {
        // Remove releases that are not present in inprogress csv file.
        self::inprogress_release_node($inprogress_csv_nid_values, $inprogress_nid_values);
      }
    }
    catch (Exception $e) {
      \Drupal::logger('hzd_release_management')->error($e->getMessage());
      $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
      $subject = 'Error while releases import';
      $body = $e->getMessage();
      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
    }
  }

  /**
   * @param $locked_csv_nid_values
   * @param $locked_nid_values
   */
  static public function locked_release_node($locked_csv_nid_values, $locked_nid_values) {
    //Writing an equivalent code below @sandeep 20180315
    // if (is_array($locked_nid_values)) {
    //   foreach ($locked_nid_values as $release_title_nid_values) {
    //     if (!in_array($release_title_nid_values, $locked_csv_nid_values)) {
    //       db_update('node_field_data')->fields(array('status' => '0'))
    //               ->condition('nid', $release_title_nid_values)->execute();
    //     }
    //   }
    // }


    foreach(array_diff((array)$locked_nid_values, (array)$locked_csv_nid_values) as $val){
      $node = Node::load($val);
      $node->set("status", 0);
      $node->save();
    }
  }

  /**
   *
   * @inprogress_csv_nid_values: inprogress csv file nids.
   * @inprogress_nid_values: nids where the release type is progress.
   */
  static public function inprogress_release_node($inprogress_csv_nid_values, $inprogress_nid_values) {
    //Writing an equivalent code below @sandeep 20180308
    // if (is_array($inprogress_nid_values)) {
      // foreach ((array)$inprogress_nid_values as $release_title_nid_values) {
      //   if (!in_array($release_title_nid_values, $inprogress_csv_nid_values)) {
      //     // 20140730 droy - Instead of unpublishing a release, move it to status rejected.
      //     // db_query("UPDATE {node} SET status = %d WHERE nid = %d", 0, $release_title_nid_values);.
          
      //   }
      // }
    // }
    foreach(array_diff($inprogress_nid_values, $inprogress_csv_nid_values) as $val){
      $node = Node::load($val);
        $node->set("field_release_type", 4);
        $node->set('status',1);
        $node->save();
      }
    }

  /**
   * Function for documentation link.
   */
  static public function documentation_link_download($params, $values) {
    try {
      $title = $params['title'];
      $field_release_value = $params['release_value'];
      $field_date_value = $params['date_value'];
      $field_documentation_link_value = $params['doku_link'];
      $values_service = $values['service'];
      $values_title = $values['title'];

      $link = $values['documentation_link'];

      $values_date = $values['datum'];

      // $service = strtolower(db_result(db_query("SELECT title FROM {node} where nid= %d", $values_service)));.
      $service_query = db_select('node_field_data', 'nfd')
        ->Fields('nfd', array('title'))
        ->condition('nid', $values_service, '=');
      $service_query = $service_query->execute()->fetchAssoc();
      $service = $service_query['title'];
      // $nid = db_result(db_query("SELECT nid FROM {node} where title = '%s' ", $values_title));.
      $db = \Drupal::database();
      $query = $db->select('node_field_data', 'nfd');
      $query->fields('nfd', array('nid'));
      $query->condition('title', $values_title, '=');
      $nid_query = $query->execute()->fetchAssoc();

      $nid = $nid_query['nid'];
      // Create url alias.
      /**
       * $release_value_type = db_result(db_query("SELECT field_release_type_value
       * FROM {content_type_release} WHERE nid = %d ", $nid));
       */
      $release_value_type_query = db_select('node__field_release_type', 'nfrt')
        ->Fields('nfrt', array('field_release_type_value'))
        ->condition('entity_id', $nid, '=');
      $release_value_type_q = $release_value_type_query->execute()->fetchAssoc();
      $release_value_type = $release_value_type_q['field_release_type_value'];

      if (isset($release_value_type['field_release_type_value']) && $release_value_type['field_release_type_value'] != 3) {
        $url_alias = create_url_alias($nid, $service, $values);
      }
      /**
       * $count_nid = db_result(db_query("SELECT count(*)
       * FROM {release_doc_failed_download_info}
       * WHERE nid = %d", $nid));
       */
      $count_nid_query = db_select('release_doc_failed_download_info', 'rdfdi')
        ->Fields('rdfdi', array('nid'))
        ->condition('nid', $nid, '=');

      $count_nid = $count_nid_query->countQuery()->execute()->fetchField();

      $field_release_type = db_select('node__field_release_type', 'nfrt')
        ->Fields('nfrt', array('field_release_type_value'))
        ->condition('entity_id', $nid, '=')->execute()->fetchAssoc();

      /**
       * $field_release_type_value = db_result(db_query("SELECT field_release_type_value
       * FROM {content_type_release}
       * WHERE nid=%d", $nid));
       */
      $field_release_type_value = $field_release_type['field_release_type_value'];

      // Checked documentation link empty or not.
      if ($link != '') {
        /* Check It is new release or not
         * Check Release status changes from inprogress to released
         * Check how many times release import attempted.If three attempts unsuccesssful failure is perment.
         * Check released release date/time changed or not.
         */

        if ((!$title) || ($field_release_value == 2 && $field_release_type_value == 1) || (($count_nid < 3) && ($count_nid >= 0)) || (($field_date_value != $values_date) && ($field_release_type_value == 1))) {

          $removePreviousData = 0;
          if (($values_date != $field_date_value) || ($field_release_value == 2 && $field_release_type_value == 1) || $field_documentation_link_value != $link) {
            $removePreviousData = 1;
            
          }
          self::do_download_documentation($nid, $values_title, $link, $service, $title, $field_documentation_link_value, $removePreviousData);

        }
      }
    }
    catch (Exception $e) {
      \Drupal::logger('hzd_release_management')->error($e->getMessage());
      $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
      $subject = 'Error while releases import';
      $body = $e->getMessage();
      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
    }
  }


  /**
   * Downloads documentation based on the service and release data provides
   * 
   * 
   * 
   */
  static function do_download_documentation($nid, $values_title, $link, $service,$title, $field_documentation_link_value, $removePreviousData = 0){
          list($release_title, $product, $release, $compressed_file, $link_search) = self::get_release_details_from_title($values_title, $link);
          // Check secure-download string is in documentation link. If yes excluded from documentation download.
          if (empty($link_search)) {
            $root_path = \Drupal::service('file_system')->realpath("private://");
            $path = \Drupal::service('file_system')->realpath("private://") . "/releases";
            // $service = "'" . $service . "'";.
            $release_title = strtolower($release_title);
            $service = strtolower($service);
            $product = strtolower($product);
            $paths = $path . "/" . $service . "/" . $product . "/" . $release_title . "/dokumentation";

            // Check the directory exist or not.
            if (!is_dir(str_replace("'", "", $paths))) {
              shell_exec("mkdir -p " . $path . "/" . $service . "/" . $product . "/" . $release_title . "/dokumentation");
            }
            $existing_zip_file = $paths . "/" . $compressed_file;

            /*
             * Remove Documentation directory folders.
             * Check Release status changes from inprogress to released.
             * Check released release date/time changed or not.
             */
            if ($removePreviousData) {

              $dokument_zip = explode("/", $field_documentation_link_value);
              $dokument_zip_file_name = strtolower(array_pop($dokument_zip));
              $root_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
              $zip_version_path = $root_path . "/releases/downloads/" . $title . "_doku.zip";
              if (!empty($dokument_zip_file_name)) {
                if (file_exists($zip_version_path)) {
                  shell_exec("rm -rf " . $zip_version_path);
                }
              }
              $remove_docs = scandir(str_replace("'", "", $paths));
              unset($remove_docs[0]);
              unset($remove_docs[1]);
              foreach ($remove_docs as $doc_files) {
                shell_exec("rm -rf " . $paths . "/" . $doc_files);
              }
              // cache_clear_all('release_doc_import_'.$nid, 'cache');.
//                        drupal_flush_all_caches();
            }
            $existing_paths_replace = str_replace("'", "", $paths);
            $scan_docu = scandir($existing_paths_replace);
            unset($scan_docu[0]);
            unset($scan_docu[1]);
            // Check Documentation directory empty or not.
            if (empty($scan_docu[2])) {
              if (is_dir($paths)) {
                $scan_dir = scandir($paths);
              }

              $username = \Drupal::config('hzd_release_management.settings')->get('release_import_username');
              $password = \Drupal::config('hzd_release_management.settings')->get('release_import_password');
              self::release_documentation_link_download($username, $password, $paths, $link, $compressed_file, $nid);
              // $nid_count = db_result(db_query("SELECT count(*)
              //                                           FROM {release_doc_failed_download_info} WHERE nid = %d", $nid));.
              $query = db_select('release_doc_failed_download_info', 'rdfdi')
                ->Fields('rdfi', array('nid'))
                ->condition('rdfdi.nid', $nid, '=');

              $nid_count = $query->countQuery()->execute()->fetchField();

              if ($nid_count == 3) {
                self::release_not_import_mail($nid);
              }
            }
          }
        }

  /**
   * Function for sending mail.
   */
  public static function release_not_import_mail($nid) {
    $get_mails = \Drupal::config('hzd_release_management.settings')->get('release_not_import');
    $to = explode(',', $get_mails);
    $module = 'hzd_release_management';
    $key = 'download';
    $from = \Drupal::config('system.site')->get('mail');
    $message_body = [];
    $message_body[] = [
    '#markup' => \Drupal::config('hzd_release_management.settings')->get('release_mail_body')['value']
    ];
    $message_body[] = [
    '#markup' => "Release : " . node_get_title_fast([$nid])[$nid]
    ];
    $params['message'] = \Drupal::service('renderer')->render($message_body);
    /**
     * $field_link_value = db_result(db_query("SELECT field_documentation_link_value
     * FROM {content_type_release}
     * WHERE nid = %d ", $nid));
     */
    
    $field_link_value_query = db_select('node__field_documentation_link', 'nfdl')
            ->Fields('nfdl', array('field_documentation_link_value'))
            ->condition('entity_id', $nid, '=');

    $field_link_value = $field_link_value_query->execute()->fetchAssoc();
    $get_release_link = explode("/", $field_link_value['field_documentation_link_value']);
    $get_release_link_value = array_pop($get_release_link);
    $params['subject'] = t('Release Documentation Failed: ') . $get_release_link_value;
    $send = TRUE;
    foreach ($to as $single_address) {
        $mailManager = \Drupal::service('plugin.manager.mail');
        $module  = 'hzd_release_management';
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = true;
        $mailManager->mail($module, $key, $single_address, $langcode, $params, NULL, $send);
//      problem_management_mail($key, $params);
      // drupal_mail($module, $key, $single_address, $language, $params, $from, $send);.
    }
  }

  /**
   * Function for sending mail.
   */
  public static function rarbeitsanleitungen_not_import_mail() {
    $get_mails = \Drupal::config('hzd_notifications.settings')->get('arbeitsanleitungen_not_import');
    $to = explode(',', $get_mails);
    $module = 'hzd_notifications';
    $key = 'download';
    $from = \Drupal::config('system.site')->get('mail');
    $message_body = [];
    $message_body[] = [
      '#markup' => \Drupal::config('hzd_notifications.settings')->get('arbeitsanleitungen_mail_body')['value']
    ];
    $message_body[] = [
      '#markup' => "Arbeitsanleitungen : Not imported.",
    ];
    $params['message'] = \Drupal::service('renderer')->render($message_body);

    $params['subject'] = t('Arbeitsanleitungen : Not imported. ');
    $send = TRUE;
    foreach ($to as $single_address) {
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'hzd_release_management';
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $mailManager->mail($module, $key, $single_address, $langcode, $params, NULL, $send);
    }
  }

  /**
   * Copy subfolders to Sonstige foldes.
   */
  public static function copy_subfolders_to_sonstige($dokument_path) {
    try {
      $sonstige_docs_path = $dokument_path . "/sonstige";
      $sonstige_path = $dokument_path . "/sonstige";
      if (is_dir(str_replace("'", "", $sonstige_path))) {
        $dockument_subfolders = self::move_subfolders_to_sonstige($sonstige_docs_path, $dokument_path, $sonstige_path);
      }
    }
    catch (Exception $e) {
      \Drupal::logger('hzd_release_management')->error($e->getMessage());
      $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
      $subject = 'Error while releases import';
      $body = $e->getMessage();
      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
    }
  }

  /**
   * Move subfolders to sonstige and remove subfolders of sonstige.
   */
  public static function move_subfolders_to_sonstige($sonstige_docs_path, $dokument_path, $sonstige_path) {
    try {
      $sonstige_docs_path_scandir = scandir(str_replace("'", "", $sonstige_docs_path));
      unset($sonstige_docs_path_scandir[0]);
      unset($sonstige_docs_path_scandir[1]);
      $remove_sonstige_sub_folders = null;
      foreach ($sonstige_docs_path_scandir as $sonstige_docs_path_values) {
        $subfolders_of_sontige = str_replace("'", "", $sonstige_docs_path . "/" . $sonstige_docs_path_values);
        $sontige_sub_docs = $sonstige_path . "/'" . $sonstige_docs_path_values . "'";
        if (is_dir($subfolders_of_sontige)) {
          $remove_sonstige_sub_folders = $subfolders_of_sontige;
          $remove_sub_doc = $sontige_sub_docs;
          self::move_subfolders_to_sonstige($subfolders_of_sontige, $dokument_path, $sontige_sub_docs);
        } else {
          $release_sonstige_doku = str_replace("'", "", $sonstige_docs_path . "/" . $sonstige_docs_path_values);
          $release_sonstige_path = $dokument_path . "/sonstige";
          shell_exec("mv " . $sontige_sub_docs . " " . $release_sonstige_path);

          if (isset($remove_sonstige_sub_folders) && is_dir($remove_sonstige_sub_folders)) {
            shell_exec("rm -rf " . $remove_sub_doc);
          }
        }
      }

      if (is_dir($remove_sonstige_sub_folders)) {
        shell_exec("rm -rf " . $remove_sub_doc);
      }
    }
    catch (Exception $e) {
      \Drupal::logger('hzd_release_management')->error($e->getMessage());
      $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
      $subject = 'Error while releases import';
      $body = $e->getMessage();
      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
    }
  }

  /**
   * Downloads documentation link and extracts.
   * If document download fail, stores error message.
   *
   * @param $username string, http authentication username
   * @param $password string, http authentication password
   * @param $paths , documentation file system path.
   * @param $link , documentation link
   * @param $compressed_file , zip directory name.
   * @param $nid , id of the release title.
   *
   * @return nothing
   */
  static public function release_documentation_link_download($username, $password, $paths, $link, $compressed_file, $nid) {
    try {

      shell_exec("wget --no-check-certificate --user='" . $username . "'  --password='" . $password . "' -P " . $paths . "  " . $link);
      $dokument_path = $paths;
      $remove_quotes = str_replace("'", "", $paths);
      $download_directory = scandir($remove_quotes);
      unset($download_directory[0]);
      unset($download_directory[1]);

      // Check documentation link downloaded or not.
      if (!empty($download_directory[2])) {
        shell_exec("unzip " . $paths . "/" . $compressed_file . " -d " . $paths);
        shell_exec("convmv -f latin1 -t utf8 -r --notest " . $paths);
        $zip_root_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
        $zip_lowcaps = strtolower($compressed_file);
        shell_exec("rm -rf " . $paths . "/" . $compressed_file);

        $extracted_file = scandir($remove_quotes);
        unset($extracted_file[0]);
        unset($extracted_file[1]);
        // Check documentation zip file extracted or not.
        if (!empty($extracted_file[2])) {
          $compressed_file_ex = explode(".zip", $compressed_file);
          $upper = strtolower($compressed_file_ex[0]);
          $paths = str_replace("'", "", $paths);
          $paths = trim($paths, "'");
          $extract_file = scandir($paths);
          // Check documentation folder exist or not.
          if ((is_dir($paths . "/" . $extract_file[2])) && (!empty($extract_file[2]))) {
            rename($paths . "/" . $extract_file[2], $paths . "/" . $upper);
            $new_file = $paths . "/" . $upper;
            $sonstige_content = array();
            // $list_scandir = scandir($new_file);
            $list_scandir = array();
            if ($dh = opendir($new_file)) {
              while (($file = readdir($dh)) !== FALSE) {
                if (!is_dir($new_file . DIRECTORY_SEPARATOR . $file)) {
                  array_push($list_scandir, $file);
                }
              }
              closedir($dh);
            }
            $root_folders = array("afb", "benutzerhandbuch", "betriebshandbuch", "releasenotes", "sonstige", "zertifikat");
            // Move root level documents to sonstige folder.
            foreach ($list_scandir as $list_values) {
              if (!in_array($list_values, $root_folders)) {
                $new_path = $dokument_path . "/" . $upper;
                shell_exec("mkdir " . $new_path . "/sonstige");
                shell_exec("mv " . $new_path . "/" . $list_values . " " . $new_path . "/sonstige");
              }
            }
          }
          $doc_path = str_replace("'", "", $paths);
          $sub_dir_path = $doc_path . "/" . $upper;
          if (is_dir($sub_dir_path)) {
            $sub_dir = scandir($sub_dir_path);
            unset($sub_dir[0]);
            unset($sub_dir[1]);
            $new_path = $dokument_path . "/" . $upper;

            // Move all document sub folders to dokumentation folder.
            foreach ($sub_dir as $docs) {
              shell_exec("mv " . $new_path . "/" . $docs . " " . $dokument_path);
            }
            // Copy all subfolders into sonstige.
            self::copy_subfolders_to_sonstige($dokument_path);
          }
          $betriebshandbuch = $dokument_path . DIRECTORY_SEPARATOR . 'betriebshandbuch';
          $htmls = array_filter(scandir($betriebshandbuch),function($item){
            if (strtolower(substr($item, -9)) == '_html.zip') {
              return true;
            }
          });
          foreach ((array)$htmls as $html) {
	    //Extracting html.zip content into the HTML(New) folder
	    $html_folder_path = $betriebshandbuch . DIRECTORY_SEPARATOR . "html";
	    shell_exec('mkdir ' . $html_folder_path);

            $unzipCmd = 'unzip ' . $betriebshandbuch . DIRECTORY_SEPARATOR . $html . " -d " . $html_folder_path;
            shell_exec($unzipCmd);

	    // Unzip only first file found
	    break;
          }
          shell_exec("rm -rf " . $new_path);
          //Delete the failed log record (if any)
          \Drupal::database()->delete('release_doc_failed_download_info')
          ->condition('nid', $nid)
          ->execute();
        } else {
          // Using shell_exec function could not capture the error message.so insert the default message into  release_doc_failed_download_info  table.
          $failed_link = "Download file was not extracted";
          $failed_info = array(
            'nid' => $nid,
            'created' => time(),
            'reason' => $failed_link,
          );
          db_insert('release_doc_failed_download_info')->fields($failed_info)->execute();
        }
      } else {
        // Using shell_exec function could not capture the error message.so insert the default message into  release_doc_failed_download_info  table.
        $download_directory = scandir($remove_quotes);
        \Drupal::logger('hzd_release_management')->error("Error occurred in release download documentation link generate. For the node " . $nid);
        $doc_download_url = "wget --no-check-certificate --user='" . $username . "'  --password='" . $password . "' -P " . $paths . "  " . $link;
        \Drupal::logger('hzd_release_management')->error($doc_download_url);
        \Drupal::logger('hzd_release_management')->error(print_r($download_directory, TRUE));
        $failed_link = "Documentation link was not downloaded";
        $failed_info = array(
          'nid' => $nid,
          'created' => time(),
          'reason' => $failed_link,
        );
        db_insert('release_doc_failed_download_info')->fields($failed_info)->execute();
      }
    }
    catch (Exception $e) {
      \Drupal::logger('hzd_release_management')->error($e->getMessage());
      \Drupal::logger('hzd_release_management')->error("Error occurred in release download documentation link 
       generate. For the node " . $nid);
      $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
      $subject = 'Error while releases import';
      $body = $e->getMessage();
      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
    }
  }

  /**
   * Get release details from release title.
   */
  static public function get_release_details_from_title($values_title, $link) {
    $release_title = $values_title;
    $get_release_title = explode("_", $release_title);
    $product = $get_release_title[0];
    $release = $get_release_title[1];
    $product = strtolower($product);
    $link_explode = explode('/', $link);
    $compressed_file = array_pop($link_explode);
    $link_search = array_search('secure-downloads', $link_explode);
    return array($release_title, $product, $release, $compressed_file, $link_search);
  }

  static public function has_deployed_info($node) {
    if ($node->getType() == 'deployed_releases') {
      if (!is_null($node->field_previous_release->value)) {
          return TRUE;
      }
    }
    else {
        $service = $node->field_relese_services->target_id;
        $deployed_releases_node_ids = \Drupal::entityQuery('node')
            ->condition('type', 'deployed_releases', '=')
            ->condition('field_release_service', $service , '=')
            ->condition('field_earlywarning_release', $node->id(), '=')
            ->condition('field_previous_release', NULL, 'IS NOT NULL')
            ->execute();
        if (!empty($deployed_releases_node_ids)) {
            return TRUE;
        }
        
    }
    return FALSE;
  }
  
  /**
   *
   * @filter_options:filter options for filtering releases according to selected filters
   * @limit:page limit
   * @returns: table display of deployed releases
   */
  static public function deployed_releases_displaytable($service_release_type = KONSONS) {
    $deployed_releases_node_ids = \Drupal::entityQuery('node')
            ->condition('type', 'deployed_releases', '=');

    $filter_value = HzdreleasemanagementStorage::get_release_filters();
    $type = 'deployed_releases';
    $group_id = get_group_id();
    if (isset($filter_value['release_type'])) {
      $default_type = $filter_value['release_type'];
    } else {
      if ($group_id != RELEASE_MANAGEMENT) {
        $default_type = db_query("SELECT release_type "
                . "FROM {default_release_type} "
                . "WHERE group_id = :gid", array(":gid" => $group_id))->fetchField();
        $default_type = $default_type ? $default_type : KONSONS;
      } else {
        $default_type = KONSONS;
      }
    }
    if (!$filter_value['deployed_type']) {
      $filter_value['deployed_type'] = "current";
    }
    if ($filter_value['services']) {
      $deployed_releases_node_ids->condition('field_release_service', $filter_value['services'], '=');
    } else {
      $services_obj = db_query("SELECT n.title, n.nid
                     FROM {node_field_data} n, {group_releases_view} grv, 
                     {node__release_type} nrt 
                     WHERE n.nid = grv.service_id and n.nid = nrt.entity_id 
                     and grv.group_id = :gid and nrt.release_type_target_id = :tid 
                     ORDER BY n.title asc", array(
          ":gid" => $group_id,
          ":tid" => $default_type
              )
              )->fetchAll();
      $services = [];
      if (empty($services_obj)) {
        $services = [-1];
      }
      foreach ($services_obj as $services_data) {
        $services[] = $services_data->nid;
      }
//            kint($services);
      $deployed_releases_node_ids->condition('field_release_service', $services, 'IN');
    }
//        kint($deployed_releases_node_ids);exit;
    if (isset($filter_value['states']) && $filter_value['states'] != 1) {
      $deployed_releases_node_ids->condition('field_user_state', $filter_value['states'], '=');
    }
    if ($filter_value['releases']) {
      $deployed_releases_node_ids->condition('field_earlywarning_release', $filter_value['releases'], '=');
    }
    if ($filter_value['filter_startdate'] && preg_match ("/^([0-9]{2}).([0-9]{2}).([0-9]{4})$/", $filter_value['filter_startdate'])) {
      $startdate = DateTimePlus::createFromFormat('d.m.Y|', $filter_value['filter_startdate'], null, ['validate_format' => FALSE])->format('Y-m-d');
//            $startdate = mktime(0, 0, 0, date('m', $filter_value['filter_startdate']),
//                date('d', $filter_value['filter_startdate']), date('y', $filter_value['filter_startdate']));
      $deployed_releases_node_ids->condition('field_date_deployed', $startdate, '>=');
//            pr($startdate);exit;
    }
    if ($filter_value['filter_enddate'] && preg_match ("/^([0-9]{2}).([0-9]{2}).([0-9]{4})$/", $filter_value['filter_enddate'])) {
//            $enddate = mktime(23, 59, 59,
//                date('m', $filter_value['filter_enddate']), date('d', $filter_value['filter_enddate']),
//                date('y', $filter_value['filter_enddate']));
      $endDate = DateTimePlus::createFromFormat('d.m.Y H:i:s|', $filter_value['filter_enddate'] . ' 23:59:59', null, ['validate_format' => FALSE])->format('Y-m-d H:i:s');
//            $deployed_releases_node_ids->condition('field_date_deployed',
//                array($filter_value['filter_startdate'], $filter_value['filter_enddate']), 'BETWEEN');
      $deployed_releases_node_ids->condition('field_date_deployed', $endDate, '<=');
    }

    if ($filter_value['environment_type']) {
      $deployed_releases_node_ids->condition('field_environment', $filter_value['environment_type'], '=');
    }

    if ($filter_value['deployed_type']) {
      if ($filter_value['deployed_type'] == 'current') {
        $filter_type = $deployed_releases_node_ids->orConditionGroup();
        $filter_type->condition('field_archived_release', 1, '<>');
        $filter_type->condition('field_archived_release', NULL, 'IS');
        $deployed_releases_node_ids->condition($filter_type);
      } elseif ($filter_value['deployed_type'] == 'archived') {
        $deployed_releases_node_ids->condition('field_archived_release', 1, '=');
      } elseif ($filter_value['deployed_type'] == 'all') {
        $archive = $deployed_releases_node_ids->orConditionGroup();
        $archive->condition('field_archived_release', array('0' => 0, '1' => 1), 'IN');
        $archive->condition('field_archived_release', NULL, 'IS');
        $deployed_releases_node_ids->condition($archive);
      }
    }
    $deployed_releases_node_ids->sort('field_date_deployed', 'DESC');

//    $service_release_type = isset($filter_value['release_type']) 
//        ? $filter_value['release_type'] : $default_type;
//    $deployed_releases_node_ids->condition('grv.group_id', $gid, '=')
//            ->condition('nrt.release_type_target_id', $service_release_type, '=');
//    

    $gid = $group_id ? $group_id : RELEASE_MANAGEMENT;

    if ($filter_value['limit'] != 'all') {
      $page_limit = (isset($filter_value['limit']) ? $filter_value['limit'] : DISPLAY_LIMIT);
      $result = $deployed_releases_node_ids->pager($page_limit)->execute();
    } else {
      $result = $deployed_releases_node_ids->execute();
    }
    $rows = [];
    $current_uri = \Drupal::request()->getRequestUri();
//        pr($current_uri = \Drupal::request()->getRequestUri());exit;
    foreach ($result as $deployed_releases_node_id) {
      $deployed_release_node = \Drupal\node\Entity\Node::load($deployed_releases_node_id);
      $state = db_query("SELECT abbr FROM {states} where id = :id", array(
          ":id" => $deployed_release_node->field_user_state->value
              )
              )->fetchField();
      $service = \Drupal\node\Entity\Node::load(
                      $deployed_release_node->field_release_service->value)->get('title')->value;

      $release_node = \Drupal\node\Entity\Node::load(
                      $deployed_release_node->field_earlywarning_release->value);
      $release = ($release_node instanceof Node) ? $release_node->getTitle() : null;

      if ($deployed_release_node->field_environment->value == 1) {
        $environment_val = t('Produktion');
      } else if ($deployed_release_node->field_environment->value == 2) {
          $environment_val = t('Pilot');
      } else {
        $environment_val = \Drupal\node\Entity\Node::load(
                        $deployed_release_node->field_environment->value)->getTitle();
      }
//      // date("d.m.Y", 1900000)

      $has_depoyment_info = HzdreleasemanagementStorage::has_deployed_info($deployed_release_node);
      $elements = array(
          'state' => $state,
          'environment' => $environment_val,
          'service' => $service,
          'release' => $release,
          'dateDeployed' => date("d.m.Y", strtotime($deployed_release_node->field_date_deployed->value)
          )
      );
      if (isset($filter_value['states']) && $filter_value['states'] > 1) {
        // date("d.m.Y", 1900000)
        if ($filter_value['states'] == $deployed_release_node->field_user_state->value) {
          $elements = array(
              'state' => $state,
              'environment' => $environment_val,
              'service' => $service,
              'release' => $release,
              'dateDeployed' => date("d.m.Y", strtotime($deployed_release_node->field_date_deployed->value)
              )
          );
        } else {
          $elements = array();
        }
      }
      if (isset($group_id)) {
        $earlywarnings_nids = \Drupal::entityQuery('node')
                ->condition('type', 'early_warnings', '=')
                ->condition('field_earlywarning_release', $deployed_release_node->field_earlywarning_release->value, '=')
                ->condition('field_release_service', $deployed_release_node->field_release_service->value, '=');
        $earlywarnings_count = $earlywarnings_nids->count()->execute();
        if ($earlywarnings_count) {
          $warningclass = ($earlywarnings_count >= 10 ? 'warningcount_second' : 'warningcount');
          $view_options['query'] = array(
              'services' => $deployed_release_node->field_release_service->value,
              'releases' => $deployed_release_node->field_earlywarning_release->value,
              'release_type' => $type
          );
          $view_options['attributes'] = array(
              'class' => 'view-earlywarning',
              'title' => t('Read Early Warnings for this release'));
          $view_earlywarning_url = Url::fromRoute('hzd_earlywarnings.view_early_warnings', array('group' => $group_id), $view_options
          );
          $view_earlywarning = array(
              '#title' => array('#markup' => "<span class = '" . $warningclass . "'>" . $earlywarnings_count . "</span> "),
              '#type' => 'link',
              '#url' => $view_earlywarning_url,
          );
          $view_warning = \Drupal::service('renderer')->renderRoot($view_earlywarning);
        } else {
          $view_warning = t('<span class="no-warnigs"></span>');
        }

        // Early Warning create icon.
        $create_icon_path = drupal_get_path('module', 'hzd_release_management') . '/images/create-icon.png';
        $create_icon = '<img height=15 src = "/' . $create_icon_path . '">';

        $options['query'] = array(
            'services' => $deployed_release_node->field_release_service->value,
            'releases' => $deployed_release_node->field_earlywarning_release->value,
            'release_type' => $type
        );
        $options['query']['destination'] = $current_uri;
        $options['attributes'] = array(
            'class' => 'create_earlywarning',
            'title' => t('Add an Early Warning for this release'
            )
        );
        $create_earlywarning_url = Url::fromRoute('hzd_earlywarnings.add_early_warnings', array('group' => $group_id), $options
        );

        $create_earlywarning = array(
            '#title' => array(
                '#markup' => $create_icon
            ),
            '#type' => 'link',
            '#url' => $create_earlywarning_url
        );
        $create_warning = \Drupal::service('renderer')->renderRoot($create_earlywarning);

        $earlywarnings_cell = t('@create @view', array( '@create' => $create_warning, '@view' => $view_warning ));

        $elements[] = ['data' => $earlywarnings_cell, 'class' => 'earlywarnings-cell'];
      }

      $download_imgpaths = drupal_get_path('module', 'hzd_release_management') . '/images/document-icon.png';
      $download = "<img src = '/" . $download_imgpaths . "'>";

           
      $link_info = !empty($release_node->field_documentation_link) ? $release_node->field_documentation_link->value : null;
      $link_info_path = !empty($release_node->field_link) ? $release_node->field_link->value : null;
      $link = null;
      if ($link_info) {
        $url = Url::fromRoute(
                        'hzd_release_management.document_page_link', array(
                    'group' => $group_id,
                    'service_id' => $deployed_release_node->field_release_service->value,
                    'release_id' => $deployed_release_node->field_earlywarning_release->value,
                        )
        );
        $doc_link = array(
            '#title' => array(
                '#markup' => $download
            ),
            '#type' => 'link',
            '#url' => $url
        );
        $link = \Drupal::service('renderer')->renderRoot($doc_link);
      } else {
//                $link = t('No Download link available');
      }

      $info_link = '';
      
      if($has_depoyment_info) {
          $deployed_imgpaths = drupal_get_path('module', 'hzd_release_management') . '/images/notification-icon.png';
          $deployed_img = "<img title='Einsatzinformationen anzeigen' class = 'deployed-info-icon' src = '/" . $deployed_imgpaths . "'>";

          $filterData = \Drupal::request()->query;
          $exposedFilterData = $filterData->all();
          unset($exposedFilterData['form_build_id']);
          unset($exposedFilterData['form_id']);

          $url = $deployed_release_node->toUrl('canonical');


          $markupdata = ['#type' => 'container', '#attributes' => ['id' => 'deployed-' . $deployed_release_node->id(), 'class' => ['deployed-popover-wrapper']]];
          $renderer = \Drupal::service('renderer');
          $hover_markup = $renderer->render($markupdata);
          $info_link = Markup::create( '<div class="deployed-tooltip">'.$deployed_img. $hover_markup ."</div>");

          /*
          $info_link = array(
              '#title' => array(
                  '#markup' => $deployed_img
              ),
              '#type' => 'link',
              '#url' => $url
          );
          
          $info_link = \Drupal::service('renderer')->renderRoot($info_link);
          */
      }
      
      if (\Drupal\Component\Utility\UrlHelper::isValid($link_info_path)) {
        $options['attributes'] = array('class' => 'download_img_icon');
        $download_url = Url::fromUri($link_info_path);
        $download_imgpath = drupal_get_path('module', 'hzd_release_management') . '/images/download_icon.png';
        $download = "<img src = '/" . $download_imgpath . "'>";
        $download_link = array('#title' => array('#markup' => $download), '#type' => 'link', '#url' => $download_url);
        $link_path = \Drupal::service('renderer')->renderRoot($download_link);
      } else {
        $link_path = '';
      }

      $release_download = t('<div class="links-wrapper">@info_link <div class="other-links"> @link_path @link</div></div>', array('@link_path' => $link_path, '@link' => $link, '@info_link' => $info_link));
      array_push($elements, $release_download);
      $rows[] = $elements;
    }

//        if (count($rows) == 0) {
//            $output[]['#markup'] = 'No Data to be displayed';
//            return $output;
//        }

    if (isset($group_id)) {
      $state = array('data' => t('State'), 'class' => 'state-hdr');
      $environment = array('data' => t('Environment'), 'class' => 'environment-hdr');
      $service = array('data' => t('Service'), 'class' => 'service-hdr');
      $release = array('data' => t('Release'), 'class' => 'release-hdr');
      $date = array('data' => t('Date Deployed'), 'class' => 'date-hdr');
      $earlywarnings = array('data' => t('Early Warnings'), 'class' => 'early-warnings-hdr');
      $download = array('data' => t('EI/D/L'), 'class' => 'download-hdr');
      $header = array($state, $environment, $service, $release, $date, $earlywarnings, $download);
    } else {
      $state = array('data' => t('State'), 'class' => 'state-hdr');
      $environment = array('data' => t('Environment'), 'class' => 'environment-hdr');
      $service = array('data' => t('Service'), 'class' => 'service-hdr');
      $release = array('data' => t('Release'), 'class' => 'release-hdr');
      $date = array('data' => t('Date Deployed'), 'class' => 'date-hdr');
      $download = array('data' => t('EI/D/L'), 'class' => 'download-hdr');
      $header = array($state, $environment, $service, $release, $date, $download);
    }

//    if (!empty($rows)) {

    $output['deployed'] = array(
        '#theme' => 'table',
        '#rows' => $rows,
        '#header' => $header,
        '#attributes' => ['id' => "sortable", 'class' => "tablesorter releases deployed"],
        '#empty' => t('No data to be displayed'),
    );

    $output['pager'] = array(
        '#type' => 'pager',
        '#quantity' => 5,
        '#prefix' => '<div id="pagination">',
        '#suffix' => '</div>',
        '#exclude_from_print'=>TRUE,
    );

    $output['#attached']['library'] = array(
        'hzd_release_management/hzd_release_management',
//            'downtimes/downtimes'
    );
    $output['#attached']['drupalSettings']['release_management'] = array(
        'group_id' => $group_id,
    );
    return $output;
//    } else {
//      $output[]['#markup'] = t('results not found');
//      return $output;
//    }
  }

  /**
   * Deployed release tab default text.
   */
  static public function deployed_releases_text() {
    $url = Url::fromRoute('hzd_release_management.deployed_releases', ['group' => Zentrale_Release_Manager_Lander]);
    $link = \Drupal::l(t('hier'), $url);

    $output = "<div class = 'deployed-release-text'><p>Hier sehen Sie eine &Uuml;bersicht der von den L&auml;ndern produktiv eingesetzten Releases. &Uuml;ber die unten stehenden Auswahlfelder k&ouml;nnen Sie die Ansicht filtern.</p><p>
Um Releases zu melden, m&uuml;ssen Sie Mitglied der Gruppe ZRML sein. Initial sind dies alle Zentralen Release Manager der L&auml;nder (ZRMKL). Auf Antrag beim <a href=\"mailto:zrmk@hzd.hessen.de\">Zentralen Release Manager KONSENS</a> (ZRMK) k&ouml;nnen Stellvertreter in die Gruppe aufgenommen werden. Eingesetzte Releases melden Sie bitte " . $link . ".</p><p>
F&uuml;r R&uuml;ckfragen steht Ihnen der <a href=\"mailto:zrmk@hzd.hessen.de\">Zentrale Release Manager KONSENS</a> (ZRMK) zur Verf&uuml;gung.</p></div>";
    $build['#markup'] = $output;
    return $build;
  }

  /**
   * Deployed release tab default text.
   */
  static public function deployed_info_text() {
    $url = Url::fromRoute('hzd_release_management.deployed_releases', ['group' => Zentrale_Release_Manager_Lander]);
    $link = \Drupal::l(t('hier'), $url);

    $output = "<div class = 'deployed-info-text'><p>Hier sehen Sie eine &Uuml;bersicht der von den L&auml;ndern produktiv eingesetzten Releases. &Uuml;ber die unten stehenden Auswahlfelder k&ouml;nnen Sie die Ansicht filtern.</p><p>
Um Releases zu melden, m&uuml;ssen Sie Mitglied der Gruppe ZRML sein. Initial sind dies alle Zentralen Release Manager der L&auml;nder (ZRMKL). Auf Antrag beim <a href=\"mailto:zrmk@hzd.hessen.de\">Zentralen Release Manager KONSENS</a> (ZRMK) k&ouml;nnen Stellvertreter in die Gruppe aufgenommen werden. Eingesetzte Releases melden Sie bitte " . $link . ".</p><p>
F&uuml;r R&uuml;ckfragen steht Ihnen der <a href=\"mailto:zrmk@hzd.hessen.de\">Zentrale Release Manager KONSENS</a> (ZRMK) zur Verf&uuml;gung.</p></div>";
    $build['#markup'] = $output;
    return $build;
  }

  /**
   * Display text on releases and inprogress tabs.
   */
  static public function release_info($type = NULL) {
    if ($type == 'progress' && self::RWCommentAccess()) {
      $output = "
<div><p>Ab 12.6.2019 wird der Status \"Zertifizierung ZRMK (DSL-Zert-RMK)\" in den Status \"Warten auf Freigabe f&uuml;r KONSENS durch AnL (ZRMK)\" umbenannt.</p></div>
<div class='menu-filter menu-filter-progress'>
<ul>
<li><b>Legende:</b></li><li><img height=15 src='/modules/custom/hzd_release_management/images/download_icon.png'> Release herunterladen</li>
<li><img height=15 src='/modules/custom/hzd_release_management/images/document-icon.png'> Dokumentation ansehen</li>
<li><img height=15 src='/modules/custom/hzd_release_management/images/icon.png'> Early Warnings ansehen</li>
<li><img height=15 src='/modules/custom/hzd_release_management/images/create-icon.png'> Early Warning erstellen</li>
<li><img height=15 src='/modules/custom/hzd_release_inprogress_comments/images/blue-icon.png'>Kommentare ansehen</li>
<li><img height=15 src='/modules/custom/hzd_release_inprogress_comments/images/create-green-icon.png'>Kommentieren</li>
<li><img class='white-bg' height=15 src='/modules/custom/hzd_release_management/images/e-icon.png'>".t('Deployment Information')."</li>
</ul>
</div>";
    }
    else if($type == 'archived') {
      $output = "<div class='menu-filter'>
                   <ul>
                      <li><b>Legende:</b></li>
                      <li><img height=15 src='/modules/custom/hzd_release_management/images/download_icon.png'> Release herunterladen</li>
                      <li><img height=15 src='/modules/custom/hzd_release_management/images/document-icon.png'> Dokumentation ansehen</li>
                   </ul>
                </div>";
    }
    else {
      $output = "<div class='menu-filter'>
        <ul>
          <li><b>Legende:</b></li>
          <li><img height=15 src='/modules/custom/hzd_release_management/images/download_icon.png'> Release herunterladen</li>
          <li><img height=15 src='/modules/custom/hzd_release_management/images/document-icon.png'> Dokumentation ansehen</li>
          <li><img height=15 src='/modules/custom/hzd_release_management/images/icon.png'> Early Warnings ansehen</li>
          <li><img height=15 src='/modules/custom/hzd_release_management/images/create-icon.png'> Early Warning erstellen</li>
          <li><img class='white-bg' height=15 src='/modules/custom/hzd_release_management/images/e-icon.png'>".t('Deployment Information')."</li>
       </ul>
     </div>";
    }
    $build['#markup'] = $output;
    $build['#exclude_from_print'] = 1;
    return $build;
  }

  /**
   * @return bool
   */
  static public function RWCommentAccess() {
    $current_url_user = \Drupal::routeMatch()->getParameter('user');
    if (is_object($current_url_user)) {
      $user_id = $current_url_user->Id();
    } else {
      $user_id = $current_url_user;
    }

    if ($user_id) {
      $user = User::load($user_id);
    } else {
      $user = \Drupal::currentUser();
    }
    $group = Group::load(RELEASE_MANAGEMENT);
    $groupMember = $group->getMember($user);
    if (array_intersect(['site_administrator', 'administrator'], $user->getRoles())) {
      return TRUE;
    }
    if($groupMember) {
      $roles = $groupMember->getRoles();
      if (!empty($roles) && (in_array($group->bundle() . '-admin', array_keys($roles)))) {
        return TRUE;
      }
      $userData = \Drupal::service('user.data');
      $rw_comments_permission = $userData->get('cust_group', $user->id(), 'rw_comments_permission');
      if ($rw_comments_permission) {
        return $rw_comments_permission;
      } else {
        return FALSE;
      }
    }else {
      return FALSE;
    }
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Session\AccountProxyInterface|User|null
   */
  static public function CurrentUserData() {
    $membershipcontent = \Drupal::routeMatch()->getParameter('group_content');
    $uid = $membershipcontent->getEntity()->id();

    if ($uid) {
      $user = User::load($uid);
    }
    return $user;
  }
  /**
   * Get non production environments list when the state was selected.
   */
  static public function get_environment_options($state = 1) {
    $environment_lists[0] = t('All');
    $environment_lists[1] = t('Produktion');
    $environment_lists[2] = t('Pilot');
    if ($state != 1) {
  
      $entityQuery = \Drupal::entityQuery('node');
      $entityQuery->condition('type','non_production_environment');
      $entityQuery->sort('field_order');
      $entityQuery->condition('field_non_production_state',$state);
      $nodes_nid = $entityQuery->execute();
      $nodeTitles = node_get_title_fast($nodes_nid);
      foreach ($nodeTitles as $nid => $vals) {
        $environment_lists[$nid] = $vals;
      }
      
      /*$non_productions_lists_query = db_select('node_field_data', 'nfd');
      $non_productions_lists_query->Fields('nfd', array('nid', 'title'));
      $non_productions_lists_query->join('node__field_non_production_state', 'nfnps', 'nfd.nid = nfnps.entity_id');
      $non_productions_lists_query->condition('nfnps.field_non_production_state_value', $state, '=');
      $non_productions_lists_query->condition('nfd.type', 'non_production_environment', '=');
      $non_productions_lists_query->orderBy('field_order');
      $non_productions_lists = $non_productions_lists_query->execute()->fetchAll();
      // While ($row = db_fetch_array($non_productions_lists)) {.
      foreach ($non_productions_lists as $row) {
        $environment_lists[$row->nid] = $row->title;
      }*/
    }
    //Environment do not need sort
//    natcasesort($environment_lists);
    return $environment_lists;
  }

  /**
   *
   */
  static public function releases_display_table($type = NULL, $filter_where = NULL, $limit = NULL, $service_release_type = null) {
    $group_id = RELEASE_MANAGEMENT;
    $header = self::hzd_get_release_tab_headers($type);
    $gid = $group_id ? $group_id : RELEASE_MANAGEMENT;
    $filter_value = HzdreleasemanagementStorage::get_release_filters();
    $service_release_type = $filter_value['release_type'];
    if (is_null($service_release_type)) {
      if (isset($group_id) && $group_id != RELEASE_MANAGEMENT) {
        $service_release_type = \Drupal::database()->select('default_release_type', 'ds')
                        ->fields('ds', ['release_type'])
                        ->condition('group_id', $gid)
                        ->execute()->fetchField();
      } else {
        $service_release_type = KONSONS;
      }
    }
    $release_type = get_release_type($type);
    $release_node_ids = self::hzd_release_query($release_type, $gid);
//        pr($release_node_ids);exit;
    $rows = [];
    foreach ($release_node_ids as $release_node_id) {
      $link = null;
      $releases = \Drupal\node\Entity\Node::load($release_node_id);
      if ($releases->field_documentation_link->value) {
        $link = self::hzd_get_release_documentation_link(
                        $releases->field_documentation_link->value, $releases->field_relese_services->target_id, $releases->id());
      } else {
        if ($type == 'progress' || $type == 'released') {
            $link = t('No Download link available');
        } else {
          $link = '';
        }
      }
      if ($releases->field_link->value) {
        $options['attributes'] = array('class' => 'download_img_icon');
        if (\Drupal\Component\Utility\UrlHelper::isValid($releases->field_link->value)) {
          $url = Url::fromUri($releases->field_link->value, $options);
          $download_imgpath = drupal_get_path('module', 'hzd_release_management') . '/images/download_icon.png';
          $download = "<img src = '/" . $download_imgpath . "'>";
          $download_link = array('#title' => array('#markup' => $download), '#type' => 'link', '#url' => $url);
          $link_path = \Drupal::service('renderer')->renderRoot($download_link);
        }
      } else {
        $link_path = '';
      }

      $depolyed_link = '';
      if ($type == 'progress' || $type == 'released') {
        $has_depoyment_info = HzdreleasemanagementStorage::has_deployed_info($releases);
        if ($has_depoyment_info) {
          $deployed_imgpath = drupal_get_path('module', 'hzd_release_management') . '/images/e-icon.png';
          $deployed_img = "<img title='Einsatzinformationen anzeigen' class = 'e-info-icon' src = '/" . $deployed_imgpath . "'>";

          $groupId = RELEASE_MANAGEMENT;
          $options = \Drupal::request()->query->all();
          $options['services'] = $releases->field_relese_services->target_id;
          $options['releases'] = $releases->id();
          unset($options['form_id']);
          unset($options['form_build_id']);
          unset($options['page']);

          $url = Url::fromRoute('hzd_release_management.deployedinfo', array('group' => $groupId), [
              'query' => $options
          ]);
          $download_link = array('#title' => array('#markup' => $deployed_img), '#type' => 'link', '#url' => $url);
          $depolyed_link = \Drupal::service('renderer')->renderRoot($download_link);
        }
      }        
      $link = t('@dep_link @link_path @link', array(
          '@link_path' => $link_path,
          '@link' => $link,
          '@dep_link' => $depolyed_link
              )
      );
      $row = array();
      $service = $releases->get('field_relese_services')->first()->entity;
      $row = array(
          'service' => $service->get('title')->value,
          'release' => $releases->getTitle()
      );

      $row[] = $releases->field_status->value;

      $row[] = $releases->field_date->value != NULL ?
              date('d.m.Y H:i:s', $releases->field_date->value) : '';

      if ($type == 'released' || $type == 'archived' || $type == 'progress') {
        if (isset($group_id)) {
          $early_warnings = self::hzd_release_early_warnings(
            $releases->field_relese_services->target_id, $releases->id(), $type, $service_release_type);

          if ($type == 'progress') {
            if (self::RWCommentAccess()) {
              $earlywarnings_cell = array(
                'data' => $early_warnings,
                'class' => 'earlywarnings-cell inprogress-comment-cell'
              );
            } else {
              $earlywarnings_cell = array(
                'data' => $early_warnings,
                'class' => 'earlywarnings-cell inprogress-no-comment-cell'
              );
            }
          } else {
            $earlywarnings_cell = array(
              'data' => $early_warnings,
              'class' => 'earlywarnings-cell'
            );
          }
          if ($type != 'archived') {
            $row[] = $earlywarnings_cell;
          }
        }
        $row[] = $link;
      }

      if ($type == 'locked') {
        $row[] = $releases->field_release_comments->value;
      }
      $rows[] = $row;
    }

    // pr($rows);exit;
//        if (!empty($rows)) {
    $output['releases'] = array(
        '#theme' => 'table',
        '#rows' => $rows,
        '#header' => $header,
        '#attributes' => ['id' => "sortable", 'class' => ["tablesorter", 'releases', $type]],
        '#empty' => t('No records found'),
    );

    $output['pager'] = array(
        '#type' => 'pager',
        '#quantity' => 5,
        '#prefix' => '<div id="pagination">',
        '#suffix' => '</div>',
        '#exclude_from_print' => 1,
    );
    $output['#attached']['drupalSettings']['release_management'] = array(
        'group_id' => $group_id,
    );
    return $output;
  }

  /**
   *
   */
  static public function hzd_release_query($release_type, $gid) {
    $group_id = get_group_id();
    $filter_value = self::get_release_filters();
    $release_node_ids = \Drupal::entityQuery('node')
            ->condition('type', 'release', '=')
            ->condition('field_release_type', $release_type, '=');

    if (isset($filter_value['release_type'])) {
      $default_type = $filter_value['release_type'];
    } else {
      if (isset($group_id) && $group_id != RELEASE_MANAGEMENT) {
        $default_type = db_query("SELECT release_type FROM "
                . "{default_release_type} WHERE group_id = :gid", array(
            ":gid" => $group_id
                )
                )->fetchField();
        $default_type = isset($filter_value['release_type']) ?
                $filter_value['release_type'] : ($default_type ?
                $default_type : KONSONS);
      } else {
        $default_type = KONSONS;
      }
    }
    if ($filter_value['services']) {
      // $filter_where .= " and field_relese_services_nid = ". $service;.
      $release_node_ids->condition('field_relese_services', $filter_value['services'], '=');
    } else {
      $group_release_view_service_id_query = \Drupal::database()
              ->select('group_releases_view', 'grv');
      $group_release_view_service_id_query->fields('grv', array('service_id'));
      $group_release_view_service_id_query->condition('group_id', $group_id, '=');
      $group_release_view_service = $group_release_view_service_id_query
                      ->execute()->fetchCol();

      if (!empty($group_release_view_service)) {
        $services = \Drupal::entityQuery('node')
                ->condition('type', 'services', '=')
                ->condition('release_type', $default_type, '=')
                ->condition('nid', $group_release_view_service, 'IN')
                ->execute();
      }
      if (empty($services)) {
        $services = [-1];
      }
      if (isset($services) && !empty($services)) {
        $release_node_ids->condition('field_relese_services', $services, 'IN');
      }
    }
//      if ($filter_value['r_type']) {
//        $release_node_ids->condition('field_release_type', 
//            $filter_value['r_type'], '=');
//      }

    if ($filter_value['releases']) {
      $release_node_ids->condition('nid', $filter_value['releases'], '=');
    }
    if (!empty($filter_value['filter_startdate']) && $filter_value['filter_enddate'] == '') {
      $release_node_ids->condition('field_date', $filter_value['filter_startdate'], '>');
      // $filter_where .= " and field_date_value > ". $start_date;.
    }
    if ($filter_value['filter_startdate'] && preg_match ("/^([0-9]{2}).([0-9]{2}).([0-9]{4})$/", $filter_value['filter_startdate'])) {
      $startDate = DateTimePlus::createFromFormat('d.m.Y|', $filter_value['filter_startdate'], null, ['validate_format' => FALSE])->getTimestamp();
      $release_node_ids->condition('field_date', $startDate, '>=');
    }
    if ($filter_value['filter_enddate'] && preg_match ("/^([0-9]{2}).([0-9]{2}).([0-9]{4})$/", $filter_value['filter_enddate'])) {
      $endDate = DateTimePlus::createFromFormat('d.m.Y|', $filter_value['filter_enddate'], null, ['validate_format' => FALSE])->getTimestamp() + 86399;
      $release_node_ids->condition('field_date', $endDate, '<=');
      /* $release_node_ids->condition('field_date',
        array($filter_value['filter_startdate'],
        $filter_value['filter_enddate']), 'BETWEEN'); */
    }
    
    // beacause of false requirement this is added, but not necessary anymore @sandeep 23-08-2017
/*    $deployedReleases = \Drupal::database()->select('node__field_earlywarning_release', 'nd')
            ->condition('bundle', 'deployed_releases')
            ->fields('nd', ['field_earlywarning_release_value'])
            ->execute()
            ->fetchCol();
//    pr($deployedReleases);exit;
    $release_node_ids->condition('nid', (array) $deployedReleases, 'NOT IN');*/
    $release_node_ids->sort('field_date', 'DESC');
    $release_node_ids->condition('status', 1);
    if ($filter_value['limit'] == 'all') {
      $result = $release_node_ids->execute();
    } else {
      $page_limit = (isset($filter_value['limit']) ? $filter_value['limit'] : DISPLAY_LIMIT);
      $result = $release_node_ids->pager($page_limit)->execute();
    }
    return $result;
  }

  static function getSelectedServicesForReleases($groupId, $defaultTye) {
//    $defaultTye = \Drupal::database()->query("SELECT release_type FROM {default_release_type} WHERE group_id = :gid", array(":gid" => $groupId))->fetchField();
    $services_obj = \Drupal::database()->query("SELECT n.title, n.nid
                     FROM {node_field_data} n, {group_releases_view} grv, {node__release_type} nrt 
                     WHERE n.nid = grv.service_id and n.nid = nrt.entity_id and grv.group_id = :gid and nrt.release_type_target_id = :tid 
                     ORDER BY n.title asc", array(":gid" => $groupId, ":tid" => $defaultTye))->fetchAll();

    foreach ($services_obj as $services_data) {
      $services[$services_data->nid] = $services_data->nid;
    }
    if (empty($services)) {
      $services = [-1];
    }
    return $services;
  }

  /**
   *
   */
  static public function hzd_get_release_tab_headers($type) {
    $group_id = get_group_id();
    if ($type == 'released' || $type == 'archived') {
      $header = array(t('Service'), t('Release'), t('Status'), t('Date'));
      if (isset($group_id) && $type != 'archived') {
        $header[] = t('Early Warnings');
      }
      if ($type == 'archived') {
        $header[] = t('D/L');
      }
      else {
        $header[] = t('EI/D/L');
      }
      
    }
    if ($type == 'progress' || $type == 'locked' || $type == 'in_progress') {
      $header = array(t('Service'), t('Release'), t('Status'), t('Date'));
      if ($type == 'progress') {
        if (isset($group_id)) {
          $header[] = t('Early Warnings');
        }
        $header[] = t('EI/D/L');
      }
      if ($type == 'locked') {
        $header[] = t('Comment');
      }
    }
    return $header;
  }

  /**
   *
   */
  static public function hzd_get_release_documentation_link($doc_link, $service_id, $release_id) {
    $group_id = get_group_id();

    $download_imgpaths = drupal_get_path('module', 'hzd_release_management') . '/images/document-icon.png';
    $download = '<img src = "/' . $download_imgpaths . '">';

    $secure_downloads = array_search('secure-downloads', explode('/', $doc_link));
    if ($secure_downloads) {
      $url = Url::fromUserInput('/group/' . $group_id . '/releases/documentation/' . $service_id . '/' . $release_id);
    } else {
      $url = Url::fromUserInput('/group/' . $group_id . '/releases/documentation/' . $service_id . '/' . $release_id);
    }

    $docu_link = array('#title' => array('#markup' => $download), '#type' => 'link', '#url' => $url);
    return \Drupal::service('renderer')->renderRoot($docu_link);
  }

  /**
   *
   */
  static public function hzd_release_early_warnings($service_id, $release_id, $type, $tid) {
    $group_id = get_group_id();

    // Early Warnigs count for specific service and release.
    $query = db_select('node_field_data', 'n');
    $query->join('node__field_earlywarning_release', 'nfer', 'n.nid = nfer.entity_id');
    $query->join('node__field_release_service', 'nfrs', 'n.nid = nfrs.entity_id');
    $query->condition('n.type', 'early_warnings', '=')
      ->condition('nfer.field_earlywarning_release_value', $release_id, '=')
      ->condition('nfrs.field_release_service_value', $service_id, '=');
    $earlywarnings_count = $query->countQuery()->execute()->fetchField();

    if ($type == 'progress' && self::RWCommentAccess()) {
      $el_class = 'create_earlywarning earlywarning-inprogress';
    }else {
      $el_class = 'create_earlywarning';
    }

    if ($earlywarnings_count > 0) {
      $warningclass = ($earlywarnings_count >= 10 ? 'warningcount_second' : 'warningcount');
      $view_options['query'] = array(
        'services' => $service_id,
        'releases' => $release_id,
        'type' => $type,
        'release_type' => $tid
      );
      $view_options['attributes'] = array(
        'class' => 'view-earlywarning',
        'title' => t('Read Early Warnings for this release'));
      $view_earlywarning_url = Url::fromUserInput('/group/' . $group_id . '/view-early-warnings', $view_options);
      $view_earlywarning = array(
        '#title' => array('#markup' => "<span class = '" . $warningclass . "'>" . $earlywarnings_count . "</span> "),
        '#type' => 'link',
        '#url' => $view_earlywarning_url,
      );
      $view_warning = \Drupal::service('renderer')->renderRoot($view_earlywarning);
    } else {
      if ($type == 'progress' && self::RWCommentAccess()) {
        $view_warning = Markup::create('<a><span class="nonewarningcount"></span></a>');
      }else {
        $view_warning = t('<span class="no-warnigs"></span>');
      }
    }

    // Early Warning create icon.
    $create_icon_path = drupal_get_path('module', 'hzd_release_management') . '/images/create-icon.png';
    $create_icon = '<img height=15 src = "/' . $create_icon_path . '">';
    // Redirection array after creation of early warnings.
    $redirect = array('released' => 'releases', 'archived' => 'archived', 'progress' => 'releases/in_progress', 'locked' => 'releases/locked');
    $options['query']['destination'] = 'group/' . $group_id . '/' . $redirect[$type];
    $options['query'][] = array(
      'services' => $service_id,
      'releases' => $release_id,
      'type' => $type,
      'release_type' => $tid
    );
    $options['attributes'] = array('class' => $el_class, 'title' => t('Add an Early Warning for this release'));
    $create_earlywarning_url = Url::fromRoute('hzd_earlywarnings.add_early_warnings', ['group' => $group_id], $options);
    $create_earlywarning = array('#title' => array('#markup' => $create_icon), '#type' => 'link', '#url' => $create_earlywarning_url);
    $create_warning = \Drupal::service('renderer')->renderRoot($create_earlywarning);

    if ($type == 'progress' && self::RWCommentAccess()) {
      // Comment count for specific service and release.
      $cmt_query = db_select('node_field_data', 'n');
      $cmt_query->join('node__field_earlywarning_release', 'nfer', 'n.nid = nfer.entity_id');
      $cmt_query->join('node__field_release_service', 'nfrs', 'n.nid = nfrs.entity_id');
      $cmt_query->condition('n.type', 'release_comments', '=')
        ->condition('nfer.field_earlywarning_release_value', $release_id, '=')
        ->condition('nfrs.field_release_service_value', $service_id, '=');
      $cmt_count = $cmt_query->countQuery()->execute()->fetchField();

      if ($cmt_count > 0) {
        $cmtclass = ($cmt_count >= 10 ? 'warningcount_second' : 'commentcount');
        $cmt_view_options['query'] = array(
          'services' => $service_id,
          'releases' => $release_id,
          'type' => $type,
          'release_type' => $tid
        );
        $cmt_view_options['attributes'] = array(
          'class' => 'view-comment',
          'title' => t('Read Early Warnings for this release'));
        $view_cmt_url = Url::fromUserInput('/group/' . $group_id . '/view-release-comments', $cmt_view_options);
        $view_cmt = array(
          '#title' => array('#markup' => "<span class = '" . $cmtclass . "'>" . $cmt_count . "</span> "),
          '#type' => 'link',
          '#url' => $view_cmt_url,
        );
        $view_cmt = \Drupal::service('renderer')->renderRoot($view_cmt);
      } else {
        $view_cmt = Markup::create('<a><span class="nonecommentcount"></span></a>');
      }

      // Comments create icon.
      $cmt_create_icon_path = drupal_get_path('module', 'hzd_release_inprogress_comments') . '/images/create-green-icon.png';
      $cmt_create_icon = '<img height=15 src = "/' . $cmt_create_icon_path . '">';
      $cmt_options['query']['destination'] = 'group/' . $group_id . '/releases/in_progress';
      $cmt_options['query'][] = array(
        'services' => $service_id,
        'releases' => $release_id,
        'type' => $type,
        'release_type' => $tid
      );
      $cmt_options['attributes'] = array('class' => 'create_comment', 'title' => t('Add an Comments for this release'));
      $create_cmt_url = Url::fromRoute('hzd_release_inprogress_comments.add_release_comments', ['group' => $group_id], $cmt_options);
      $create_cmt = array('#title' => array('#markup' => $cmt_create_icon), '#type' => 'link', '#url' => $create_cmt_url);
      $create_cmt_warning = \Drupal::service('renderer')->renderRoot($create_cmt);
      $release_earlywarning = t('@view @create @create_cmt @view_cmt', array('@create' => $create_warning, '@view' => $view_warning, '@view_cmt' => $view_cmt, '@create_cmt' => $create_cmt_warning));
    } else {
      $release_earlywarning = t('@create @view', array('@create' => $create_warning, '@view' => $view_warning));
    }

    return $release_earlywarning;
  }

  /**
   *
   */
  static public function delete_group_release_view() {
    // $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }

    db_delete('group_releases_view')->condition('group_id', $group_id, '=')
            ->execute();
  }

  /**
   *
   */
  static public function get_default_release_services_current_session() {
    // Getting the default Services
    // $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }

    $query = db_select('group_releases_view', 'grv');
    $query->Fields('grv', array('service_id'));
    $query->condition('group_id', $group_id, '=');
    $result = $query->execute()->fetchAll();
    return $result;
  }

  /**
   *
   */
  static public function get_release_type_current_session() {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }

    $release_type_query = db_select('default_release_type', 'drt');
    $release_type_query->Fields('drt', array('release_type'));
    $release_type_query->condition('drt.group_id', $group_id, '=');
    $release_type = $release_type_query->execute()->fetchField();
    //  dpm($release_type);
    return $release_type;
  }

  /**
   *
   */
  static public function insert_group_release_view($default_release_type, $selected_services) {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }

    $release_type_query = db_select('default_release_type', 'drt');
    $release_type_query->Fields('drt', array('release_type'));
    $release_type_query->condition('drt.group_id', $group_id, '=');
    $release_type = $release_type_query->execute()->fetchField();
    if ($release_type) {
      db_update('default_release_type')->fields(array('release_type' => $default_release_type))->condition('group_id', $group_id, '=')->execute();
    } else {

      db_insert('default_release_type')->fields(array('group_id' => $group_id, 'release_type' => $default_release_type))->execute();
    }
    // $sql = 'insert into {group_releases_view} (group_id, service_id) values (%d, %d)';.
    $counter = 0;
    if (sizeof($selected_services) > 0) {
      foreach ($selected_services as $service) {
        if ($service != 0) {
          $counter++;
          // db_query($sql, $group_id, $service);.
          db_insert('group_releases_view')->fields(array(
              'group_id' => $group_id,
              'service_id' => $service,
          ))->execute();
        }
      }
    }
    // exit;.
    return $counter;
  }

  static public function get_release_filters() {
    $parameters = array();
    $request = \Drupal::request()->query;
    $parameters['release_type'] = $request->get('release_type');
    $parameters['services'] = $request->get('services');
    $parameters['releases'] = $request->get('releases');
    $parameters['filter_startdate'] = $request->get('filter_startdate');
    $parameters['filter_enddate'] = $request->get('filter_enddate');
    $parameters['states'] = $request->get('states');
    $parameters['environment_type'] = $request->get('environment_type');
    $parameters['deployed_type'] = $request->get('deployed_type');
    $parameters['r_type'] = $request->get('r_type');
    $parameters['limit'] = $request->get('limit');
    return $parameters;
  }



  /**
   *
   * @filter_options:filter options for filtering releases according to selected filters
   * @limit:page limit
   * @returns: table display of deployed releases
   */
  static public function deployed_info_displaytable($service_release_type = KONSONS) {
    $deployed_releases_node_ids = \Drupal::entityQuery('node')
        ->condition('field_previous_release', NULL, 'IS NOT NULL')
        ->condition('type', 'deployed_releases', '=');

    $filter_value = HzdreleasemanagementStorage::get_release_filters();
    $type = 'deployed_releases';
    $group_id = get_group_id();
    
    if (isset($filter_value['release_type'])) {
      $default_type = $filter_value['release_type'];
    }
    else {
      if ($group_id != RELEASE_MANAGEMENT) {
          $default_type = db_query("SELECT release_type "
          . "FROM {default_release_type} "
          . "WHERE group_id = :gid", array(":gid" => $group_id))->fetchField();
          $default_type = $default_type ? $default_type : KONSONS;
      }
      else {
          $default_type = KONSONS;
      }
    }
    
    if ($filter_value['services']) {
        $deployed_releases_node_ids->condition('field_release_service', $filter_value['services'], '=');
    }
    else {
      $services_obj = db_query("SELECT n.title, n.nid
                     FROM {node_field_data} n, {group_releases_view} grv, 
                     {node__release_type} nrt 
                     WHERE n.nid = grv.service_id and n.nid = nrt.entity_id 
                     and grv.group_id = :gid and nrt.release_type_target_id = :tid 
                     ORDER BY n.title asc", array(
                         ":gid" => $group_id,
                         ":tid" => $default_type
                     )
      )->fetchAll();
      $services = [];
      if (empty($services_obj)) {
          $services = [-1];
      }
      foreach ($services_obj as $services_data) {
          $services[] = $services_data->nid;
      }
      $deployed_releases_node_ids->condition('field_release_service', $services, 'IN');
    }

    if (isset($filter_value['states']) && $filter_value['states'] != 1) {
      $deployed_releases_node_ids->condition('field_user_state', $filter_value['states'], '=');
    }
    if ($filter_value['releases']) {
      $deployed_releases_node_ids->condition('field_earlywarning_release', $filter_value['releases'], '=');
    }

    if ($filter_value['environment_type']) {
      $deployed_releases_node_ids->condition('field_environment', $filter_value['environment_type'], '=');
    }
    $deployed_releases_node_ids->sort('field_date_deployed', 'DESC');


    $gid = $group_id ? $group_id : RELEASE_MANAGEMENT;
    if ($filter_value['limit'] != 'all') {
      $page_limit = (isset($filter_value['limit']) ? $filter_value['limit'] : DISPLAY_LIMIT);
      $result = $deployed_releases_node_ids->pager($page_limit)->execute();
    }
    else {
      $result = $deployed_releases_node_ids->execute();
    }
    $rows = [];

    $current_uri = \Drupal::request()->getRequestUri();

    foreach ($result as $deployed_releases_node_id) {
      $deployed_release_node = \Drupal\node\Entity\Node::load($deployed_releases_node_id);
      $state = HzdreleasemanagementHelper::getStateABBR($deployed_release_node->field_user_state->value);
      $service = HzdreleasemanagementHelper::getNodedetails($deployed_release_node->field_release_service->value, 'title');
      $release = HzdreleasemanagementHelper::getNodedetails($deployed_release_node->field_earlywarning_release->value, 'title');
      $previous_release_title = HzdreleasemanagementHelper::getNodedetails($deployed_release_node->field_previous_release->value, 'title');
      if(!$previous_release_title) {
        $previous_release_title = 'Ersteinsatz';
      }
      
      if ($deployed_release_node->field_environment->value == 1) {
          $environment_val = t('Produktion');
      }
      else if ($deployed_release_node->field_environment->value == 2) {
          $environment_val = t('Pilot');
      }
      else {
        $environment_val = \Drupal\node\Entity\Node::load($deployed_release_node->field_environment->value)->getTitle();           
      }
      $installation_duration = $deployed_release_node->field_installation_duration->value;
      $automated_depoyment = $deployed_release_node->field_automated_deployment->value;
      $abnormalities = $deployed_release_node->field_abnormality_description->value;
      $deployed_date = date('d.m.Y', strtotime($deployed_release_node->field_date_deployed->value));
      
      $elements = array(
          'state' => $state,
          'release' => $release,
          'deployed_date' => $deployed_date,
          'environment' => $environment_val,
          'previous_release' => $previous_release_title,
          'installation_duration' => $installation_duration,
          'automated_deployment' => $automated_depoyment?t('Yes'):t('No'),
          'abnormalities' => $abnormalities,
      );

      if (isset($filter_value['states']) && $filter_value['states'] > 1) {
        if ($filter_value['states'] == $deployed_release_node->field_user_state->value) {
          $elements = array(
            'state' => $state,
            'release' => $release,
            'deployed_date' => $deployed_date,
            'environment' => $environment_val,
            'previous_release' => $previous_release_title,
            'installation_duration' => $installation_duration,
            'automated_deployment' => $automated_depoyment?t('Yes'):t('No'),
            'abnormalities' => $abnormalities,
          );
        }
        else {
          $elements = array();
        }
      }
      $rows[] = $elements;
    }

    $state = array('data' => t('State'), 'class' => 'state-hdr');
    $release = array('data' => t('Release'), 'class' => 'release-hdr');
    $date = array('data' => t('Date Deployed'), 'class' => 'date-hdr');
    $environment = array('data' => t('Environment'), 'class' => 'environment-hdr');
    $previous_release = array('data' => t('Previous Release'), 'class' => 'previous-release-hdr');
    $installation_duration = array('data' => t('ID'), 'class' => 'instattion-duration-hdr');
    $automated_depoyment = array('data' => t('AD'), 'class' => 'automated-deployment-hdr');
    $abnormalities = array('data' => t('Abnormalities'), 'class' => 'abnormalities-hdr');
    
    $header = array($state, $release, $date, $environment, $previous_release, $installation_duration, $automated_depoyment, $abnormalities);

    $output['deployed'] = array(
        '#theme' => 'table',
        '#rows' => $rows,
        '#header' => $header,
        '#attributes' => ['id' => "deployed-info-sortable", 'class' => "tablesorter deployedinfo"],
        '#empty' => t('No data to be displayed'),
    );

    $output['pager'] = array(
        '#type' => 'pager',
        '#quantity' => 5,
        '#prefix' => '<div id="pagination">',
        '#suffix' => '</div>',
        '#exclude_from_print'=>TRUE,
    );

    $output['#attached']['library'] = array(
        'hzd_release_management/hzd_release_management',
    );
    $output['#attached']['drupalSettings']['release_management'] = array(
        'group_id' => $group_id,
    );
    return $output;
  }

  /**
   * Display text on releases and inprogress tabs.
   */
  static public function deployed_info_legend($type = NULL) {
    if ($type == 'deployed') {
        $output = "<div class='menu-filter'>
                   <ul>
                      <li><b>Legende:</b></li>
<li><img class='white-bg' height=15 src='/modules/custom/hzd_release_management/images/notification-icon.png'>".t('Deployment information')."</li>
                   </ul>
                </div>";
    }
    else {
        $output = "<div class='menu-filter'>
                 <ul>
                   <li><b>Legende:</b></li>
                   <li><b>".t('ID')." :</b> ".t('Installation Duration')."</li>
                   <li><b>".t('AD')." :</b> ".t('Automated Deployment')."</li>
                 </ul>
               </div>";
    }
    $build['#markup'] = $output;
    $build['#exclude_from_print'] = 1;
    return $build;
  }

}

