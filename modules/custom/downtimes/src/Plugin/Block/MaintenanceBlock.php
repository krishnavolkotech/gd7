<?php

namespace Drupal\downtimes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\Node;
use Drupal\Core\Render\Markup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'MaintenanceBlock' block.
 *
 * @Block(
 *  id = "maintenance_block",
 *  admin_label = @Translation("Maintenance"),
 * )
 */
class MaintenanceBlock extends BlockBase
{
    
    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        return [
                'number_of_posts' => $this->t(''),
            ] + parent::defaultConfiguration();
    }
    
    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {
        $form['number_of_posts'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Number of posts'),
            '#description' => $this->t(''),
            '#default_value' => $this->configuration['number_of_posts'],
            '#maxlength' => 64,
            '#size' => 64,
            '#weight' => '0',
        ];
        
        return $form;
    }
    
    /**
     * {@inheritdoc}
     */
    public function blockSubmit($form, FormStateInterface $form_state) {
        $this->configuration['number_of_posts'] = $form_state->getValue('number_of_posts');
    }
    
    function access(AccountInterface $account, $return_as_object = false) {
        $routeMatch = \Drupal::routeMatch();
        $parameters = $routeMatch->getParameters();
        if ($routeMatch->getRouteName() == 'entity.node.canonical' && $parameters->get('node')->getType() == 'downtimes') {
            //exception for downtimes content type
            return AccessResult::allowed();
        }
        if ($routeMatch->getRouteName() == 'hzd_customizations.front') {
            return AccessResult::allowed();
        }
        return AccessResult::forbidden();
    }
    
    /**
     * {@inheritdoc}
     */
    public function build() {
        $routeMatch = \Drupal::routeMatch();
        $build = [];
        $maintenance_list = \Drupal::database()->select("downtimes", 'd')
            ->fields('d', ['service_id', 'description', ' downtime_id', ' state_id', 'reason', 'startdate_planned', 'enddate_planned', 'scheduled_p', 'resolved'])
            ->condition('service_id', '0', '<>')
            ->condition('cancelled', 0)
            ->condition('resolved', 0)
            ->condition('scheduled_p', 1)
            ->orderBy('startdate_planned', 'asc');
//        ->condition('startdate_planned', REQUEST_TIME, '>=');
//    $orGroup = $maintenance_list->orConditionGroup()
//        ->condition('scheduled_p', 1)
//        ->condition('resolved', 0);
//    $andGroup = $maintenance_list->andConditionGroup()
//        ->condition('scheduled_p', 1)
//        ->condition('startdate_planned', REQUEST_TIME, '<=');
//    $orGroup->condition($andGroup);
//    $maintenance_list->condition($orGroup);
        $maintenance_list = $maintenance_list->execute()->fetchAll();
        $states = \Drupal::database()->select('states', 's')
            ->fields('s', ['id', 'abbr'])
            ->condition('abbr', '', '<>')
            ->orderBy('id', 'asc')
            ->execute()
            ->fetchAllKeyed();
//    $maintenance_list = \Drupal::database()->select("SELECT service_id,description, downtime_id, state_id,reason,startdate_planned,enddate_planned,scheduled_p FROM downtimes d WHERE d.service_id <> '' AND d.cancelled = 0  AND d.resolved = 0 AND (d.scheduled_p = 0 OR (d.scheduled_p = 1 AND startdate_planned <= :current_date))", array(':current_date' => REQUEST_TIME))->fetchAll();
        $result = $serviceids_list = array();
        $unResolvedServices = $data = [];
        // Get the service id's list and get respective details from service id.
        foreach ($maintenance_list as $key => $vals) {
            $incident = Node::load($vals->downtime_id);
            
            if ($incident) {
                $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($vals->downtime_id);
                $serviceid = explode(',', $vals->service_id);
                $stateids = explode(',', $vals->state_id);
                
                foreach ($serviceid as $ids) {
                    // Loops for all services
                    $service = Node::load($ids);
                    $service_name = $service->getTitle();
                    $stateText = '';
                    $serviceNames[$ids] = $service_name;
                    foreach ($stateids as $sids) {
                        // Loops for all states
                        if ($groupContent) {
//                            $hover_markup = MaintenanceBlock::get_hover_markup($vals->startdate_planned, $vals->enddate_planned, $vals->description, $vals->scheduled_p);
                            $hoverIconHtml = $hover_markup = null;
                            if ($routeMatch->getRouteName() == 'hzd_customizations.front') {
                                $hover_markup = MaintenanceBlock::get_hover_markup($incident);
                                $hoverIconHtml = '<div class="service-tooltip"><img height="10" src="/themes/hzd/images/i-icon-26.png"></div>';
                            }
                            $class = '';
                            if ($vals->startdate_planned < REQUEST_TIME) {
                                $class = 'text-danger';
//                                $unResolvedServices[$ids] = $ids;
                            }
                            $label = Markup::create('<span class="state-item ' . $class . '">[' . $states[$sids] . '] ' . date('d.m.Y H:i', $vals->startdate_planned) . ' Uhr </span>');
                          $url = $incident->toUrl();
//                          $url = Url::fromRoute('cust_group.group_content_view',['group'=>$groupContent->getGroup()->id(),'type'=>'downtimes','group_content'=>$groupContent->id()]);
                            $data[$ids][] = Markup::create(Link::fromTextAndUrl($label, $url)->toString() . $hoverIconHtml . $hover_markup);
                        }
                    }
                }
            }
        }
        
        
        $link_options = array(
            'attributes' => array(
                'class' => array(
                    'front-page-link',
                ),
            ),
        );


//    $all_link = Link::createFromRoute($this->t('Störungen und Blockzeiten'), 'downtimes.new_downtimes_controller_newDowntimes', ['group' => INCIDENT_MANAGEMENT], $link_options);
//    $report_link = Link::createFromRoute($this->t('Report Maintenance'), 'downtimes.create_maintenance', ['group' => INCIDENT_MANAGEMENT], $link_options);
        $markup['items'] = ['#type'=>'container','#attributes'=>['class'=>['maintenance-home-info']]];
        foreach ($data as $sid => $item) {
            $class = '';
//            if (in_array($sid, $unResolvedServices)) {
//                $class = 'text-danger';
//            }
            $title = Markup::create('<span class="' . $class . '">' . $serviceNames[$sid] . '</span>');
            
            $markup['items']['incident_list'][] = [
                '#title' => $title,
                '#prefix' => '<div class="maintenance-list">',
                '#suffix' => '</div>',
                '#items' => $item,
                '#theme' => 'item_list',
                '#weight'=> ord($serviceNames[$sid]),
                '#type' => 'ul',
                '#attributes' => array(
                    'class' => array(
                        'front-page-link',
                    ),
                ),
            ];
      
        }
//        pr($markup);exit;
        $markup['downtimes'] = ['#type' => 'container', '#weight' => 100, '#attributes' => ['class' => ['link-wrapper-downtimes']]];
        $markup['downtimes']['list'] = [
            '#title' => $this->t('Störungen und Blockzeiten'),
            '#type' => 'link',
            '#url' => Url::fromRoute('downtimes.new_downtimes_controller_newDowntimes', ['group' => INCIDENT_MANAGEMENT], $link_options)
        ];
        if ($routeMatch->getRouteName() == 'hzd_customizations.front') {
            $markup['#attributes'] = ['class' => ['frontpage-downtime-block']];
          $access = \Drupal::service('access_manager')->checkNamedRoute('downtimes.create_maintenance', ['group' => INCIDENT_MANAGEMENT], \Drupal::currentUser());
          if($access) {
            $markup['downtimes']['create'] = [
              '#title' => $this->t('Report Maintenance'),
              '#type' => 'link',
              '#url' => Url::fromRoute('downtimes.create_maintenance', ['group' => INCIDENT_MANAGEMENT], $link_options)
            ];
          }
        } else {
            $markup['#attributes'] = ['class' => ['view-downtime-block']];
        }
//    $markup['all_link'] = $all_link->toString();
//    $markup['report_link'] = $report_link->toString();
//    $build['incidents_block_number_of_posts']['#markup'] = render($markup['incident_list']) . render($links);
        $markup['#cache']['max-age'] = 0;
        
        return $markup;
    }
    
    /**
     * Return the hover markup to be shown on front page blocks for downtimes.
     * @param unix time $start_date_planned
     * @param unix time $end_date_planned
     * @param string $description
     * @param boolean $scheduled_p
     * @return markup
     */
    public static function get_hover_markup($entity) {
        
/*        $html = "<ul class='downtime-hover' style='display:none;'>";
        // Getting the below start date. end date and description for hover.
        if (!empty($start_date_planned)) {
            $start_date_planned = DateTimePlus::createFromTimestamp((integer)$start_date_planned)->format('d.m.Y');
            $html .= "<li>" . t('Start:') . $start_date_planned . "</li>";
        }
        
        // If end date is not empty and if it is maintenance(ie., scheduled_p =1), then only display end date in hover.
        if (!empty($end_date_planned) && $scheduled_p) {
            $end_date_planned = DateTimePlus::createFromTimestamp((integer)$end_date_planned)->format('d.m.Y');
            $html .= "<li>" . t('End:') . $end_date_planned . "</li>";
        }
        
        if (!empty($description)) {
            $description = strip_tags($description);
            $description = Unicode::Truncate($description, 100, TRUE, TRUE);
            $html .= "<li>$description</li>";
        }
        
        $html .= "</ul>";*/
        $view_builder = \Drupal::entityManager()->getViewBuilder('node');
        $markup = $view_builder->view($entity, 'popup', 'de');
        
        return \Drupal::service('renderer')->render($markup);
    }
    
}
