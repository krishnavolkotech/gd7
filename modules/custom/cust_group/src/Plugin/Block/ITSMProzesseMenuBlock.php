<?php
/**
 * @file
 * Contains \Drupal\cust_group\Plugin\Block\ITSMProzesseMenuBlock.
 */
namespace Drupal\cust_group\Plugin\Block;
use Drupal\Core\Block\BlockBase;
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
    $menuHtml = '<ul class="menu nav">
    <li><a href="/group/24">Incident Management</a></li>
    <li><a href="/group/26">Capacity Management</a></li>
    <li><a href="/group/31">Problem Management</a></li>
    <li><a href="/group/32">Release Management</a></li>
    <li><a href="/group/34">Service Level Management</a></li>
    <li><a href="/group/4">Betriebsportal KONSENS</a></li>
    </ul>';
    $title = "ITSM-Prozesse";
    return ['#title'=>$this->t($title),'#markup'=>$this->t($menuHtml)];
  }
}
