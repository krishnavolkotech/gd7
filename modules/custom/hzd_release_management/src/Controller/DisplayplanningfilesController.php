<?php

namespace Drupal\hzd_release_management\Controller;

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
        $link = \Drupal\Core\Link::fromTextAndUrl(t('Home'), $url);
        $breadcrumb[] = $link;
        $url = Url::fromUserInput('/release-management');
        $link = \Drupal::l(t('Deployed Releases'), $url);
        $breadcrumb[] = $link;
        $breadcrumb[] = t('Deployed Releases');
        $request = \Drupal::request();
        $route_match = \Drupal::routeMatch();
        $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
        $breadcrumb[] = $title;
        $output['#breadcrumb'] = $breadcrumb;

        $query = \Drupal::database()->select('node_field_data', 'nfd');
        $query->fields('nfd', array('title', 'nid'));
        $query->condition('nfd.type', 'planning_files', '=');
        $planning_files = $query->execute()->fetchAll();

        $header = array(t('Title'), t('Filename'), t('Edit'), t('Delete'));

        foreach ($planning_files as $list_files) {
            $query = \Drupal::database()->select('file_managed', 'fm');
            $query->addfield('fm', 'filename');
            $query->leftjoin('node__field_upload_planning_file', 'nfupf', 'fm.fid = nfupf.field_upload_planning_file_target_id');
            $query->condition('nfupf.entity_id', $list_files->nid, '=');
            $filename = $query->execute()->fetchField();
        
            $url = Url::fromUserInput('/node/' . $list_files->nid . '/edit');
            $edit = \Drupal::service('link_generator')->generate('Edit', $url);
            $url = Url::fromUserInput('/node/' . $list_files->nid . '/delete');
            $delete = \Drupal::service('link_generator')->generate('Delete', $url);

            $elements = array(
              'title' => $list_files->title,
              'filename' => $filename,
              'edit' => $edit,
              'delete' => $delete,
            );
            $rows[] = $elements;
        }
        if (!isset($elements)) {
            $url = Url::fromUserInput('/release-management/add/planning-files');
            $output[] = array('#title' => t('Create Planning Files'),
              '#type' => 'link',
              '#url' => $url,
              /**
              'attributes' => array(
                'class' => 'planning-files-link'
              ),
               */
            );

            $output[]['#markup'] = t('No Data to be displayed');

            return $output;
        }
        $url = Url::fromUserInput('/release-management/add/planning-files');
        $output[] = array('#title' => t('Create Planning Files'),
          '#type' => 'link',
          '#url' => $url,
          /**
          'attributes' => array(
            'class' => 'planning-files-link'
          ),
          */
        );

        $output['planning_files'] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        );
        return $output;
    }

}
