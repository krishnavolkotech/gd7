<?php



namespace Drupal\hzd_notifications\Controller;
use Drupal\Core\Controller\ControllerBase;


class NotificationsCronController extends ControllerBase{
    
    
    public function dailyCron(){
        return ['#type'=>'markup','#markup'=>'hello'];
    }
}