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
        $maintenance_list = $maintenance_list->execute()->fetchAll();
        $states = \Drupal::database()->select('states', 's')
            ->fields('s', ['id', 'abbr'])
            ->condition('abbr', '', '<>')
            ->orderBy('id', 'asc')
            ->execute()
            ->fetchAllKeyed();
        $result = $serviceids_list = array();
        $unResolvedServices = $data = [];
        // Get the service id's list and get respective details from service id.
        foreach ($maintenance_list as $key => $vals) {
           // $incident = Node::load($vals->downtime_id);
            
           // if ($incident) {
                $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($vals->downtime_id);
                $serviceid = explode(',', $vals->service_id);
                $stateids = explode(',', $vals->state_id);
                $serviceNames = node_get_all_title_fast($serviceid);
                foreach ($serviceid as $ids) {
                    // Loops for all services
                    foreach ($stateids as $sids) {
                        // Loops for all states
                        if ($groupContent) {
                            $hoverIconHtml = $hover_markup = null;
                            if ($routeMatch->getRouteName() == 'hzd_customizations.front') {
                              $renderer = \Drupal::service('renderer');
                                $markupdata = ['#type'=>'container','#attributes'=>['id' => 'maintenance-' . $vals->downtime_id, 'class'=>['downtime-popover-wrapper']]];
                                $hover_markup = $renderer->render($markupdata);
                                $hoverIconHtml = '<div class="service-tooltip"><img height="10" src="/themes/hzd/images/i-icon-26.png"></div>';
                            }
                            $class = '';
                            if ($vals->startdate_planned < REQUEST_TIME) {
                                $class = 'text-danger';
                            }
                            $label = Markup::create('<span class="state-item ' . $class . '">[' . $states[$sids] . '] ' . date('d.m.Y H:i', $vals->startdate_planned) . ' Uhr </span>');
                            $url = Url::fromUserInput('/node/' . $vals->downtime_id);
                            $data[$ids][] = Markup::create(Link::fromTextAndUrl($label, $url)->toString() . $hoverIconHtml . $hover_markup);
                        }
                    }
                }
            //}
        }
        # Sort services alphabetically
        if (!empty($serviceNames))
        asort($serviceNames, SORT_STRING | SORT_FLAG_CASE | SORT_NATURAL);
        $link_options = array(
            'attributes' => array(
                'class' => array(
                    'front-page-link',
                ),
            ),
        );
        $downtime_link_options = array(
            'attributes' => array(
                'class' => array(
                    'downtime-create-button',
                ),
            ),
        );

        $markup['items'] = ['#type'=>'container','#attributes'=>['class'=>['maintenance-home-info']]];
        foreach ($data as $sid => $item) {
            $class = '';
            $title = Markup::create('<span class="' . $class . '">' . $serviceNames[$sid] . '</span>');
            $markup['items']['incident_list'][] = [
                '#title' => $title,
                '#prefix' => '<div class="maintenance-list">',
                '#suffix' => '</div>',
                '#items' => $item,
                '#theme' => 'item_list',
                '#weight'=> array_search($sid, array_keys($serviceNames)),
                '#type' => 'ul',
                '#attributes' => array(
                    'class' => array(
                        'front-page-link',
                    ),
                ),
            ];
      
        }
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
              '#url' => Url::fromRoute('downtimes.create_maintenance', ['group' => INCIDENT_MANAGEMENT], $downtime_link_options)
            ];
          }
        } else {
            $markup['#attributes'] = ['class' => ['view-downtime-block']];
        }

      $markup['#cache'] = ['max-age' => 0];
        
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
        $view_builder = \Drupal::entityManager()->getViewBuilder('node');
        $markup = $view_builder->view($entity, 'popup', 'de');
        $container = ['#type'=>'container','#attributes'=>['class'=>['downtime-popover-wrapper']]];
        $container[] = $markup;
        return \Drupal::service('renderer')->render($container);
    }
    
}
