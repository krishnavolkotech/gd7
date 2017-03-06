<?php

namespace Drupal\cust_pdf\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\entity_print\Event\PrintEvents;

/**
 * Class PdfBeforeRenderSubscriber.
 *
 * @package Drupal\cust_pdf
 */
class PdfBeforeRenderSubscriber implements EventSubscriberInterface {
    

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[PrintEvents::PRE_SEND] = ['pdfPreRender'];

    return $events;
  }

  /**
   * This method is called whenever the entity_print.print.html_alter event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function pdfPreRender(Event $event) {
    $node = reset($event->getEntities());
    if($node->bundle() == 'quickinfo'){
      $printEngine = $event->getPrintEngine()->getPrintObject();
      $event->getPrintEngine()->doRender();
      $printEngine->setPaper(200, 200, 595.28, 841.89);
      $font = $printEngine->getFontMetrics()->get_font("robotoregular");
      $printEngine->getCanvas()->page_text(460, 140, "Siete {PAGE_NUM} von {PAGE_COUNT}", $font, 12, array(0,0,0));
    }
  }

}
