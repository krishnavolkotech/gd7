<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Cache\CacheBackendInterface;


if(!defined('EXEOSS'))
  define('EXEOSS', \Drupal::config('hzd_release_management.settings')->get('ex_eoss_service_term_id'));

/**
 *
 */
class DeployedReleases extends ControllerBase {

  /**
   *  
   */
  public function OverView(GroupInterface $group) {
    
    $form = \Drupal::formBuilder()
            ->getForm('Drupal\hzd_release_management\Form\DeployedReleasesOverviewiew');
    $form['form_build_id']['#access'] = FALSE;
    $form['form_token']['#access'] = FALSE;
    $form['form_id']['#access'] = FALSE;
    $output[] = $form;
    $type = $request = \Drupal::request()->get('release_type', KONSONS);
    $output[] = $this->display_deployed_release_table($group, $type);
    return $output;
  }

  public function display_deployed_release_table(GroupInterface $group, $release_type) {

    if(!in_array($release_type, [KONSONS, EXEOSS])){
      return ['#markup' => Markup::create($this->t("Invalid input given"))];
    }

    $group_id = $group->id();
    $cid = 'deployedReleasesOverview:' . $group_id . ':' . $release_type;
    $build = NULL;

    $cache = \Drupal::cache()->get($cid);
    if (!empty($cache->data)) {
      $build = $cache->data;
      return $build;
    }



    $db = \Drupal::database();
    $states = $db->select('states', 's')
            ->condition('s.id', 1, '!=')
            ->fields('s', array('abbr', 'id'))
            ->orderBy('s.abbr');
    $states = $states->execute()->fetchAll();
    $groupServs = $db->select('group_releases_view', 's')
            ->condition('s.group_id', $group_id, '=')
            ->fields('s', array('service_id'))
            ->distinct()
            ->orderBy('s.service_id');
    $groupServs = $groupServs->execute()->fetchCol();

    $services = \Drupal::entityQuery('node')
            ->condition('type', 'services')
            ->condition('status', 1)
            ->condition('field_release_name', 'NULL', '!=')
            ->condition('release_type', $release_type)
            ->sort('title')
            ->execute();

    $groupServs = array_intersect($services, $groupServs);
    if (count($groupServs) > 0) {
      $serviceEntities = Node::loadMultiple($groupServs);
      $state = array();
      $headers[0] = t('Service');
      foreach ($states as $state_details) {
        $state[$state_details->abbr] = $state_details->id;
        $headers[$state_details->abbr] = $state_details->abbr;
      }
      $emptyStates = NULL;
      $count = 1;
      foreach ($serviceEntities as $service) {
        $dep = NULL;
        foreach ($states as $state_details) {
          $titles = NULL;
          $releases = \Drupal::entityQuery('node')
                  ->condition('type', 'deployed_releases')
                  ->condition('field_release_service', $service->id())
                  ->condition('field_user_state', $state_details->id)
                  ->condition('field_archived_release', 1, '<>')
                  ->condition('status', 1)
                  ->condition('field_environment', 1)
                  ///the database record for field_archived_release doesn't exist so cannot query it here.
//            ->condition('field_archived_release', 1,'<>')
                  ->execute();
          foreach ($releases as $release) {
            $releaseNode = Node::load($release);
            $finalRelease = $releaseNode->get('field_earlywarning_release')->value;
	    $finalReleaseNode = Node::load($finalRelease);
            if ($finalReleaseNode) { $titles[] = $finalReleaseNode->label(); }
          }
          if ($titles) {
            $x = [
                '#items' => $titles,
                '#theme' => 'item_list',
                '#type' => 'ul',
            ];
            $newData[$state_details->abbr] = render($x);
          } else {
            !isset($emptyStates[$state_details->abbr]) ? $emptyStates[$state_details->abbr] = 0 : null;
            $emptyStates[$state_details->abbr] += 1;
            $newData[$state_details->abbr] = '';
          }
        }
        //$dep[] = $service->get('field_release_name')->value;
	      $dep[] = $service->get('title')->value;
        $dep += $newData;
        $depReleases[] = $dep;
        $newData = NULL;
        $count++;
      }
      foreach ($depReleases as $key => $depRelease) {
        foreach ($depRelease as $state => $stateData) {
          if (isset($emptyStates[$state]) && $emptyStates[$state] == count($depReleases)) {
            unset($depReleases[$key][$state]);
            unset($headers[$state]);
          }
        }
      }
      $build = [
        '#theme' => 'table',
        '#header' => $headers,
        '#rows' => $depReleases,
        '#attributes' => [
            'class' => ['view-deployed-releases']
        ]
      ];
      \Drupal::cache()->set($cid, $build, CacheBackendInterface::CACHE_PERMANENT, ['deployedReleasesOverview']);
      return $build;
    } else {
      return ['#markup' => Markup::create($this->t("No data created yet"))];
    }
  }
}