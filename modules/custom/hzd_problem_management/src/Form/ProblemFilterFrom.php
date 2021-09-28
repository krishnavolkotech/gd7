<?php

/**
 * @file
 * Contains \Drupal\problem_management\Form\ProblemFilterFrom
 */

namespace Drupal\problem_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\problem_management\HzdStorage;

class ProblemFilterFrom extends FormBase
{
    
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'problem_filter_form';
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'problem_management.settings',
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    // (array $form, FormStateInterface $form_state,$arg = NULL)
    public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $default_limit = NULL) {
        $filter_value = HzdStorage::get_problem_filters();
        $group_id = get_group_id();
        $form['#method'] = 'get';
        /*
         * In problems setting page user will select the services . Only those
         * service relalted problems is to be displayed in result and service
         * filter.
         */
        $default_services[0] = '<' . t("Select Service")->render() . '>';
        $query = \Drupal::database()->select('node_field_data', 'nfd');
        $query->join('group_problems_view', 'gpv', 'nfd.nid = gpv.service_id');
        $query->Fields('nfd', array('nid', 'title'));
        $query->condition('gpv.group_id', $group_id, '=');
        //Filter services with minimum one problem/release.
        $query->join('node__field_services', 'nfs', 'nfd.nid=nfs.field_services_target_id');
        $query->orderBy('nfd.title','asc');
        $service = $query->execute()->fetchAll();

        //select field_services_target_id from node_field_data nfd join node__field_services nfs where nfd.nid=nfs.entity_id and nfd.type='problem' group by field_services_target_id;

        foreach ($service as $services) {
          $serviceEntity = \Drupal\node\Entity\Node::load($services->nid);
//          if(!empty($serviceEntity->get('field_problem_name')->value)){
//            $default_services[$services->nid] = $serviceEntity->get('field_problem_name')->value;
//          }
         $default_services[$services->nid] = $serviceEntity->get('field_problem_name')->value;
        }
        // default functions
        $default_function[0] = '<' . t("Select Function")->render() . '>';
    
        // default releases
        $default_release[0] = '<' . t("Select Release")->render() . '>';
        
        $service_id = $filter_value['service'];
        if (isset($service_id)) {
            $populatedData = HzdStorage::get_functions_release($type, $service_id);;
            $default_function += $populatedData['functions'];
            $default_release += $populatedData['releases'];
        }

        $form['#prefix'] = "<div class = 'problem_filters'>";
        $form['#suffix'] = "</div>";
        natcasesort($default_services);
        $form['service'] = array(
            '#type' => 'select',
            '#options' => $default_services,
//      '#default_value' => isset($default_value_service) ? 
//      $default_value_service: $form_state->getValue('service'),
            '#default_value' => isset($filter_value['service']) ? $filter_value['service'] : $form_state->getValue('service'),
            '#weight' => -10,
//      '#ajax' => array(
//        'callback' => '::problem_search_results',
//        'wrapper' => 'problem_search_results_wrapper',
//        'method' => 'replace',
//        'event' => 'change',
//        'progress' => array(
//          'type' => 'throbber', 
//          'message' => NULL,
//          ),
//        ),
            '#attributes' => array(
                'onchange' => 'jQuery(\'select[name="function"]\').prop(\'selectedIndex\',0);jQuery(\'select[name="release"]\').prop(\'selectedIndex\',0);this.form.submit();',
            ),
            '#prefix' => "<div class = 'service_search_dropdown hzd-form-element'>",
            '#suffix' => '</div>',
//      '#validated' => TRUE,
        );
        
        
        $form['function'] = array(
            '#type' => 'select',
            '#options' => $default_function,
            // '#default_value' => isset($default_value_function) ?
            // $default_value_function: $form_state->getValue('function'),
            '#default_value' => isset($filter_value['function']) ? $filter_value['function'] : $form_state->getValue('function'),
            '#weight' => -9,
//      '#ajax' => array(
//        'callback' => '::problem_search_results',
//        'wrapper' => 'problem_search_results_wrapper',
//        'method' => 'replace',
//        'event' => 'change',
//        'progress' => array(
//            'type' => 'throbber', 
//            'message' => NULL,
//          ),
//        ),
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => "<div class = 'function_search_dropdown hzd-form-element'>",
            '#suffix' => '</div>',
//      '#validated' => TRUE,
        );
        
        $form['release'] = array(
            '#type' => 'select',
            '#options' => $default_release,
            // '#default_value' => isset($default_value_release) ?
            //  $default_value_release : $form_state->getValue('release'),
            '#default_value' => isset($filter_value['release']) ? $filter_value['release'] : $form_state->getValue('release'),
            '#weight' => -8,
//      '#ajax' => array(
//        'callback' => '::problem_search_results',
//        'wrapper' => 'problem_search_results_wrapper',
//        'method' => 'replace',
//        'event' => 'change',
//        'progress' => array(
//          'type' => 'throbber',
//          'message' => NULL,
//          ),
//       ),
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => "<div class = 'release_search_dropdown hzd-form-element'>",
            '#suffix' => '</div>',
//      '#validated' => TRUE,
        );

//    $service_id = isset($filter_value['service']) ? $filter_value['service']
//    ['service']: $form_state->getValue('service');
        
        
        $search_string = $filter_value['string'];
        $form['string'] = array(
            '#type' => 'textfield',
            '#weight' => -7,
            '#size' => 42,
            //   '#default_value' => !empty($default_value_string) ?
            //   $default_value_string :
            //   $this->t('Search Title, Description, Cause, Workaround, Solution'),
            '#default_value' => isset($search_string) ? $search_string : '',
            '#placeholder' => $this->t('Search Title, Description, Cause, Workaround, Solution'),
            '#attributes' => array("class" => ["search_string"]),
            "#prefix" => "<div class = 'string_search hzd-form-element'>",
            '#suffix' => '</div>',
        );
        
        $form['submit'] = array(
            '#type' => 'button',
            '#weight' => -6,
            //      '#ajax' => array(
            //        'callback' => '::problem_search_results',
            //        'wrapper' => 'problem_search_results_wrapper',
            //        'method' => 'replace',
            //        'event' => 'click',
            //        'progress' => array('type' => 'throbber'),
            //        ),
            '#attributes' => array("class" => ["filter_submit"]),
            '#prefix' => '<div class = "search_string_submit  hzd-form-element-auto">',
            '#suffix' => '</div>',
        );
        
        $default_limit = array(
            'all' => '<' . t('All') . '>',
            20 => 20,
            50 => 50,
            100 => 100,
        );
//    $limit = $form_state->getValue('limit');
        $form['limit'] = array(
            '#type' => 'select',
            '#options' => $default_limit,
            '#default_value' => isset($filter_value['limit']) ? $filter_value['limit'] : DISPLAY_LIMIT,
            '#weight' => -8,
//      '#ajax' => array(
//        'callback' => '::problem_search_results',
//        'wrapper' => 'problem_search_results_wrapper',
//        'method' => 'replace',
//        'event' => 'change',
//        'progress' => array(
//          'type' => 'throbber',
//          'message' => NULL,
//         ),
//      ),
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            "#prefix" => "<div class = 'limit_search_dropdown  hzd-form-element'>",
            '#suffix' => '</div>',
        );
//  $form['#action'] = '/' .$path;
        /*$form['actions'] = array(
            '#type' => 'actions'
        );*/
        $form['actions']['reset'] = array(
            '#type' => 'button',
            '#value' => t('Reset'),
            '#weight' => 100,
            '#attributes' => array(
                'onclick' => 'reset_form_elements(); return false;',
		'class'=> ['button','btn-default','btn']
            ),
        );
      $form['#exclude_from_print']=1;
        
        return $form;
    }
    
    /**
     * {@inheritDoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
    }
    
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
    }
}
