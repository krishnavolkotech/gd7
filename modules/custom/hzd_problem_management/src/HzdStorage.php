<?php

namespace Drupal\problem_management;

use Drupal\node\Entity\Node;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Database\Query\Condition;
use Drupal\group\Entity\GroupContent;
use Drupal\hzd_services\HzdservicesHelper;

class HzdStorage {

    protected $tempStore;

    // Pass the dependency to the object constructor

    const DISPLAY_LIMIT = 20;
    const PROBLEM_MANAGEMENT = 31;

    public function __construct(PrivateTempStoreFactory $temp_store_factory) {
        // For "mymodule_name," any unique namespace will do
        $this->tempStore = $temp_store_factory->get('problem_management');
    }

    /*
     * Inserts the status of the import file on cron run
     */

    static function insert_import_status($status, $msg) {
        // Populate the node access table.
        db_insert('problem_import_history')
                ->fields(array(
                    'problem_date' => time(),
                    'import_status' => $status,
                    'error_message' => $msg
                ))
                ->execute();
        // $sql = "insert into {problem_import_history} (problem_date, import_status, error_message) values (%d, '%s', '%s') "; 
        // db_query($sql, time(), $status, $msg);
    }

    /*
     * function for saving problem node
     */

    static public function saving_problem_node($values) {
        if (($values['title'] == '') || ($values['status'] == '')) {
            $message = t('Required Field Values Are Missing');
            \Drupal::logger('problem_management')->error($message);
            $mail = \Drupal::config('problem_management.settings')->get('import_mail');
            $subject = 'Error while import';
            $body = t("There is an issue while importing of the file. Required field values are missing.");
            HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
            $status = t('Error');
            $msg = t('Required Field Values Are Missing');
            HzdStorage::insert_import_status($status, $msg);
            return FALSE;
        }
        if ((is_int($values['sno'])) ) {
            $message = t(' sno must be integer');
            \Drupal::logger('problem_management')->error($message);
            $mail = \Drupal::config('problem_management.settings')->get('import_mail');
            $subject = 'Error while import';
            $body = t("There is an issue while importing of the file. sno must be integer.");
            HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
            $status = t('Error');
            $msg = t('sno must be integer.');
            HzdStorage::insert_import_status($status, $msg);
            return FALSE;
        }
        $query = \Drupal::database()->select('groups_field_data', 'gfd');
        $query->Fields('gfd', array('id'));
        $query->condition('label', 'problem management', '=');
        $group_id = $query->execute()->fetchCol();

        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->join('node__field_s_no', 'nfsn', 'n.nid = nfsn.entity_id');
        $query->Fields('n', array('nid', 'vid', 'created'));
        $query->condition('field_s_no_value', $values['sno'], '=');
        $node_infos = $query->execute()->fetchAll();

        foreach ($node_infos as $node_info) {
            $nid = $node_info->nid;
            $vid = $node_info->vid;
            $created = $node_info->created;
        }
        //the erofnet date field conversion   
        $replace = array('/' => '.', '-' => '.');
        $formatted_date = strtr($values['eroffnet'], $replace);

        $date_time = explode(" ", $formatted_date);
        $date_format = explode(".", $date_time[0]);
        $time_format = explode(":", $date_time[1]);

        if ($date_format[1] && $date_format[0] && $date_format[2]) {
            $date = mktime((int) $time_format[0], (int) $time_format[1], (int) $time_format[2], (int) $date_format[1], (int) $date_format[0], (int) $date_format[2]);
        }
        $eroffnet = ( $date ? $date : time());
        // Generate notifications for updated problems.

        if (isset($nid)) {
            unset($values['sno']);
            $exist_node = node_load($nid);
            $existing_node_vals = array();

            $existing_node_vals['status'] = $exist_node->field_problem_status->value;
            $existing_node_vals['service'] = $exist_node->field_services->target_id;
            $existing_node_vals['function'] = $exist_node->field_function->value;
            $existing_node_vals['release'] = $exist_node->field_release->value;
            $existing_node_vals['title'] = $exist_node->getTitle();
            $existing_node_vals['body'] = $exist_node->body->value;
            $existing_node_vals['diagnose'] = $exist_node->field_diagnose->value;
            $existing_node_vals['solution'] = $exist_node->field_solution->value;
            $existing_node_vals['workaround'] = $exist_node->field_work_around->value;
            $existing_node_vals['version'] = $exist_node->field_version->value;
            $existing_node_vals['priority'] = $exist_node->field_priority->value;
            $existing_node_vals['taskforce'] = $exist_node->field_task_force->value;
            $existing_node_vals['comment'] = $exist_node->field_comments->value;
            $existing_node_vals['processing'] = $exist_node->field_processing->value;
            $existing_node_vals['attachment'] = $exist_node->field_attachment->value;
            $existing_node_vals['eroffnet'] = $exist_node->field_eroffnet->value;
            $existing_node_vals['timezone'] = 'Europe/Berlin';
            $existing_node_vals['closed'] = $exist_node->field_closed->value;
            /**
              $existing_node_vals['problem_eroffnet'] = $exist_node->field_problem_eroffnet->value;
              // $existing_node_vals['problem_status'] = $exist_node->field_problem_status->value;
              $existing_node_vals['ticketstore_count'] = $exist_node->field_ticketstore_count->value;
              $existing_node_vals['ticketstore_link'] = $exist_node->field_ticketstore_link->value;
             */
            if (count(array_diff($existing_node_vals, $values)) != 0) {
                // $node_array['status'] = 1;
                $problem_node = node_load($nid);
                $problem_node->setTitle(\Drupal\Component\Utility\Html::escape($values['title']));
                $problem_node->set('status', 1);
                $problem_node->set('created', $created ? $created : time());
                $problem_node->set('body', $values['body']);
                $problem_node->set('comment', array
                    (
                    'status' => 2,
                    'cid' => 0,
                    'last_comment_timestamp' => 0,
                    'last_comment_name' => '',
                    'last_comment_uid' => 0,
                    'comment_count' => 0,
                        )
                );
                $problem_node->set('field_attachment', \Drupal\Component\Utility\Html::escape($values['attachment']));
                $problem_node->set('field_closed', $values['closed']);
                $problem_node->set('field_comments', array
                    (
                    'value' => $values['comment'],
                    'format' => 'basic_html',
                        )
                );
                $problem_node->set('field_services', array(
                    'target_id' => $values['service'],
                        )
                );
                $problem_node->set('field_diagnose', $values['diagnose']);
                $problem_node->set('field_eroffnet', $values['eroffnet']);
                $problem_node->set('field_function', $values['function']);
                $problem_node->set('field_priority', $values['priority']);
                $problem_node->set('field_problem_eroffnet', $eroffnet);
                $problem_node->set('field_problem_status', $values['status']);
                $problem_node->set('field_processing', $values['processing']);
                $problem_node->set('field_release', $values['release']);
//	$problem_node->set('field_sdcallid', $values['sdcallid']);
                $problem_node->set('field_solution', array
                    (
                    'value' => $values['solution'],
                    'format' => 'basic_html'
                ));
                // $problem_node->set('field_s_no', $values['sno']);
                // $problem_node->set('field_release', $values['release']);
                $problem_node->set('field_task_force', array($values['taskforce']));
                // $problem_node->set('field_release', $values['release']);
                // $problem_node->set('field_ticketstore_count', $values['ticketstore_count']);
                // $problem_node->set('field_release', $values['release']);
                // $problem_node->set('field_ticketstore_link', $values['ticketstore_link']);
                // $problem_node->set('field_timezone', $values['timezone']);
                $problem_node->set('field_version', $values['version']);
                $problem_node->set('field_work_around', array
                    (
                    'value' => $values['workaround'],
                    'format' => 'basic_html',
                ));
                $problem_node->save();
                return TRUE;
            }
        } else {

            $node_array = array
                (
                'nid' => array(''),
                'vid' => array(''),
                'type' => 'problem',
                'title' => \Drupal\Component\Utility\Html::escape($values['title']),
                'uid' => 1,
                'status' => 1,
                'created' => time(),
                'body' => array(
                    'summary' => '',
                    'value' => $values['body'],
                    'format' => 'basic_html',
                ),
                'comment' => array(
                    'status' => 2,
                    'cid' => 0,
                    'last_comment_timestamp' => 0,
                    'last_comment_name' => '',
                    'last_comment_uid' => 0,
                    'comment_count' => 0,
                ),
                'field_attachment' => $values['attachment'],
                'field_closed' => $values['closed'],
                'field_comments' => array(
                    'value' => $values['comment'],
                    'format' => 'basic_html',
                ),
                'field_diagnose' => $values['diagnose'],
                'field_eroffnet' => $values['eroffnet'],
                'field_function' => array(
                    'value' => $values['function'],
                ),
                'field_priority' => $values['priority'],
                'field_problem_eroffnet' => $eroffnet,
                'field_problem_status' => \Drupal\Component\Utility\Html::escape($values['status']),
                'field_processing' => $values['processing'],
                'field_release' => $values['release'],
                // 'field_sdcallid' => $values['sdcallid'],
                'field_services' => array(
                    'target_id' => $values['service'],
                ),
                // 'field_timezone' => $values['timezone'],
                'field_solution' => array(
                    'value' => $values['solution'],
                    'format' => 'basic_html',
                ),
                'field_s_no' => $values['sno'],
                'field_task_force' => $values['taskforce'],
                // 'field_ticketstore_count' => $values['ticketstore_count'],
                // 'field_ticketstore_link' => $values['ticketstore_link'],
                'field_version' => $values['version'],
                'field_work_around' => array(
                    'value' => $values['workaround'],
                    //     'timezone' =>  $values['timezone'],
                    'format' => 'basic_html',
                ),
                'status' => 1,
            );

            $node = Node::create($node_array);
            $node->save();
            $nid = $node->id();

            if ($nid) {
                // $group_id = \Drupal::routeMatch()->getParameter('group');
                $group = \Drupal\group\Entity\Group::load($group_id['0']);
                //Adding node to group
                // dpm($group->getGroupType());

                $group_content = GroupContent::create([
                            'type' => $group->getGroupType()->getContentPlugin('group_node:problem')->getContentTypeConfigId(),
                            'gid' => $group_id,
                            'entity_id' => $node->id(),
                            'request_status' => 1,
                            'label' => $values['title'],
                            'uid' => 1,
                ]);
                $group_content->save();
                // dpm($group_content->id());       
            }
            return TRUE;
        }
        return FALSE;
    }

    static function insert_group_problems_view($group_id, $selected_services) {

        // $sql = 'insert into {group_problems_view} (group_id, service_id) values (%d, %d)';
        $counter = 0;

        // $tempstore = \Drupal::service('user.private_tempstore')->get('problem_management');
        // $group_id = $tempstore->get('Group_id');
        $group = \Drupal::routeMatch()->getParameter('group');
        if (is_object($group)) {
            $group_id = $group->id();
        } else {
            $group_id = $group;
        }

        if (!empty($selected_services)) {

            foreach ($selected_services as $service) {

                if (!empty($service))
                    $counter++;
                $query = \Drupal::database()
                        ->insert('group_problems_view')
                        ->fields(array('group_id' => $group_id, 'service_id' => $service))
                        ->execute();
                // db_query($sql, $_SESSION['Group_id'], $service);
            }
        }
        return $counter;
    }

    static function import_history_display_table($limit = NULL) {
        $build = array();


        $query = \Drupal::database()->select('problem_import_history', 'pmh');
        $query->Fields('pmh', array('problem_date', 'import_status', 'error_message'));
        // $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender');

        if ($limit != 'all') {
            $page_limit = ($limit ? $limit : 20);
            $query->orderBy('id');
            $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($page_limit);
            $result = $pager->execute();
        } else {
            $result = $query->execute()->fetchAll();
        }

        foreach ($result as $row) {
            $elements = array(
                'date' => date('d-m-Y', $row->problem_date),
                'time' => date('H:i', $row->problem_date),
                'import_status' => $row->import_status,
                'error' => $row->error_message,
            );
            $rows[] = $elements;
        }

        $header = array(
            '0' => array('data' => t('Date'), 'class' => 'import_date'),
            '1' => array('data' => t('Time'), 'class' => 'time'),
            '2' => array('data' => t('Import Status'), 'class' => 'import_status'),
            '3' => array('data' => t('Error Message'), 'class' => 'error_message'),
        );

        $build['history_table'] = array(
            '#theme' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#empty' => t('No Data Created Yet'),
            '#attributes' => array(
                'id' => 'sortable_new',
                '#class' => 'tablesorter'
            ),
        );
        $build['pager'] = array(
            '#type' => 'pager',
            '#prefix' => '<div id="pagination">',
            '#suffix' => '</div>',
        );
        // echo '<pre>';  print_r($build); exit;
        return $build;
    }

    static function build_ahah_query($form_state) {
        $group = \Drupal::routeMatch()->getParameter('group');
        if (is_object($group)) {
            $group_id = $group->id();
        } else {
            $group_id = $group;
        }

        $sql_where = array();
        if ($form_state['values']['service']) {
            $service = $form_state['values']['service'];
            $sql_where[] = array('and' => array(
                    'field' => 'nfs.field_services_target_id',
                    'value' => $service,
                    'operator' => '=',
                )
            );
        }

        if ($form_state['values']['string']) {
            $text = $form_state['values']['string'];
            if ($text != t('Search Title, Description, cause, Workaround, solution')) {

                $query = \Drupal::database()->select('node_field_data', 'nfd');
                $query->Fields('nfd', array('nid'));
                $sql_where[] = array('or' =>
                    array(
                        array(
                            'field' => 'nfsn.field_s_no_value',
                            'value' => $text,
                            'operator' => '=',
                        ),
                        array(
                            'field' => 'nfd.title',
                            'value' => '%' . $text . '%',
                            'operator' => 'like',
                        ),
                        array(
                            'field' => 'nb.body_value',
                            'value' => '%' . $text . '%',
                            'operator' => 'like',
                        ),
                        array(
                            'field' => 'nfwa.field_work_around_value',
                            'value' => '%' . $text . '%',
                            'operator' => 'like',
                        ),
                    )
                );

                $query->leftJoin('node_revision', 'nr', 'nfd.nid = nr.nid');
                $query->leftJoin('node__body', 'nb', 'nfd.nid = nb.entity_id');
                $query->leftJoin('node__field_s_no', 'nfsn', 'nb.entity_id = nfsn.entity_id');
                $query->leftJoin('node__field_problem_status', 'nfps', 'nfsn.entity_id = nfps.entity_id');
                $query->leftJoin('node__field_services', 'nfs', 'nfps.entity_id = nfs.entity_id');
                $query->leftJoin('node__field_function', 'nff', 'nfs.entity_id = nff.entity_id');
                $query->leftJoin('node__field_work_around', 'nfwa', 'nff.entity_id = nfwa.entity_id');

                $group_problems_view_service_id_query = \Drupal::database()->select('group_problems_view', 'gpv');
                $group_problems_view_service_id_query->addField('gpv', 'service_id');
                $group_problems_view_service_id_query->conditions('group_id', $group_id ? $group_id : self::PROBLEM_MANAGEMENT, '=');
                $group_problems_view_service = $group_problems_view_service_id_query->execute()->fetchAll();



                $group_problems_view = array();
                if (!empty($group_problems_view_service)) {
                    foreach ($group_problems_view_service as $service) {
                        $group_problems_view[] = $service->service_id;
                    }
                }

                $or = db_or();
                if (!empty($sql_where)) {
                    foreach ($sql_where as $where) {
                        foreach ($where as $conjuction => $condition) {
                            if ($conjuction == 'and') {
                                $query->condition($condition['field'], $condition['value'], $condition['operator']);
                            } else if ($conjuction == 'or') {
                                foreach ($condition as $conditions) {
                                    $or->condition($conditions['field'], $conditions['value'], $conditions['operator']);
                                }
                                $query->condition($or);
                            }
                        }
                    }
                }

                $query->condition('nfsn.field_s_no_value', $text, '=');

                if (!empty($group_problems_view)) {
                    $query->condition('nfs.field_services_target_id', $group_problems_view, 'IN');
                }

                $current_path = \Drupal::service('path.current')->getPath();
                $get_uri = explode('/', $current_path);

                if (isset($get_uri['4']) && $get_uri['4'] == 'archived_problems') {
                    $url = ( isset($group_id) ? 'group/' . $group_id . '/problems/archived_problems' : 'problems/archived_problems');
                    // $filter_where = " and nfps.field_problem_status_value = 'geschlossen' ";
                    $query->condition('nfps.field_problem_status_value', 'geschlossen', 'like');
                } else {
                    $url = ( isset($group_id) ? 'group/' . $group_id . '/problems' : 'problems');
                    $query->condition('nfps.field_problem_status_value', 'geschlossen', 'not like');
                }
                $sid = $query->execute()->fetchCol();
            }
        }



        if ($form_state['values']['function']) {
            $function = trim($form_state['values']['function']);
            $sql_where[] = array('and' =>
                array(
                    'field' => 'nff.field_function_value',
                    'value' => $function,
                    'operator' => '=',
                )
            );
        }

        if ($form_state['values']['release']) {
            $release = trim($form_state['values']['release']);
            $sql_where[] = array(
                'and' => array(
                    'field' => 'nfr.field_release_value',
                    'value' => $release,
                    'operator' => '=',
                )
            );
        }

        if ($form_state['values']['limit']) {
            $limit = $form_state['values']['limit'];
        }

        if ($sid) {
            $params = array(
                'url' => $url,
            );
            $params_seralized = serialize($params);
            $_SESSION['params_seralized'] = $params_seralized;
        }
        if ($sid) {
            return array("sid" => $sid, "query" => $sql_where);
        } else {
            return array("query" => $sql_where);
        }
    }

    static function ahah_problems_display($form, $form_state, $sql_where = NULL, $string = NULL, $limit = NULL) {
        $form_state->setValue('submitted', FALSE);
        $form_build_id = $_POST['form_build_id'];
        // FormCache::getCache($form_build_id, $form_state); 
        if ($_POST) {
            $service = $form_state->getValue('service');
        }

        //Geting functions and release data
        $default_function_releases = self::get_functions_release($string, $service);
        $form['function']['#options'] = !empty($default_function_releases['functions']) ? $default_function_releases['functions'] : $this->t("Select Service");
        // $form['function']['#options'] = $default_function_releases['functions'];
        $form['release']['#options'] = $default_function_releases['releases'];
        //  FormCache::setCache($form_build_id, $form, $form_state);
        $_SESSION['sql_where'] = $sql_where;
        $_SESSION['limit'] = $limit;

        $result['content']['#prefix'] = "<div id = 'problem_search_results_wrapper'>";
        $result['content']['problems_filter_element'] = \Drupal::formBuilder()->getForm('Drupal\problem_management\Form\ProblemFilterFrom', $string);
        $result['content']['problems_reset_element']['#prefix'] = "<div class = 'reset_form'>";
        $result['content']['problems_reset_element']['form'] = HzdproblemmanagementHelper::problem_reset_element();
        $result['content']['problems_reset_element']['#suffix'] = '</div><div style = "clear:both"></div>';
        $result['content']['problems_default_display'] = HzdStorage::problems_default_display($sql_where, $string, $limit);
        $result['content']['#suffix'] = "</div>";

        return $result;
    }

    /*
     * Function for populating the Functions and releses option values
     */

    static function get_functions_release($string = NULL, $service = NULL) {
        $sql_query = \Drupal::database()->select('node__field_function', 'nff');
        $sql_query->join('node__field_problem_status', 'nfps', 'nff.entity_id = nfps.entity_id');
        $sql_query->join('node__field_release', 'nfr', 'nfps.entity_id = nfr.entity_id');
        $sql_query->join('node__field_services', 'nfs', 'nfr.entity_id = nfs.entity_id');
        $sql_query->addField('nff', 'field_function_value', 'function');
        $sql_query->addField('nfr', 'field_release_value', 'prob_release');
        $sql_query->condition('nfs.field_services_target_id', $service, '=');

        if ($string == 'archived_problems') {
            $sql_query->condition('nfps.field_problem_status_value', 'geschlossen', '=');
            $sql_query->orderBy('nff.field_function_value');
        } else {
            $sql_query->condition('nfps.field_problem_status_value', 'geschlossen', '!=');
            $sql_query->orderBy('nff.field_function_value');
        }
        $default_function[] = t("Select Function");
        $default_release[] = t("Select Release");
        $functions = $sql_query->execute()->fetchAll();
        foreach ($functions as $function) {
            $default_function[$function->function] = $function->function;
            if ($function->prob_release) {
                $default_release[$function->prob_release] = $function->prob_release;
            }
        }

        return array('releases' => $default_release, 'functions' => $default_function);
    }

    /*
     * Problems display table
     * @sql_where: sql query for filtering the problems.
     * @string: type of problem(current, archived)
     * @limit: limit of problems to display per page.

     * archived problems will have the status "geschlossen".
     * problems which does not have the status 'geschlossen' will come under current
     * Details will displays the detailed problems display page where back to search is available.
     * While back from search same value need to be shown to user.
     * values are stored in session for showing the same results while back to search.
     */

    static function problems_default_display($sql_where = NULL, $string = NULL, $limit = NULL) {
        $group = \Drupal::routeMatch()->getParameter('group');
        if (is_object($group)) {
            $group_id = $group->id();
        } else {
            $group_id = $group;
        }
        // dpm($group_id);

        $sql_select = \Drupal::database()->select('node_field_data', 'nfd');
        $sql_select->Fields('nfd', array('nid'));
        $build = array();
        $request = \Drupal::request();
        if (!empty($_SESSION['problems_query'])) {
            $serialized_data = unserialize($_SESSION['problems_query']);
            $sql_where = $sql_where ? $sql_where : $serialized_data['sql'];
            $string = $string ? $string : $serialized_data['string'];
            $limit = $limit ? $limit : $serialized_data['limit'];
        }

        if ($string == 'archived_problems') {
            $url = ( $group_id ? 'group/' . $group_id . '/problems/archived_problems' : 'problems/archived_problems');
            // $filter_where = " and nfps.field_problem_status_value = 'geschlossen' ";
            $sql_select->condition('nfps.field_problem_status_value', 'geschlossen', 'like');
        } else {
            $url = ( $group_id ? 'group/' . $group_id . '/problems' : 'problems');
            // $filter_where = " and nfps.field_problem_status_value != 'geschlossen' ";
            $sql_select->condition('nfps.field_problem_status_value', 'geschlossen', 'not like');
        }
        // $sql_select = " SELECT n.nid ";

        $sql_select->leftJoin('node_revision', 'nr', 'nfd.nid = nr.nid');
        $sql_select->leftJoin('node__body', 'nb', 'nfd.nid = nb.entity_id');
        $sql_select->leftJoin('node__field_processing', 'nfp', 'nfd.nid = nfp.entity_id');
        $sql_select->leftJoin('node__field_problem_status', 'nfps', 'nfp.entity_id = nfps.entity_id');
        $sql_select->leftJoin('node__field_services', 'nfs', 'nfps.entity_id = nfs.entity_id');
        // $sql_select->join('node__field_problem_eroffnet', 'nfpe', 'nfsn.entity_id = nfpe.entity_id');
        $sql_select->leftJoin('node__field_function', 'nff', 'nfs.entity_id = nff.entity_id');
        $sql_select->leftJoin('node__field_release', 'nfr', 'nff.entity_id = nfr.entity_id');
        $sql_select->leftJoin('node__field_work_around', 'nfwa', 'nfr.entity_id = nfwa.entity_id');
        $sql_select->leftJoin('node__field_s_no', 'nfsn', 'nfwa.entity_id = nfsn.entity_id');


        $group_problems_view_service_id_query = \Drupal::database()->select('group_problems_view', 'gpv');
        $group_problems_view_service_id_query->addField('gpv', 'service_id');
        $group_problems_view_service_id_query->conditions('group_id', $group_id? : self::PROBLEM_MANAGEMENT, '=');
        $group_problems_view_service = $group_problems_view_service_id_query->execute()->fetchAll();
        $group_problems_view = array();

        if (!empty($group_problems_view_service)) {
            foreach ($group_problems_view_service as $service) {
                $group_problems_view[] = $service->service_id;
            }
        }

        if (!empty($sql_where)) {
            $or = db_or();
            foreach ($sql_where as $where) {
                foreach ($where as $conjuction => $condition) {
                    if ($conjuction == 'and') {
                        $sql_select->condition($condition['field'], $condition['value'], $condition['operator']);
                    } else if ($conjuction == 'or') {
                        foreach ($condition as $conditions) {
                            $or->condition($conditions['field'], $conditions['value'], $conditions['operator']);
                        }
                        $sql_select->condition($or);
                    }
                    // $query->condition($sql_where);
                }
            }
        }

        if (!empty($group_problems_view)) {
            $sql_select->condition('nfs.field_services_target_id', $group_problems_view, 'IN');
        }

        if ($limit == 'all') {
            $result = $sql_select->execute()->fetchAll();
        } else {
            $page_limit = ($limit ? $limit : self::DISPLAY_LIMIT);
            $pager = $sql_select->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($page_limit);
            $result = $pager->execute()->fetchAll();
        }

        $req_service = $request->request->get('service');
        $req_release = $request->request->get('release');
        $req_function = $request->request->get('function');
        $req_string = $request->request->get('string');
        $req_limit = $request->request->get('limit');
        $page = $request->request->get('page');

        $rows = array();
        $status_msgs = array('Neues Problem', 'Known Error', 'Geschlossen', 'behoben');

        foreach ($result as $problems_info) {
            $problems_node = node_load($problems_info->nid);

            $service_query = db_query("select title 
	      from {node_field_data}
	      where nid = :mlid", array(':mlid' => $problems_node->field_services->target_id))->fetchAssoc();
            $service = $service_query['title'];
            $last_update = $problems_node->field_processing->value;

            unset($_SESSION['problems_query']);
            $query_params = array(
                'nid' => $problems_node->nid->value,
                'page' => $page,
                'type' => $string,
                'sql' => $sql_where,
                'service' => $req_service,
                'function' => $req_function,
                'string' => $req_string,
                'release' => $req_release,
                'limit' => $limit,
                'url' => empty($url) ? null : $url,
                'from' => 1
            );

            $query_seralized = serialize($query_params);
            $_SESSION['problems_query'] = $query_seralized;
            $url = Url::fromUserInput('/node/' . $problems_node->nid->value, array(
                        'attributes' => array(
                            'class' => 'problems_details_link',
                            'nid' => $problems_node->nid->value,
                            'query' => $query_seralized,
            )));

            $download_link = array('#title' => array(
                    '#markup' => $problems_node->field_s_no->value
                ),
                '#type' => 'link',
                '#url' => $url
            );

            $link_path = \Drupal::service('renderer')->renderRoot($download_link);
            $user_input = '/node/' . $problems_node->nid->value;
            $elements = array(
                'service' => $service,
                'function' => $problems_node->field_function->value,
                'release' => $problems_node->field_release->value,
                'title' => $problems_node->title->value,
                'status' => $problems_node->field_problem_status->value,
                'priority' => $problems_node->field_priority->value,
                'closed' => $last_update,
                //  'actions' =>  $url_data->getGeneratedLink(),
                
            );
                        
            if ($string == 'archived_problems') {
                $elements['field_version'] = $problems_node->field_version->value;
                $elements['closed'] = $problems_node->field_closed->value;       
            }
            $elements['actions'] = $link_path;
            
            
            $rows[] = $elements;
        }

        $header = array(
            0 => array('data' => t('Service'), 'class' => 'service'),
            1 => array('data' => t('Function'), 'class' => 'function'),
            2 => array('data' => t('Release'), 'class' => 'release'),
            4 => array('data' => t('Title'), 'class' => 'problem_title'),
            5 => array('data' => t('Status'), 'class' => 'status'),
            6 => array('data' => t('Priority'), 'class' => 'priority'),
            7 => array('data' => t('Last Update'), 'class' => 'last_update'),
        );

        if ($string == 'archived_problems') {
            
            $header[] = array(
                'data' => t('Fixed With Release'),
                'class' => 'field_version'
            );

            $header[7] = array('data' => t('Closed On'), 'class' => 'closed');
        }
        $header[] = array(
            'data' => t('SDCallID'),
            'class' => 'action'
        );

        if ($rows) {
            $build['problem_table'] = array(
                '#theme' => 'table',
                '#header' => $header,
                '#rows' => $rows,
                '#empty' => t('No Data Created Yet'),
                '#attributes' => ['id' => "sortable", 'class' => "tablesorter"],
            );

            $build['pager'] = array(
                '#type' => 'pager',
                '#prefix' => '<div id="pagination">',
                '#suffix' => '</div>',
            );
            return $build;
        }
        return $build = array('#markup' => t("No Data Created Yet"));
    }

    static function delete_group_problems_view($group_id = null) {
        if (!$group_id) {
            return false;
        }
        // $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
        \Drupal::database()->delete('group_problems_view')->condition('group_id', $group_id, '=')
                ->execute();
    }

}
