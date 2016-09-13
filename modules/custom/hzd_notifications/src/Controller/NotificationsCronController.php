<?php



namespace Drupal\hzd_notifications\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class NotificationsCronController extends ControllerBase{
    
    
    public function dailyCron(){
      $nodes = \Drupal::entityQuery('node')
              ->condition('changed',time()-24*60*60,'>')
              ->execute();
      pr($nodes);exit;
      pr(time());
      pr(Node::load(347)->get('changed')->value);exit;
        return ['#type'=>'markup','#markup'=>'hello'];
    }
}