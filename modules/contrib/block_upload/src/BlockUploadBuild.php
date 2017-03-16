<?php

namespace Drupal\block_upload;
use Drupal\file\Entity\File;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Utility\Bytes;
use Drupal\user\Entity\User;

class BlockUploadBuild {

  /**
   * Builds form for removing the files with block.
   */
  public static function blockUploadRemoveForm($field_limit, $node, $field_name) {
    $field_images = $node->get($field_name);
    foreach ($field_images->referencedEntities() as $file) {
      $uid = $file->get('uid')->target_id;
      $uploader = User::load($uid);
      global $base_url;
      $url = Url::fromUri($base_url . '/user/' . $uid, array());
      $uploader =  $uploader ? Link::fromTextAndUrl($uploader->getDisplayName(), $url) : '';
      $options[$file->id()] = array(
        array(
          'data' => array(
            '#type' => 'item',
            '#title' => $uploader ? $uploader->toString()->getGeneratedLink() : '',
          ),
        ),
        array(
          'data' => array(
            '#type' => 'item',
            '#title' => \Drupal::service('date.formatter')->format($file->getCreatedTime()),
          ),
        ),
        array(
          'data' => array(
            '#theme' => 'file_link',
            '#file' => (object) $file,
          ),
          'field_type' => $field_limit->getType(),
        ),
      );

    }
    $header = array(t('Uploader'), t('Created time'), t('File'));
    $form = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No content available.'),
      '#attributes' => array('class' => array('delete-files')),
    );
    return $form;
  }

  /**
   * Returns destinaton for file upload.
   *
   * @return string
   *   Destination path.
   */
  public static function blockUploadGetUploadDestination($field) {
    if ($destination = $field->getSetting('file_directory')) {
      if (\Drupal::request()->attributes->has('node')) {
        $node = \Drupal::request()->attributes->get('node');
      }
      $token = \Drupal::token();
      $destination = $token->replace($destination, array('node' => $node));
    }
    $field_info = FieldStorageConfig::loadByName($field->get('entity_type'), $field->getName());
    $uri_scheme = $field_info->getSetting('uri_scheme');
    if (!$uri_scheme) {
      $uri_scheme = 'public';
    }
    $destination = $uri_scheme . '://' . $destination;
    file_prepare_directory($destination, FILE_CREATE_DIRECTORY);
    return $destination;
  }

  /**
   * Deletes files marked by checkbox in deletion form.
   */
  public static function blockUploadDeleteFiles($node, $field_name, &$values) {
    $delete_files = array_values($values['remove_files']);
    $count = 0;
    foreach ($node->get($field_name)->getValue() as $file_field) {
      if (in_array($file_field['target_id'], $delete_files)) {
        $node->get($field_name)->removeItem($count);
        file_delete($file_field['target_id']);
      }
      else {
        $count++;
      }

    }
    drupal_set_message(t('File(s) was successfully deleted!'));
  }

  /**
   * Returns validators array.
   *
   * @return array
   *   List of validators.
   */
  public static function blockUploadGetValidators($field_name, $fields_info, $node) {
    $settings = $node->get($field_name)->getSettings();
    $validators = array(
      'file_validate_extensions' => array($settings['file_extensions']),
      'file_validate_size' => array(Bytes::toInt($settings['max_filesize'])),
    );
    $min_resolution = isset($settings['min_resolution']) ? $settings['min_resolution'] : FALSE;
    $max_resolution = isset($settings['max_resolution']) ? $settings['max_resolution'] : FALSE;
    if (isset($min_resolution) || isset($min_resolution)) {
      $validators['file_validate_image_resolution'] = array($max_resolution, $min_resolution);
      $validators['file_validate_image_min_resolution'] = array($min_resolution);
    }
    return $validators;
  }

}

