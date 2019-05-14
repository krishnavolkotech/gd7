<?php

namespace Drupal\hzd_customizations\Plugin\Derivative;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 22/12/16
 * Time: 9:45 PM
 */
class MailTemplatesLocalTasks extends DeriverBase implements ContainerDeriverInterface {
  
  use StringTranslationTrait;
  
  public function __construct(\Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }
  
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $types = [
      'problem',
      'release',
      'downtimes',
      'planning_files',
      'quickinfo',
      'early_warnings',
      'release_comments',
      'group',
      'group_content',
    ];
    $derivatives = [];
    $typeLabels = [
      'group' => $this->t('Group'),
      'group_content' => $this->t('Group Content')
    ];
    
    foreach ($types as $type) {
      if (array_key_exists($type, $typeLabels)) {
        $title = $typeLabels[$type];
      }
      else {
        $nodeType = NodeType::load($type);
        if ($nodeType) {
          $title = $nodeType->label();
        }
      }
      
      if ($nodeType) {
        $this->derivatives['hzd_customizations.mail_templates_' . $type] = [
            'route_name' => "hzd_customizations.mail_templates",
            'route_parameters' => ['type' => $type],
            'title' => $title,
            'base_route' => "hzd_customizations.mail_templates_menu",
//                    'weight' => 100,
          ] + $base_plugin_definition;
      }
      
    }

    $this->derivatives['hzd_customizations.mail_templates_arbeitsanleitungen'] = [
      'route_name' => "hzd_notifications.aledv_notification_form",
      'title' => $this->t("AL-EDV"),
      'base_route' => "hzd_customizations.mail_templates_menu",
    ];
    return $this->derivatives;
  }
}