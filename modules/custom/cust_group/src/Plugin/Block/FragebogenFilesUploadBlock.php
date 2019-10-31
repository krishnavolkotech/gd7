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
      $currentUser = \Drupal::currentUser();
      $releaseManagement = \Drupal\group\Entity\Group::load(RELEASE_MANAGEMENT);
      $releaseManagementGroupMember = $releaseManagement->getMember($currentUser);
      $is_group_admin = \Drupal\cust_group\Controller\CustNodeController::isGroupAdmin(RELEASE_MANAGEMENT);

      if (in_array('site_administrator', $currentUser->getRoles()) || $currentUser->id() == 1) {
          $showUpload = TRUE;
      }
      else if ($is_group_admin) {
          $showUpload = TRUE;
      }
      else {
          $showUpload = FALSE;
      }

      if ($showUpload) {
        $result['fragebogen_upload_form'] = \Drupal::formBuilder()->getForm('Drupal\cust_group\Form\FragebogenUploadForm');
        $result['fragebogen_upload_form']['#prefix'] = '<div class = "fragebogen-filesupload-form-wrapper">';
        $result['fragebogen_upload_form']['#suffix'] = '</div><div id="fragebogen-files-list">';
      }

      $result['fragebogen_upload_filter_element'] = \Drupal::formBuilder()->getForm(
          '\Drupal\cust_group\Form\FragebogenUploadFilterForm');
      $result['fragebogen_upload_filter_element']['#prefix'] = '<div class = "fragebogen-filter-form-wrapper">';
      $result['fragebogen_upload_filter_element']['#suffix'] = '</div>';


      $publicFiles = \Drupal::service('file_system')->realpath("public://");
      $zipUploadedDir = $publicFiles . '/release-fragebogen/';
      checkAndCreateFolder($zipUploadedDir);
      
      $privateFiles = \Drupal::service('file_system')->realpath("private://");
      $unzipFolder = $privateFiles . '/release-fragebogen/';
      checkAndCreateFolder($unzipFolder);
      
      
      $this->unzip_release_fragebogen_files($unzipFolder, $zipUploadedDir);

      $filterData = \Drupal::request()->query->all();
      $fileName = isset($filterData['filename']) && $filterData['filename'] != '' ? $filterData['filename'] : NULL;

      $files = $this->release_fragebogen_files($unzipFolder, $fileName);
      $rows = [];
      
      if ($releaseManagementGroupMember && count($files) > 0) {
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
        '#cache'=>['max-age' => 0],
        '#header' => [
          $this->t('Filename'),
          $this->t('Date'),
        ],
      ];
      return $result;
    }

    /**
     * Unzip the uploaded files into private files directory
     */
    public function unzip_release_fragebogen_files($unzipFolder, $zipUploadedDir) {
      if ($dh = opendir($zipUploadedDir)) {
        while (($fileName = readdir($dh)) !== false) {
          if ($fileName != '.' && $fileName != '..') {
            $uploadedFiles = $zipUploadedDir . '' . $fileName;
            $rpath = preg_replace('/\W/', '\\\\$0', $uploadedFiles);
            shell_exec("unzip -o " . $rpath . " -d " . $unzipFolder);
            shell_exec("rm " . $rpath);
          }
        }
        closedir($dh);
      }
      else {
        drupal_set_message($this->t('Fragebogen Upload Directory Does Not Exists.'), 'error');
      }
    }

    /**
     * Listing the files from Private files directory
     */
    public function release_fragebogen_files($unzipFolder, $searchFileName = NULL) {
      $files = [];
      if ($dh = opendir($unzipFolder)) {
        while (($fileName = readdir($dh)) !== false) {
          if (file_exists($unzipFolder. '/' .$fileName) && $fileName != '.' && $fileName != '..') {
              $time = filemtime($unzipFolder. '/' .$fileName);
              $path = "private://release-fragebogen/". $fileName;
              $file_downloadable_link = file_create_url($path);
              $url = \Drupal\Core\Url::fromUri($file_downloadable_link, array('attributes' => array('target' => '_blank')));
              $file_link = \Drupal\Core\Link::fromTextAndUrl($fileName, $url)->toString();

              if (isset($searchFileName)) {
                  if (stripos($fileName, $searchFileName) !== FALSE) {
                      $files[] = ['file' => $file_link, 'time' => $time];
                  }
              }
              else {
                  $files[] = ['file' => $file_link, 'time' => $time];    
              }
                            
          }
        }
        closedir($dh);
        usort($files, 'filesCompareByName');
      }
      else {
        drupal_set_message($this->t('Fragebogen Unzip Directory Does Not Exists.'), 'error');
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
