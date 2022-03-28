<?php

/**
 * @file
 * Contains \Drupal\pdf_api\Plugin\DompdfGenerator.
 */

namespace Drupal\puphpeteer\Plugin\PdfGenerator;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\pdf_api\Annotation\PdfGenerator;
use Drupal\pdf_api\Plugin\PdfGeneratorBase;
use Drupal\pdf_api\Plugin\PdfGeneratorInterface;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Rialto\Data\JsFunction;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A PDF generator plugin for Puphpeteer.
 *
 * @PdfGenerator(
 *   id = "puphpeteer",
 *   module = "puphpeteer",
 *   title = @Translation("Puphpeteer"),
 *   description = @Translation("PDF generator using Puphpeteer."),
 *   required_class = "Nesk\Puphpeteer\Puppeteer",
 * )
 */
class PuphpeteerGenerator extends PdfGeneratorBase implements ContainerFactoryPluginInterface {

  /**
   * Instance of the DOMPDF class library.
   *
   * @var \nesk\puphpeteer\Puppeteer
   */
  protected $generator;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Route Match
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * URL Generator service instance.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * Current user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $currentUser;

  /**
   * Route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Settings for our generated CSS.
   *
   * @var array
   */
  protected $css = [];

  /**
   * Page orientation.
   *
   * @var string
   */
  protected $landscape = FALSE;

  /**
   * Page size.
   *
   * @var string
   */
  protected $page_size = 'A4';

  /**
   * Header.
   *
   * @var string
   */
  protected $header = '';

  /**
   * Footer.
   *
   * @var string
   */
  protected $footer = '';

  /**
   * HTML content.
   *
   * @var string
   */
  protected $html = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactory $configFactory, LoggerInterface $logger, CurrentRouteMatch $currentRouteMatch, RouteProviderInterface $routeProvider, UrlGeneratorInterface $urlGenerator, CsrfTokenGenerator $csrfTokenGenerator, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->settings = $configFactory->get('puphpeteer.settings');
    $this->logger = $logger;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->routeProvider = $routeProvider;
    $this->urlGenerator = $urlGenerator;
    $this->csrfTokenGenerator = $csrfTokenGenerator;
    $this->currentUser = $currentUser;

    $settings = $this->settings->get();

    $options = [
      'logger' => $settings['debug'] ? $logger : NULL,
      'log_browser_console' => $settings['log_to_browser_console'],
      'log_node_console' => $settings['log_to_node_console'],
      'executable_path' => $settings['executable_path'],
      'read_timeout' => $settings['read_timeout'],
      'idle_timeout' => $settings['idle_timeout'],
      'debug' => $settings['debug'],
    ];

    try {
      $this->generator = new Puppeteer($options);
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory')->get('puphpeteer'),
      $container->get('current_route_match'),
      $container->get('router.route_provider'),
      $container->get('url_generator'),
      $container->get('csrf_token'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setter($pdf_content, $pdf_location, $save_pdf, $paper_orientation, $paper_size, $footer_content, $header_content, $path_to_binary = '') {
    $this->setPageOrientation($paper_orientation);
    $this->setHeader($header_content);
    $this->addPage($pdf_content);
  }

  /**
   * {@inheritdoc}
   */
  public function getObject() {
    return $this->generator;
  }

  /**
   * We don't use the pre-rendered HTML.
   */
  public function usePrintableDisplay() {
    return $this->settings->get('source') == 'printable';
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader($text) {
    $this->header = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function addPage($html) {
    $this->html = $html;
  }

  /**
   * {@inheritdoc}
   */
  public function setPageOrientation($orientation = PdfGeneratorInterface::PORTRAIT) {
    $this->landscape = ($orientation == PdfGeneratorInterface::LANDSCAPE);
  }

  /**
   * {@inheritdoc}
   */
  public function setPageSize($page_size) {
    if ($this->isValidPageSize($page_size)) {
      $this->page_size = $page_size;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFooter($text) {
    $this->footer = $text;
  }


  /**
   * {@inheritdoc}
   */
  public function save($location) {

    // Start the browser, as configured.
    $headless = $this->settings->get('headless');
    $launchParams = [
      'args' => ['--no-sandbox', '--disable-setuid-sandbox', '--start-maximized'],
      'headless' => $headless,
      'slowMo' => $this->settings->get('slowMo'),
      'ignoreHTTPSErrors' => true,
      'defaultViewport' => null,
    ];

    if ($this->settings->get('devTools')) {
      $launchParams['args'][] = '--auto-open-devtools-for-tabs';
    }

    try {
      $browser = $this->generator->launch($launchParams);
    }
    catch (\Exception $exception) {
      $this->messenger()
        ->addError('We failed to generate the PDF, sorry. Please try again later.');
      $this->logger
        ->alert($this->t("Puphpeteer failed to start the browser (:message).", [
          ':message' => $exception->getMessage(),
        ]));
      throw($exception);
    }

    $pages = [];
    while (empty($pages)) {
      $pages = $browser->pages();
    }
    $page = $pages[0];

    // Give Chrome in Puppeteer the same access the current user has.
    $cookies = \Drupal::request()->cookies->all();
    $arg = [];
    foreach ($cookies as $name => $value) {
      $arg[] = [
        'name' => $name,
        'value' => $value,
        'domain' => \Drupal::request()->getHost(),
      ];
    }
    $page->setCookie(... $arg);

    $url = null;

    switch ($this->settings->get('source')) {
      case 'printable':
        break;

      case 'canonical':
        $route_name = 'entity.' . $this->entity->getEntityTypeId() . '.canonical';
        $route = $this->routeProvider->getRouteByName($route_name);
        $options = [];
        foreach($route->getOptions()['parameters'] as $name => $details) {
          if ($name == $this->entity->getEntityTypeId()) {
            $options[$name] = $this->entity->id();
          }
          if ($name == 'webform_submission') {
            $options['webform'] = $this->entity->getWebform()->id();
          }
        }
        $url = $this->urlGenerator->generateFromRoute(
          $route_name, $options, [ 'absolute' => TRUE ]);
        break;

      case 'print':
        $url = $this->urlGenerator->generateFromRoute(
          'printable.show_format.' . $this->entity->getEntityTypeId(), [
          'printable_format' => 'print',
          'entity' => $this->entity->id(),
        ], [
          'absolute' => TRUE,
        ]);
        break;
    }

    if ($url) {
      $page->goto($url);
    }
    else {
      $page->setContent($this->html);
    }

    if ($this->settings->get('triggerDebugging')) {
      $page->evaluate(JsFunction::createWithBody("debugger")->async(true));
    }

    if ($this->settings->get('pagedjs')) {
      $page->addScriptTag([
        'url' => 'https://unpkg.com/pagedjs/dist/paged.polyfill.js',
        'text' => 'text/javascript',
      ]);
    }

    $page->emulateMediaType('print');

    if (!$headless) {
      // Wait until browser is closed.
      while ($browser->isConnected()) {
        sleep(1);
      }
      exit(0);
    }
    else {
      if ($this->settings->get('pagedjs')) {
        $page->waitForXPath('//template');
      }
      else {
        $page->waitForNetworkIdle();
      }

      $options = [
        'printBackground' => TRUE,
        'omitBackground' => TRUE,
        'preferCSSPageSize' => TRUE,
        'displayHeaderFooter' => TRUE,
      ];
      if ($this->landscape) {
        $options['landscape'] = TRUE;
      }
      if ($this->page_size !== 'Letter') {
        $options['format'] = $this->page_size;
      }
      if ($this->header) {
        $options['headerTemplate'] = (string) $this->header;
      }
      if ($this->footer) {
        $options['footerTemplate'] = (string) $this->footer;
      }
      // To output from Chrome directly to the filesystem:
      // $options['path'] = $location;
      $buffer = $page->pdf($options);

      // Don't just cast to a string - that messes up the encoding.
      $buffer = base64_decode($buffer->toString('base64'));
      $browser->close();

      file_put_contents($location, $buffer);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $this->generator->stream("pdf", array('Attachment' => 0));
  }

  /**
   * {@inheritdoc}
   */
  public function stream($filelocation) {
    $this->generator->Output($filelocation, "F");
  }

}
