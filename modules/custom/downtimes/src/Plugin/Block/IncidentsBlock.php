<?php

namespace Drupal\downtimes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'IncidentsBlock' block.
 *
 * @Block(
 *  id = "incidents_block",
 *  admin_label = @Translation("Incident Block"),
 * )
 */
class IncidentsBlock extends BlockBase
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
        if ($routeMatch->getRouteName() == 'cust_group.group_content_view' && $parameters->get('group')->id() == INCIDENT_MANAGEMENT && $parameters->get('group_content')->getEntity()->getType() == 'downtimes') {
            //exception for downtimes content type
            return AccessResult::allowed();
        }
        if ($routeMatch->getRouteName() == 'front_page.front') {
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
            ->fields('d', ['service_id', 'description', ' downtime_id', ' state_id', 'reason', 'startdate_planned', 'enddate_planned', 'scheduled_p'])
            ->condition('service_id', '0', '<>')
            ->condition('cancelled', 0)
            ->condition('resolved', 0)
            ->condition('scheduled_p', 0)
            ->orderBy('startdate_planned', 'desc');
//        ->condition('startdate_planned', REQUEST_TIME, '<=');
        /*    $orGroup = $maintenance_list->orConditionGroup()
          ->condition('scheduled_p', 0);
          $andGroup = $maintenance_list->andConditionGroup()
          ->condition('scheduled_p', 1)
          ->condition('startdate_planned', REQUEST_TIME, '<=');
          $orGroup->condition($andGroup);
          $maintenance_list->condition($orGroup); */
        $maintenance_list = $maintenance_list->execute()->fetchAll();
        $states = \Drupal::database()->select('states', 's')
            ->fields('s', ['id', 'abbr'])
            ->condition('abbr', '', '<>')
            ->orderBy('id', 'asc')
            ->execute()
            ->fetchAllKeyed();
        /*    $maintenance_list = \Drupal::database()->select("SELECT service_id,description, downtime_id, state_id,reason,startdate_planned,enddate_planned,scheduled_p FROM downtimes d WHERE d.service_id <> '' AND d.cancelled = 0  AND d.resolved = 0 AND (d.scheduled_p = 0 OR (d.scheduled_p = 1 AND startdate_planned <= :current_date))", array(':current_date' => REQUEST_TIME))->fetchAll(); */
        $result = $serviceids_list = array();
        $data = [];
        // Get the service id's list and get respective details from service id.
        foreach ($maintenance_list as $key => $vals) {
            $incident = Node::load($vals->downtime_id);
            
            if ($incident) {
                $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($vals->downtime_id);
                $serviceid = explode(',', $vals->service_id);
                $stateids = explode(',', $vals->state_id);
                $serviceEntities = Node::loadMultiple($serviceid);
                $serviceTitles = $stateTitles = null;
                foreach ($serviceEntities as $serviceItem) {
                    $serviceTitles .= $serviceItem->getTitle() . ', ';
                }
                foreach ($stateids as $stateId) {
                  if(isset($states[$stateId]))
                    $stateTitles .= ' [' . $states[$stateId] . ']';
                }
                if ($groupContent) {
                    $hoverIconHtml = $hover_markup = null;
//          $hover_markup = MaintenanceBlock::get_hover_markup($vals->startdate_planned, $vals->enddate_planned, $vals->description, $vals->scheduled_p);
                    if ($routeMatch->getRouteName() == 'front_page.front') {
                        $hoverIconHtml = '<div class="service-tooltip"><img height="10" src="/themes/hzd/images/i-icon-26.png"></div>';
                        $hover_markup = MaintenanceBlock::get_hover_markup($incident);
                    }
                    $label = Markup::create(trim($serviceTitles, ', ') . $stateTitles);
//                    $url = $groupContent->toUrl()->setOption('attributes', ['class' => ['text-danger']]);
//                    $data[] = Markup::create(Link::fromTextAndUrl($label, $url)->toString() . ' ' . date('d.m.Y H:i', $vals->startdate_planned) . ' Uhr ' . $hoverIconHtml . $hover_markup);
//                  $url = Url::fromRoute('cust_group.group_content_view',['group'=>$groupContent->getGroup()->id(),'type'=>'downtimes','group_content'=>$groupContent->id()],['attributes'=>['class' => ['text-danger']]]);
                  $url = $incident->toUrl('canonical', ['attributes'=>['class' => ['text-danger']]]);
                    $data[] = Markup::create(Link::fromTextAndUrl($label, $url)->toString() . ' ' . date('d.m.Y H:i', $vals->startdate_planned) . ' Uhr ' . $hoverIconHtml . $hover_markup);
//                    $data[] = Markup::create(Link::createFromRoute($label, 'cust_group.group_content_view',['group'=>$groupContent->getGroup()->id(),['type'=>'downtimes','group_content'=>$groupContent->id()]],['attributes'])->toString() . ' ' . date('d.m.Y H:i', $vals->startdate_planned) . ' Uhr ' . $hoverIconHtml . $hover_markup);
                }
//        foreach ($serviceid as $ids) {
//          // Loops for all services
//          $service = Node::load($ids);
//          $service_name = $service->getTitle();
//          $stateText = '';
//          $serviceNames[$ids] = $service_name;
//          foreach ($stateids as $sids) {
//            // Loops for all states
//          }
//        }
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
//    $report_link = Link::createFromRoute($this->t('Report Downtime'), 'downtimes.create_downtimes', ['group' => INCIDENT_MANAGEMENT], $link_options);
//    foreach ($data as $sid => $item) {
        $markup['incident_list'][] = [
//        '#title' => $serviceNames[$sid],
            '#prefix' => '<div>',
            '#suffix' => '</div>',
            '#items' => $data,
            '#theme' => 'item_list',
            '#type' => 'ul',
            '#attributes' => ['class' => ['incidents-home-block']]
        ];
//    }
//    $build['incidents_block_number_of_posts']['#markup'] = render($markup['incident_list']) . render($markup['all_link']) . render($markup['report_link']);
        $markup['downtimes'] = ['#type' => 'container', '#weight' => 100, '#attributes' => ['class' => ['link-wrapper-downtimes']]];
        $markup['downtimes']['list'] = [
            '#title' => $this->t('Störungen und Blockzeiten'),
            '#type' => 'link',
            '#url' => Url::fromRoute('downtimes.new_downtimes_controller_newDowntimes', ['group' => INCIDENT_MANAGEMENT], $link_options)
        ];
        if ($routeMatch->getRouteName() == 'front_page.front') {
          $access = \Drupal::service('access_manager')->checkNamedRoute('downtimes.create_downtimes', ['group' => INCIDENT_MANAGEMENT], \Drupal::currentUser());
          if($access) {
            $markup['downtimes']['create'] = [
              '#title' => $this->t('Report Downtime'),
              '#type' => 'link',
              '#url' => Url::fromRoute('downtimes.create_downtimes', ['group' => INCIDENT_MANAGEMENT], $link_options)
            ];
          }
            $markup['#attributes'] = ['class' => ['frontpage-downtime-block']];
        } else {
            $markup['#attributes'] = ['class' => ['view-downtime-block']];
        }
        $markup['#cache']['max-age'] = 0;
        return $markup;
    }
    
}
