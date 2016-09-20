<?php

namespace Drupal\cust_group\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

class GroupMenuMigrateController extends ControllerBase{

    function menuMigrate(){
        $query = \Drupal::entityQuery('group')
          ->execute();
        //$query = [31=>31,32=>32];
        $groups = \Drupal\group\Entity\Group::loadMultiple($query);
        foreach($groups as $group){
            $data[$group->get('field_old_reference')->value] = $group->id();
        }
        foreach($data as $old=>$new){
            $menuId = 'menu-'.$old;
            $menuItems = \Drupal::entityQuery('menu_link_content')
            ->condition('menu_name',$menuId)
                ->execute();
          foreach($menuItems as $item){
            $menu = MenuLinkContent::load($item);
            //if($item->get('link'))
            $uri = $menu->get('link')->getValue()[0]['uri'];
            if(strpos($uri,'/node/'.$old) !== false){
                $changedUri = str_replace('/node/'.$old,'/group/'.$new,$uri);
                $menu->set('link',['uri'=>$changedUri])->save();
                //echo $menu->get('link')->getValue()[0]['uri'].'<br>';
            }elseif(strpos($uri,'/group/'.$new) !== false){
                if(strpos($uri,'/group/'.$new.'/members')){
                    $menu->set('link',['uri'=>'internal:'.'/group/'.$new.'/address'])->save();
                }
                if(strpos($uri,'/group/'.$new.'/faq')){
                    $newFaqPath = $this->migrateFaqPath($uri);
                    if($newFaqPath){
                        $menu->set('link',['uri'=>'internal:'.$newFaqPath])->save();
                    }
//                    echo 'migrate faq'; pr($uri);pr($newFaqPath);
//                    exit;
                }
            }elseif(count(explode('/',trim(str_replace('internal:/','',$uri),'/'))) == 2){
                $newUri = $this->getGroupNodeUri($uri);
                if($newUri){
                    $menu->set('link',['uri'=>'internal:'.$newUri])->save();
                }
                //echo $uri.'==='.$newUri.'<br>';
            }
            
          }
          
            $menuLink = MenuLinkContent::create([
                'title'      => 'My internal link from a route name',
                'link'       => ['uri' => 'route:myroute'],
                'menu_name'  => 'my-menu',
            ])->save();
        }
        pr($data);exit;
    }
    
    function getGroupNodeUri($oldUri){
        $oldNode = explode('/',trim(str_replace('internal:/','',$oldUri),'/'));
        $node = $oldNode[1];
        $groupData = \Drupal::database()->select('group_content_field_data','gcfd')
            ->fields('gcfd',['gid','id'])->condition('entity_id',$node,'=')->execute()->fetchAssoc();
        if(!empty($groupData)){
            return '/group/'.$groupData['gid'].'/node/'.$groupData['id'];
        }
        return false;
        //pr($groupData);exit;
    }
    
    function migrateFaqPath($oldUri){
        $oldTerms = explode('/',trim(str_replace('internal:/','',$oldUri),'/'));
        if(empty($oldTerms[3])){
            $oldTerms[3] = 'Allgemein';
        }
        $group = \Drupal\group\Entity\Group::load($oldTerms[1]);
        
        $parentTermQuery = \Drupal::entityQuery('taxonomy_term')
            ->condition('name',$group->label())
            ->condition('vid','faq_seite')
            ->execute();
        $termQuery = \Drupal::entityQuery('taxonomy_term')
            ->condition('name',$oldTerms[3])
            ->condition('vid','faq_seite')
            ->execute();
        $tid = null;
        if(empty($parentTermQuery)){
            $tid = array_values($termQuery)[0];
        }
        if($termQuery && $parentTermQuery){
            $tid = \Drupal::database()->select('taxonomy_term_hierarchy','t')
            ->fields('t',['tid'])
            ->condition('parent',$parentTermQuery,'IN')
            ->condition('tid',$termQuery,'IN')
            ->execute()->fetchCol();
        }
        if($tid){
            return "/group/".$oldTerms[1]."/faqs/".$tid[0];
        }
        echo 'migrate faq'; pr($oldUri);
        return false;
    }
}