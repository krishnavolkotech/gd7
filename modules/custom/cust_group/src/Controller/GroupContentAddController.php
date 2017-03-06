<?php

namespace Drupal\cust_group\Controller;


use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\Controller\GroupContentController;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GroupContentAddController extends GroupContentController {
  
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
  }
  
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('renderer')
    );
  }
  
  
  public function addPage(GroupInterface $group, $create_mode = FALSE) {
    $build = parent::addPage($group,true);
    $contentTypes = ['page','faqs','newsletter'];
    if($group->id() == QUICKINFO){
      $contentTypes[] = 'quickinfo';
    }
    if($group->id() == RELEASE_MANAGEMENT){
      $contentTypes[] = 'planning_files';
    }
    foreach($build['#bundles'] as $key=>$type){
      if($this->isContentCreatable($key,$contentTypes)){
//      if((strpos($key,'page') === false) && (strpos($key,'faqs') === false) && (strpos($key,'newsletter') === false)){
//        pr(());exit;
        unset($build['#bundles'][$key]);
      }
    }
    $build['#title'] = $this->t('Create content in %group',['%group'=>$group->label()]);
    return $build;
  }
  
  function isContentCreatable($type,$types){
    $count = 0;
    foreach ($types as $val){
      if(strpos($type,$val) === false){
        $count++;
      }
    }
    return count($types) == $count;
  }
}