<?php
/**
 * @file
 * Contains Drupal\favorites\Plugin\Block\FavBlock.
 */
namespace Drupal\favorites\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\favorites\FavoriteStorage;
use Drupal\Core\Url;
use \Drupal\Core\Link;
/**
 * Provides a 'favorites' block.
 *
 * @Block(
 *   id = "favorites_block",
 *   admin_label = @Translation("My Favorites"),
 * )
 */

class FavBlock extends BlockBase {
  
  /**
   * {@inheritdoc}
   */
    protected function blockAccess(AccountInterface $account) {
        return AccessResult::allowedIfHasPermission($account, 'manage favorites');
    }
  
  /**
   * {@inheritdoc}
   */
  public function build() {  
    global $base_url;
    $form = \Drupal::formBuilder()->getForm('Drupal\favorites\Form\AddForm');
    $items = array();
    $account = \Drupal::currentUser(); 
    $uid = $account->id();
    if ($uid) {
            $result = FavoriteStorage::getFavorites($uid);
            $i = 0;
            if($result){
              foreach ($result as $favorite) {
                $favorite->path = \Drupal::service('path_alias.manager')->getAliasByPath('/'.trim($favorite->path,'/'));
                if($favorite->query != ''){
                    $url = $base_url.$favorite->path.'?'.$favorite->query;
                }
                else{
                    $url = $base_url.$favorite->path;
                }
                $url = Url::fromUri($url);
                $url_delete = Url::fromRoute('favorites.remove',['fid'=>$favorite->fid]);
                $items[$i]['title_link'] = Link::fromTextAndUrl($favorite->title, $url);
                $items[$i]['remove_link'] = Link::fromTextAndUrl('X', $url_delete);
                $items[$i]['id'] = $favorite->fid;
                $i++;
            }
          }
    }
    return array(     
      '#attached' => array(
          'library' => array('favorites/favorites.custom'),
      ),  
      'fav_lists' => array(  
        '#theme' => 'favlist_item_list',       
        'items' => $items,  
      ),
        '#cache' => array(
         'contexts' => array(
         'user',
        ),
      ),
     // 'add_this_page' => $form,   
    );   	
	
  }
    
  
  
}
