<?php

namespace Drupal\entity_print\EventSubscriber;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\entity_print\Event\PrintEvents;
use Drupal\entity_print\Event\PrintHtmlAlterEvent;
use Masterminds\HTML5;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The PostRenderSubscriber class.
 */
class PostRenderSubscriber implements EventSubscriberInterface {

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * PostRenderSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * Alter the HTML after it has been rendered.
   *
   * This is a temporary workaround for a core issue.
   * @see https://drupal.org/node/1494670
   *
   * @param \Drupal\entity_print\Event\PrintHtmlAlterEvent $event
   *   The event object.
   */
  public function postRender(PrintHtmlAlterEvent $event) {
    // We only apply the fix to PHP Wkhtmltopdf because the other implementations
    // allow us to specify a base url.
    $config = $this->configFactory->get('entity_print.settings');
    if ($config->get('print_engines.pdf_engine') !== 'phpwkhtmltopdf') {
      return;
    }

    $html_string = &$event->getHtml();
    $html5 = new HTML5();
    $document = $html5->loadHTML($html_string);

    // Define a function that will convert root relative uris into absolute urls.
    $transform = function($tag, $attribute) use ($document) {
      $base_url = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
      foreach ($document->getElementsByTagName($tag) as $node) {
        $attribute_value = $node->getAttribute($attribute);

        // Handle protocol agnostic URLs as well.
        if (Unicode::substr($attribute_value, 0, 2) === '//') {
          $node->setAttribute($attribute, $base_url . Unicode::substr($attribute_value, 1));
        }
        elseif (Unicode::substr($attribute_value, 0, 1) === '/') {
          $node->setAttribute($attribute, $base_url . $attribute_value);
        }
      }
    };

    // Transform stylesheets, links and images.
    $transform('link', 'href');
    $transform('a', 'href');
    $transform('img', 'src');

    // Overwrite the HTML.
    $html_string = $html5->saveHTML($document);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [PrintEvents::POST_RENDER => 'postRender'];
  }

}
