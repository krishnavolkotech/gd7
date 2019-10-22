<?php

namespace Drupal\cust_group\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'FragebogenFilesUploadBlock' block.
 *
 * @Block(
 *  id = "fragebogen_files_upload_block",
 *  admin_label = @Translation("Fragebogen Release Management Files Upload Block"),
 * )
 */
class FragebogenFilesUploadBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
      $result['fragebogen_upload_form'] = \Drupal::formBuilder()->getForm(
        'Drupal\cust_group\Form\FragebogenUploadForm');
      $result['fragebogen_upload_form']['#prefix'] = '<div class = "im-attachment-filesupload-form-wrapper">';
      $result['fragebogen_upload_form']['#suffix'] = '</div><div id="im-attachment-files-list">';
      

      $query = \Drupal::database()->select('node_field_data', 'n');
      $query->join('node__field_upload', 'nfu', 'n.nid = nfu.entity_id');
      $query->leftjoin('node__field_un_zip_status', 'nfz', 'nfz.entity_id = n.nid');
      $query->condition('n.type', 'fragebogen_upload', '=');
      $query->condition('nfz.field_un_zip_status_value', 0, '=');
      $query->fields('nfu', ['field_upload_target_id']);
      $query->fields('n', ['nid']);
      $files_data = $query->execute()->fetchAll();
      
      $publicFiles = \Drupal::service('file_system')->realpath("public://");
      $unzipFolder = $publicFiles . '/release-fragebogen/';
      
      foreach($files_data as $data) {
        $this->unzip_release_fragebogen_files($unzipFolder, $data->nid, $data->field_upload_target_id);
      }
      $files = $this->release_fragebogen_files($unzipFolder);
      $rows = [];
      $currentUser = \Drupal::currentUser();
      $releaseManagement = \Drupal\group\Entity\Group::load(RELEASE_MANAGEMENT);
      $releaseManagementGroupMember = $releaseManagement->getMember($currentUser);
      
      if ($releaseManagementGroupMember) {
        foreach ($files as $file) {
          if ($file) {
            $row = array(
              array('data' => $file['file'], 'class' => 'state-cell'),
              array('data' => date('d.m.Y H:i:s',$file['time']), 'class' => 'filename-cell'),
            );
            $rows[] = $row;
          }
        }
      }
      
      $result['files'] = [
        '#type' => 'table',
        '#attributes' => ['class' => ['files']],
        '#suffix' => '</div>',
        '#rows' => $rows,
        '#empty' => t('No files available.'),
        '#header' => [
          $this->t('Dateiname'),
          $this->t('Dateidatum'),
        ],
      ];
      return $result;
    }

    public function unzip_release_fragebogen_files($unzipFolder, $nid, $fid) {
      if ($fid) {
        $file = \Drupal\file\Entity\File::load($fid);
        if ($file) {
          $path = $file->getFileUri();
          if ($path && file_exists($path) && is_dir($unzipFolder)) {
            $realFilesPath = \Drupal::service('file_system')->realpath($path);
            shell_exec("unzip -o " . $realFilesPath . " -d " . $unzipFolder);
          }
          
          if ($nid) {
            $node = \Drupal\node\Entity\Node::load($nid);
            $node->set('field_un_zip_status', 1);
            $node->save();
          }
        }
      }
    }
    
    public function release_fragebogen_files($unzipFolder) {
      if ($dh = opendir($unzipFolder)) {
        while (($fileName = readdir($dh)) !== false) {
          $stat = stat($fileName);
          if (file_exists($unzipFolder. '/' .$fileName) && $fileName != '.' && $fileName != '..') {
              $time = filectime($unzipFolder. '/' .$fileName);
              $path = "public:///release-fragebogen/". $fileName;
              $file_downloadable_link = file_create_url($path);
              $url = \Drupal\Core\Url::fromUri($file_downloadable_link, array('attributes' => array('target' => '_blank')));
              $file_link = \Drupal\Core\Link::fromTextAndUrl($fileName, $url)->toString();
              $files[] = ['file' => $file_link, 'time' => $time];
          }
        }
        closedir($dh);
        usort($files, 'filesCompareByName');
      }
      return $files;
    }

    public function access(AccountInterface $account, $return_as_object = FALSE) {
      $access = parent::access($account, $return_as_object);
      $routeMatch = Drupal::routeMatch();
      if (in_array($routeMatch->getRouteName(), [
          'entity.node.edit_form',
          'node.add'
      ])) {
          return AccessResult::forbidden();
      }
      return $access;
    }
}
