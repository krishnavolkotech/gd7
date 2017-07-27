<?php

namespace Drupal\cust_pdf\EventSubscriber;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Render\Element;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use \Drupal\Component\Utility\NestedArray;

/**
 * Class RenderPdfSubscriber.
 *
 * @package Drupal\cust_pdf
 */
class RenderPdfSubscriber implements EventSubscriberInterface {
  
  /**
   * Constructor.
   */
  public function __construct() {
  
  }
  
  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::VIEW] = ['printData', 10];
    
    return $events;
  }
  
  /**
   * This method is called whenever the kernel.view event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function printData(Event $event) {
    $query = \Drupal::request()->query;
    if ($query->has('print') && $query->get('print') == 'pdf') {
      $renderArray = $event->getControllerResult();
      $x['data'] = $this->searchNestedArrayKey($renderArray, '#exclude_from_print');
//      pr(array_keys($x));exit;
      $x['#type'] = 'container';
      $x['#attributes'] = ['style' => 'font-size:12px;', 'class' => 'pdf-content'];
      $xr['#attached']['library'][] = 'hzd/global-styling';
//      $css_assets = \Drupal::service('asset.resolver')
//        ->getCssAssets(AttachedAssets::createFromRenderArray($xr), 0);
//      $xwww = \Drupal::service('asset.css.collection_renderer')->render($css_assets);
      $print_engine = \Drupal::service('plugin.manager.entity_print.print_engine')
        ->createSelectedInstance('pdf');
      $html = \Drupal::service('renderer')->renderRoot($x);
//      $html .= \Drupal::service('renderer')->renderRoot($xwww);
      $html .= "<link type=\"text/css\" rel=\"stylesheet\" href=\"/themes/hzd/css/style.css\"/>
    <link type=\"text/css\" rel=\"stylesheet\" href=\"/themes/hzd/css/entity-print.css\"/>";
      if ($query->has('debug')) {
        echo $html;
        exit;
      }
      $print_engine->addPage($html);
      $print_engine->send("print.pdf", 0);
      exit;
    }
  }
  
  /*
   *
   * function returns the array with only the elements allowed to print( not having #exclude_from_print)
   */
  protected function searchNestedArrayKey(&$element, $key) {
    if (isset($element[$key])) {
      unset($element);
      return [];
    }
    $children = Element::children($element);
    if (!empty($children)) {
      
      foreach ($children as $item) {
        if (isset($element[$item][$key])) {
          unset($element[$item]);
        }
        else {
          $subChild = Element::children($element[$item]);
          if (!empty($subChild)) {
            $this->searchNestedArrayKey($element[$item], $key);
          }
        }
      }
    }
    return $element;
  }
  
}
