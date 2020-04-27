<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Cache\CacheBackendInterface;


if (!defined('EXEOSS'))
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

    if (!in_array($release_type, [KONSONS, EXEOSS])) {
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
      $state = array();
      $headers[0] = t('Service');
      foreach ($states as $state_details) {
        $state[$state_details->abbr] = $state_details->id;
        $headers[$state_details->abbr] = $state_details->abbr;
      }
      $emptyStates = NULL;
      $count = 1;
      $query = \Drupal::database()->select('node_field_data', 'n')
        ->condition('type', 'deployed_releases')
        ->condition('status', 1);
      $query->leftjoin('node__field_release_service', 'ufrs', 'n.nid = ufrs.entity_id');
      $query->leftjoin('node__field_user_state', 'ufus', 'n.nid = ufus.entity_id');

      $query->leftjoin('node__field_archived_release', 'ufar', 'n.nid = ufar.entity_id');
      $query->condition('ufar.field_archived_release_value', 1, '<>');

      $query->leftjoin('node__field_environment', 'ufe', 'n.nid = ufe.entity_id');
      $query->condition('ufe.field_environment_value', 1);

      $query->fields('n', ['nid', 'title']);
      $query->fields('ufrs', ['field_release_service_value']);
      $query->fields('ufus', ['field_user_state_value']);
      $query_results = $query->execute()->fetchAll();
      $cus_results = [];
      foreach ($query_results as $row) {
        $cus_results[$row->field_release_service_value][$row->field_user_state_value][] = $row;
      }
      foreach ($groupServs as $service) {
        $dep = $results = NULL;
        if (key_exists($service, $cus_results)) {
          $results = $cus_results[$service];
        }
        if ($results) {
          foreach ($states as $state_details) {
            $titles = $releases = NULL;
            if (key_exists($state_details->id, $results)) {
              $releases = $results[$state_details->id];
            }
            if ($releases) {
              foreach ($releases as $release) {
                $release_service_value = $release->nid;
                $finalRelease = node_get_field_data_fast([$release_service_value], 'field_earlywarning_release');
                $finalReleaseNode = node_get_title_fast([$finalRelease[$release_service_value]]);
                if ($finalReleaseNode) {
                  $titles[] = $finalReleaseNode[$finalRelease[$release_service_value]];
                }
              }
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
          $dep[] = node_get_title_fast([$service])[$service];
          $dep += $newData;
          $depReleases[] = $dep;
          $newData = NULL;
          $count++;
        }
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
        '#attributes' => array(
            'id' => 'current_deploysortable',
            'class' => ['view-deployed-releases'],
        ),
      ];
      \Drupal::cache()->set($cid, $build, CacheBackendInterface::CACHE_PERMANENT, ['deployedReleasesOverview']);
      return $build;
    } else {
      return ['#markup' => Markup::create($this->t("No data created yet"))];
    }
  }
}
