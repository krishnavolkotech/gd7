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
//    exit;
      $printEngine = $event->getPrintEngine()->getPrintObject();
//      $printEngine->configuration['default_paper_size'] = [200, 20, 595.28, 841.89];
      
//      $printEngine->set_option('isPhpEnabled',1);
//      $dom_pdf = $pdf->getDomPDF();
//
    $event->getPrintEngine()->doRender();
//    $node = $event->getEntities();
//    pr($node);exit;
//    $printEngine->setPaper(200, 200, 595.28, 841.89);
    $font = $printEngine->getFontMetrics()->get_font("helvetica", "bold");
    $printEngine->getCanvas()->page_text(22, 18, "Header: {PAGE_NUM} of {PAGE_COUNT}", $font, 10, array(0,0,0));

//    drupal_set_message('Event entity_print.print.html_alter thrown by Subscriber in module cust_pdf.', 'status', TRUE);
  }

}
