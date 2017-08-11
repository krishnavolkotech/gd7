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
   * @param Event $event
   */
  public function pdfPreRender(Event $event) {
    $node = $event->getEntities();
    $node = reset($node);
    if($node->bundle() == 'quickinfo'){
      $printEngine = $event->getPrintEngine()->getPrintObject();
      $event->getPrintEngine()->getBlob();
      $printEngine->setPaper(200, 200, 595.28, 841.89);
      $font = $printEngine->getFontMetrics()->get_font("Trebuchet MS");
      $printEngine->getCanvas()->page_text(450, 120, "Seite {PAGE_NUM} von {PAGE_COUNT}", $font, 11, array(0,0,0));
    }
  }

}
