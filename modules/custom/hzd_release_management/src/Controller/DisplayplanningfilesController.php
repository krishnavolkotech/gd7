<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\file\Entity\File;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 *
 */
class DisplayplanningfilesController extends ControllerBase {

  /**
   *
   */
  public function display_planning_files() {
    $output['#title'] = t('Planning Files (Overview)');
    $breadcrumb = array();
    $url = Url::fromRoute('/');
    $link = Link::fromTextAndUrl(t('Home'), $url);
    $breadcrumb[] = $link;
    $url = Url::fromUserInput('/release-management');
    $link = Link::fromTextAndUrl(t('Deployed Releases'), $url);
    $breadcrumb[] = $link;
    $breadcrumb[] = t('Deployed Releases');
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();
    $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
    $breadcrumb[] = $title;
    $output['#breadcrumb'] = $breadcrumb;

    $plannigFileNodeIds = \Drupal::entityQuery('node')
      ->condition('type', 'planning_files')
      ->execute();
    $plannigFileNodeTitles = node_get_title_fast($plannigFileNodeIds);
    $upload_planning_files = node_get_field_data_target_fast($plannigFileNodeIds, 'field_upload_planning_file');
    $header = array(t('Title'), t('Filename'), t('Edit'), t('Delete'));
    foreach ($plannigFileNodeTitles as $nid => $title) {
      $files = $upload_planning_files[$nid];
      $filename = NULL;
      if (!empty($files)) {
        $oNewFile = File::load($files);
        $filename = $oNewFile->getFilename();
      }

      $edit_url = Url::fromRoute('entity.node.edit_form', ['node' => $nid]);
      $edit_link = Link::fromTextAndUrl(t('Edit'), $edit_url);

      $delete_url = Url::fromRoute('entity.node.delete_form', ['node' => $nid]);
      $delete_link = Link::fromTextAndUrl(t('Delete'), $delete_url);
      $elements = array(
        'title' => $title,
        'filename' => $filename,
        'edit' => $edit_link,
        'delete' => $delete_link,
      );
      $rows[] = $elements;
    }
    if (!isset($elements)) {
      $url = Url::fromRoute('entity.group_content.group_node_add_form', ['group' => RELEASE_MANAGEMENT,'node_type'=>'planning_files']);
      $output[] = array(
        '#title' => t('Create Planning Files'),
        '#type' => 'link',
        '#url' => $url,
      );
      $output[]['#markup'] = t('No Data to be displayed');
      return $output;
    }
    $url = Url::fromRoute('entity.group_content.create_form', ['group' => RELEASE_MANAGEMENT,'plugin_id'=>'group_node:planning_files']);
    $output[] = array(
      '#title' => t('Create Planning Files'),
      '#type' => 'link',
      '#url' => $url,
    );
    $output['planning_files'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#cache' => ['max-age' => 0],
    );
    return $output;
  }
}
