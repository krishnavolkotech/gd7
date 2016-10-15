<?php
/**
 * @file
 * Contains \Drupal\cust_group\Plugin\Block\ITSMProzesseMenuBlock.
 */
namespace Drupal\cust_group\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\group\Entity\Group;
/**
 * Provides a 'cust_group' block.
 *
 * @Block(
 *   id = "itsmprozesse_menu_block",
 *   admin_label = @Translation("ITSM Prozesse Menu block"),
 *   category = @Translation("Custom Group")
 * )
 */
class ITSMProzesseMenuBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $groups = [24,26,31,32,34,4];
    $groupEntities = Group::loadMultiple($groups);
    foreach($groupEntities as $group){
      $menuItems[] = $group->toLink();
    }
    //$menuItems[] = \Drupal\Core\Link::createFromRoute(t('Contents'),'view.group_content.page_1',['arg_0'=>$groupId]);
    //$menuHtml = '<ul class="menu nav">
    //<li><a href="/group/24">Incident Management</a></li>
    //<li><a href="/group/26">Capacity Management</a></li>
    //<li><a href="/group/31">Problem Management</a></li>
    //<li><a href="/group/32">Release Management</a></li>
    //<li><a href="/group/34">Service Level Management</a></li>
    //<li><a href="/group/4">Betriebsportal KONSENS</a></li>
    //</ul>';
    $menu = [
            //'#title'=>$this->t("ITSM-Prozesse"),
            '#items'=>$menuItems,
            '#theme'=>'item_list',
            '#list_type'=>'ul',
            '#attributes'=>['class'=>['menu nav']],
            ];
    return $menu;
  }
}
