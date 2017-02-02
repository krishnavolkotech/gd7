<?php

namespace Drupal\hzd_customizations\Plugin\Derivative;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 22/12/16
 * Time: 9:45 PM
 */
class MailTemplatesLocalTasks extends DeriverBase implements ContainerDeriverInterface
{
    
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
        $types = ['problem', 'release', 'downtimes', 'planning_files', 'quickinfo','early_warnings'];
        $derivatives = [];
        foreach ($types as $type) {
            $nodeType = \Drupal\node\Entity\NodeType::load($type);
            if($nodeType){
              $this->derivatives['hzd_customizations.mail_templates_' . $type] = [
                  'route_name' => "hzd_customizations.mail_templates",
                  'route_parameters'=>['type'=>$type],
                  'title' => $nodeType->label(),
                  'base_route' => "hzd_customizations.mail_templates_menu",
//                    'weight' => 100,
                ] + $base_plugin_definition;
            }
            
        }
        $groupTypes = ['group', 'group_content'];
        $titles = ['group'=>'Group','group_content'=>'Group Content'];
        $derivatives = [];
        foreach ($groupTypes as $groupType) {
//            $nodeType = \Drupal\node\Entity\NodeType::load($type);
            $this->derivatives['hzd_customizations.mail_templates_' . $groupType] = [
                    'route_name' => "hzd_customizations.group_content_mail_templates",
                    'route_parameters'=>['type'=>$groupType],
                    'title' => $titles[$groupType],
                    'base_route' => "hzd_customizations.mail_templates_menu",
//                    'weight' => 100,
                ] + $base_plugin_definition;
        }
        return $this->derivatives;
    }
}