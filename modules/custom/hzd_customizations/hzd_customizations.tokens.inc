<?php

/**
 * @file
 * Builds placeholder replacement tokens for group-related data.
 */

use Drupal\group\Entity\Group;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupContent;

/**
 * Implements hook_tokens().
 */
function hzd_customizations_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $token_service = \Drupal::token();
    
    $url_options = array('absolute' => TRUE);
    if (isset($options['langcode'])) {
        $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
        $langcode = $options['langcode'];
    } else {
        $langcode = NULL;
    }
    $replacements = array();
    
    if ($type == 'group') {
        $group = $data['group'] ? : '';
        if(empty($group)){
            $group = \Drupal::routeMatch()->getParameter('group');
            if(!empty($group) && !is_object($group)){
                $group = \Drupal\group\Entity\Group::load($group);
            }
        }
        if (empty($group)) {
            $node = $data['node'];
//            $group = null;
            $groupContentId = \Drupal::entityQuery('group_content')
                ->condition('type', '%group_node%', 'LIKE')
                ->condition('entity_id', $node->id())
                ->execute();
            $groupContent = GroupContent::load(reset($groupContentId));
            if ($groupContent)
                $group = $groupContent->getGroup();
        }
        // Getting required values for group title.
//    $group = \Drupal::routeMatch()->getParameter('group');
//
//    if (!isset($group)) {
//      $node = \Drupal::routeMatch()->getParameter('node');
//
//      $groupId = \Drupal::database()->select('group_content_field_data', 'gcfd')
//        ->fields('gcfd', ['gid'])
//        ->condition('gcfd.entity_id', $node->id())
//        ->execute()->fetchField();
//
//      $group = Group::load($groupId);
//    }
        
        foreach ($tokens as $name => $original) {
            switch ($name) {
                case 'title':
                    $label = \Drupal::service('pathauto.alias_cleaner')->cleanString($group->label());
                    $replacements[$original] = isset($group) ? $label : '';
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

/**
 * Implements hook_tokens_alter().
 */
function hzd_customizations_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {
    if ($context['type'] == 'node' && !empty($context['data']['node'])) {
        if (isset($context['tokens']['field_author_state']) && !$replacements[$context['tokens']['field_author_state']]) {
            $replacements[$context['tokens']['field_author_state']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_unique_id']) && !$replacements[$context['tokens']['field_unique_id']]) {
            $replacements[$context['tokens']['field_unique_id']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_custom_title']) && !$replacements[$context['tokens']['field_custom_title']]) {
            $replacements[$context['tokens']['field_custom_title']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_related_transfer_number']) && !$replacements[$context['tokens']['field_related_transfer_number']]) {
            $replacements[$context['tokens']['field_related_transfer_number']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_other_services']) && !$replacements[$context['tokens']['field_other_services']]) {
            $replacements[$context['tokens']['field_other_services']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_intial_situation']) && !$replacements[$context['tokens']['field_intial_situation']]) {
            $replacements[$context['tokens']['field_intial_situation']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_professional_conditions']) && !$replacements[$context['tokens']['field_professional_conditions']]) {
            $replacements[$context['tokens']['field_professional_conditions']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_tech_requirements']) && !$replacements[$context['tokens']['field_tech_requirements']]) {
            $replacements[$context['tokens']['field_tech_requirements']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_depend_other_services']) && !$replacements[$context['tokens']['field_depend_other_services']]) {
            $replacements[$context['tokens']['field_depend_other_services']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_business_impact']) && !$replacements[$context['tokens']['field_business_impact']]) {
            $replacements[$context['tokens']['field_business_impact']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_technical_impact']) && !$replacements[$context['tokens']['field_technical_impact']]) {
            $replacements[$context['tokens']['field_technical_impact']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_business_impact_services']) && !$replacements[$context['tokens']['field_business_impact_services']]) {
            $replacements[$context['tokens']['field_business_impact_services']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_technical_impact_services']) && !$replacements[$context['tokens']['field_technical_impact_services']]) {
            $replacements[$context['tokens']['field_technical_impact_services']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_special_notes']) && !$replacements[$context['tokens']['field_special_notes']]) {
            $replacements[$context['tokens']['field_special_notes']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_dates']) && !$replacements[$context['tokens']['field_dates']]) {
            $replacements[$context['tokens']['field_dates']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_creators']) && !$replacements[$context['tokens']['field_creators']]) {
            $replacements[$context['tokens']['field_creators']] = 'Keine Angabe';
        }
        if (isset($context['tokens']['field_contact']) && !$replacements[$context['tokens']['field_contact']]) {
            $replacements[$context['tokens']['field_contact']] = 'Keine Angabe';
        }
    }
}
