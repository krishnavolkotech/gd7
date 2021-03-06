<?php

namespace Drupal\cust_group\Controller;


use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\Controller\GroupContentController;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Drupal\Core\Session\AccountProxyInterface;

class GroupContentAddController extends GroupContentController {

  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer, AccountProxyInterface $account) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
    $this->currentUser = $account;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('renderer'),
      $container->get('current_user'),
    );
  }


  public function addPage(GroupInterface $group, $create_mode = FALSE) {
    $build = parent::addPage($group,true);
    if($build instanceof RedirectResponse){
      return $build;
    }
//    $contentTypes = ['page','faqs','newsletter','forum'];
    //removed newsletter content type from content being created
    $contentTypes = ['page','faqs','forum'];
    if($group->id() == QUICKINFO){
      $contentTypes[] = 'quickinfo';
    }
    if($group->id() == RELEASE_MANAGEMENT){
      $contentTypes[] = 'planning_files';
    }
    if($group->id() == PROBLEM_MANAGEMENT){
      $contentTypes[] = 'problem';
    }
    if($group->id() == RISK_MANAGEMENT){
      $contentTypes[] = 'risk';
      $contentTypes[] = 'risk_cluster';
      $contentTypes[] = 'measure';
    }

    // Site-Admin actions.
    if (in_array("site_administrator", $this->currentUser()->getRoles())) {
      $contentTypes[] = 'dir_listing';
    }

    foreach($build['#bundles'] as $key=>$type){
      if(!$this->isContentCreatable($key,$contentTypes)){
//      if((strpos($key,'page') === false) && (strpos($key,'faqs') === false) && (strpos($key,'newsletter') === false)){
//        pr(());exit;
        unset($build['#bundles'][$key]);
      }
    }
    $build['#title'] = $this->t('Create content in %group',['%group'=>$group->label()]);
$this->renderer->addCacheableDependency($build,0);
    return $build;
  }

protected function isContentCreatable($type,$types){
    $contentEnablerManager = \Drupal::service('plugin.manager.group_content_enabler');
    foreach ($types as $val){
      $allPlugins = $contentEnablerManager->getGroupContentTypeIds('group_node:'.$val);
      if(in_array($type, $allPlugins)){
        return true;
      }
    }
    return false;
  }
}
