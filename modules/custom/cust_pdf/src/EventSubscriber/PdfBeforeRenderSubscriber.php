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
      $printEngine = $event->getPrintEngine()->getDomPdf();
//      $printEngine->configuration['default_paper_size'] = [200, 20, 595.28, 841.89];
      $printEngine->setPaper([0, 20, 595.28, 841.89]);
      
    drupal_set_message('Event entity_print.print.html_alter thrown by Subscriber in module cust_pdf.', 'status', TRUE);
  }

}
