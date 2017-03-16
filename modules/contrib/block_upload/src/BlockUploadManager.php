<?php

namespace Drupal\block_upload;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;

class BlockUploadManager {

  public static function blockUploadBuildBlockContent($block_id) {
    $block = array();
    if (\Drupal::request()->attributes->has('node')) {
      $node = \Drupal::request()->attributes->get('node');
    }
    $field_name = \Drupal::state()->get('block_upload_' . $block_id . '_field') ?: '';
    $field = FieldStorageConfig::loadByName(explode('.', $field_name)[0], explode('.', $field_name)[1]);
    if (!empty($field->getBundles())) {
      foreach ($field->getBundles() as $bundle) {
        if (isset($node)) {
          if ($bundle == $node->getType()) {
            $settings = \Drupal::state()->get('block_upload_' . $block_id . '_settings' ?: array());
            // Simple file upload form.
            if ($settings['plupload']) {
              $block = \Drupal::formBuilder()->getForm('Drupal\block_upload\Form\BlockUploadPluploadForm', $node, $block_id);
            }
            else {
              $block = \Drupal::formBuilder()->getForm('Drupal\block_upload\Form\BlockUploadForm', $node, $block_id);
            }
          }
        }
      }
    }
    return $block;
  }

  /**
   * Returns avaliable field list of filefield type.
   *
   * @return array
   *   Field list.
   */
  public static function blockUploadGetFieldList() {
    $fields = array();
    $results = \Drupal::entityQuery('field_storage_config')->execute();
    foreach ($results as $result) {
      $field = FieldStorageConfig::loadByName(explode('.', $result)[0], explode('.', $result)[1]);
      if ($field->getType() == 'image' || $field->getType() == 'file') {
        $fields[$result] = $result;
      }
    }
    return $fields;
  }

  /**
   * Dinamic form elements for image/file field types.
   *
   * @param int $buid
   *   Block upload variable ID.
   * @param string $type
   *   Field type.
   */
  public static function blockUploadFieldOptionsFormElements(&$form, $buid, $type) {
    $form['config'] = array(
      '#title' => t("Additional display"),
      '#description' => t('Alt and title fields display for single form display. Will not apply for plupload widget.'),
      '#prefix' => '<div id="config">',
      '#suffix' => '</div>',
      '#type' => 'fieldset',
    );
    $settings = \Drupal::state()->get('block_upload_' . $buid . '_settings') ?: array();
    // Alt and title enable options form elements for image field type.
    if ($type == 'image') {
      $form['config']['block_upload_' . $buid . '_alt'] = array(
        '#type' => 'checkbox',
        '#title' => t('Show alt field'),
        '#default_value' => isset($settings['alt']) ? $settings['alt'] : 0,
      );
      $form['config']['block_upload_' . $buid . '_title'] = array(
        '#type' => 'checkbox',
        '#title' => t('Show title field'),
        '#default_value' => isset($settings['title']) ? $settings['title'] : 0,
      );
    }
    // Description enable option form element for file field type.
    elseif ($type == 'file') {
      $form['config']['block_upload_' . $buid . '_desc'] = array(
        '#type' => 'checkbox',
        '#title' => t('Show description field'),
        '#default_value' => isset($settings['desc']) ? $settings['desc'] : 0,
      );
    }
    // Check if plupload module exists and display enable option.
    if (\Drupal::moduleHandler()->moduleExists('plupload')) {
      $form['block_upload_' . $buid . '_plupload_status'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use Plupoad for file uploads'),
        '#default_value' => isset($settings['plupload']) ? $settings['plupload'] : 0,
      );
    }
    else {
      $description = t('To enable multiuploads and drag&drop upload features, download and install Plupload module');
      $form['block_upload_' . $buid . '_plupload_status'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use Plupoad for file uploads'),
        '#disabled' => TRUE,
        '#description' => $description,
      );
    }
  }

}

