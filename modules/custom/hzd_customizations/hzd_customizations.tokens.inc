<?php

/**
 * @file
 * Builds placeholder replacement tokens for group-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_tokens().
 */
function hzd_customizations_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = array();

  if ($type == 'group') {

    // Getting required values for group title
    $group = \Drupal::routeMatch()->getParameter('group');

    if(!isset($group)){
      $node = \Drupal::routeMatch()->getParameter('node');

      $groupId = \Drupal::database()->select('group_content_field_data','gcfd')
       ->fields('gcfd',['gid'])
       ->condition('gcfd.entity_id',$node->id())
       ->execute()->fetchField();

       $group = \Drupal\group\Entity\Group::load($groupId);
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'title':
          $replacements[$original] = isset($group) ? str_replace(' ', '', $group->label()) : '';
          break;
      }
    }
  }
  return $replacements;
}

/**
 * Implements hook_token_info().
 */
function hzd_customizations_token_info() {
  $type = array(
    'name' => t('Groups'),
    'description' => t('Tokens related to individual groups.'),
    'needs-data' => 'group',
  );

  // Core tokens for group.
  $group['title'] = array(
    'name' => t("Title"),
  );

  return array(
    'types' => array('group' => $type),
    'tokens' => array('group' => $group),
  );
}
