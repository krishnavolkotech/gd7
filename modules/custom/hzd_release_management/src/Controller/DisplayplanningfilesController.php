<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

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

/*        $query = \Drupal::database()->select('node_field_data', 'nfd');
        $query->fields('nfd', array('title', 'nid'));
        $query->condition('nfd.type', 'planning_files', '=');
        $planning_files = $query->execute()->fetchAll();*/
        
        $plannigFileNodeIds = \Drupal::entityQuery('node')
            ->condition('type','planning_files')
            ->execute();
        $planningFileNodes = \Drupal\node\Entity\Node::loadMultiple(array_values($plannigFileNodeIds));

        $header = array(t('Title'), t('Filename'), t('Edit'), t('Delete'));

        foreach ($planningFileNodes as $node) {
            $files = $node->get('field_upload_planning_file')->referencedEntities();
                      
            //pr($files[0]->getFileUri());exit;
            /*$query = \Drupal::database()->select('file_managed', 'fm');
            $query->addfield('fm', 'filename');
            $query->leftjoin('node__field_upload_planning_file', 'nfupf', 'fm.fid = nfupf.field_upload_planning_file_target_id');
            $query->condition('nfupf.entity_id', $list_files->nid, '=');
            $filename = $query->execute()->fetchField();*/
            $filename = null;
            if(!empty($files[0])){
                $fileUrl = $files[0]->url();
                $filename = Link::fromTextAndUrl($files[0]->getFilename(),  Url::fromUri($fileUrl));
            }
            //$url = Url::fromUserInput('/node/' . $node->id() . '/edit');
            //$edit = \Drupal::service('link_generator')->generate('Edit', $url);
            //$url = Url::fromUserInput('/node/' . $node->id() . '/delete');
            //$delete = \Drupal::service('link_generator')->generate('Delete', $url);

            $elements = array(
              'title' => $node->label(),
              'filename' => $filename,
              'edit' => \Drupal::l(t('Edit'), Url::fromRoute('entity.node.edit_form', ['node' => $node->id()])),
              'delete' => \Drupal::l(t('Delete'), Url::fromRoute('entity.node.delete_form', ['node' => $node->id()])),
            );
            $rows[] = $elements;
        }
        if (!isset($elements)) {
            $url = Url::fromRoute('entity.group_content.group_node__planning_files.create_form',['group'=>32]);
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
        $url = Url::fromRoute('entity.group_content.group_node__planning_files.create_form',['group'=>32]);
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
