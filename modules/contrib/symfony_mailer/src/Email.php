<?php

namespace Drupal\symfony_mailer;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Defines the email class.
 */
class Email implements InternalEmailInterface {

  use BaseEmailTrait;

  /**
   * The mailer.
   *
   * @var \Drupal\symfony_mailer\MailerInterface
   */
  protected $mailer;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * @var string
   */
  protected $type;

  /**
   * @var string
   */
  protected $subType;

  /**
   * @var string
   */
  protected $entity_id;

  /**
   * Current phase, one of the PHASE_ constants.
   *
   * @var int
   */
  protected $phase = self::PHASE_INIT;

  /**
   * @var array
   */
  protected $body = [];

  /**
   * @var array
   */
  protected $processors = [];

  /**
   * @var string
   */
  protected $langcode;

  /**
   * @var string[]
   */
  protected $params = [];

  /**
   * @var string[]
   */
  protected $variables = [];

  /**
   * The account for the recipient (can be anonymous).
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var string
   */
  protected $theme = '';

  /**
   * @var array
   */
  protected $libraries = [];

  /**
   * The mail transport DSN.
   *
   * @var string
   */
  protected $transportDsn = '';

  /**
   * Constructs the Email object.
   *
   * @param \Drupal\symfony_mailer\MailerInterface $mailer
   *   Mailer service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param string $type
   *   Type. @see \Drupal\symfony_mailer\BaseEmailInterface::getType()
   * @param string $sub_type
   *   Sub-type. @see \Drupal\symfony_mailer\BaseEmailInterface::getSubType()
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   (optional) Entity. @see \Drupal\symfony_mailer\BaseEmailInterface::getEntity()
   */
  public function __construct(MailerInterface $mailer, RendererInterface $renderer, EntityTypeManagerInterface $entity_type_manager, ThemeManagerInterface $theme_manager, string $type, string $sub_type, ?ConfigEntityInterface $entity) {
    $this->mailer = $mailer;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->themeManager = $theme_manager;
    $this->type = $type;
    $this->subType = $sub_type;
    $this->entity = $entity;
    $this->inner = new SymfonyEmail();
  }

  /**
   * Creates an email object.
   *
   * Use EmailFactory instead of calling this directly.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   * @param string $type
   *   Type. @see \Drupal\symfony_mailer\BaseEmailInterface::getType()
   * @param string $sub_type
   *   Sub-type. @see \Drupal\symfony_mailer\BaseEmailInterface::getSubType()
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   (optional) Entity. @see \Drupal\symfony_mailer\BaseEmailInterface::getEntity()
   *
   * @return static
   *   A new email object.
   */
  public static function create(ContainerInterface $container, string $type, string $sub_type, ?ConfigEntityInterface $entity = NULL) {
    return new static(
      $container->get('symfony_mailer'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('theme.manager'),
      $type,
      $sub_type,
      $entity
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addProcessor(callable $function, int $phase = self::PHASE_BUILD, int $weight = self::DEFAULT_WEIGHT, string $id = NULL) {
    $this->valid(self::PHASE_INIT, self::PHASE_INIT);
    $this->processors[$phase][] = [
      'function' => $function,
      'weight' => $weight,
      'id' => $id,
    ];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    $this->valid(self::PHASE_POST_SEND, self::PHASE_PRE_RENDER);
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function setParams(array $params = []) {
    $this->valid(self::PHASE_INIT, self::PHASE_INIT);
    $this->params = $params;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setParam(string $key, $value) {
    $this->valid(self::PHASE_INIT, self::PHASE_INIT);
    $this->params[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * {@inheritdoc}
   */
  public function getParam(string $key) {
    return $this->params[$key] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $this->valid(self::PHASE_BUILD);
    return $this->mailer->send($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    $this->valid(self::PHASE_POST_SEND, self::PHASE_PRE_RENDER);
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody($body) {
    $this->valid(self::PHASE_PRE_RENDER);
    $this->body = $body;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function appendBody($body) {
    $this->valid(self::PHASE_PRE_RENDER);
    $name = 'n' . count($this->body);
    $this->body[$name] = $body;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function appendBodyEntity(EntityInterface $entity, $view_mode = 'full') {
    $this->valid(self::PHASE_PRE_RENDER);
    $build = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())
      ->view($entity, $view_mode);
    $this->appendBody($build);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    $this->valid(self::PHASE_PRE_RENDER);
    return $this->body;
  }

  /**
   * {@inheritdoc}
   */
  public function setVariables(array $variables) {
    $this->valid(self::PHASE_BUILD, self::PHASE_INIT);
    $this->variables = $variables;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setVariable(string $key, $value) {
    $this->valid(self::PHASE_BUILD, self::PHASE_INIT);
    $this->variables[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables() {
    return $this->variables;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubType() {
    return $this->subType;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestions(string $initial, string $join) {
    $part_array = [$this->type, $this->subType];
    if (isset($this->entity)) {
      $part_array[] = $this->entity->id();
    }

    $part = $initial ?: array_shift($part_array);
    $suggestions[] = $part;

    while ($part_array) {
      $part .= $join . array_shift($part_array);
      $suggestions[] = $part;
    }

    return $suggestions;
  }

  /**
   * {@inheritdoc}
   */
  public function setTheme(string $theme_name) {
    $this->valid(self::PHASE_BUILD);
    $this->theme = $theme_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    if (!$this->theme) {
      $this->theme = $this->themeManager->getActiveTheme()->getName();
    }
    return $this->theme;
  }

  /**
   * {@inheritdoc}
   */
  public function addLibrary(string $library) {
    $this->libraries[] = $library;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return $this->libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransportDsn(string $dsn) {
    $this->transportDsn = $dsn;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransportDsn() {
    return $this->transportDsn;
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    $processors = $this->processors[$this->phase] ?? [];
    usort($processors, function ($a, $b) {
      return $a['weight'] <=> $b['weight'];
    });

    foreach ($processors as $processor) {
      call_user_func($processor['function'], $this);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function initDone() {
    $this->valid(self::PHASE_INIT, self::PHASE_INIT);
    $this->phase = self::PHASE_BUILD;
  }

  /**
   * {@inheritdoc}
   */
  public function customize(string $langcode, AccountInterface $account) {
    $this->valid(self::PHASE_BUILD);
    $this->langcode = $langcode;
    $this->account = $account;
    $this->phase = self::PHASE_PRE_RENDER;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $this->valid(self::PHASE_PRE_RENDER, self::PHASE_PRE_RENDER);

    // Render subject.
    if ($this->subject instanceof MarkupInterface) {
      $this->subject = PlainTextOutput::renderFromHtml($this->subject);
    }

    // Render body.
    $body = ['#theme' => 'email', '#email' => $this];
    $html = $this->renderer->renderPlain($body);
    $this->phase = self::PHASE_POST_RENDER;
    $this->setHtmlBody($html);
    $this->body = [];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhase() {
    return $this->phase;
  }

  /**
   * {@inheritdoc}
   */
  public function getSymfonyEmail() {
    $this->valid(self::PHASE_POST_RENDER, self::PHASE_POST_RENDER);

    if ($this->subject) {
      $this->inner->subject($this->subject);
    }

    $this->inner->sender($this->sender->getSymfony());
    $headers = $this->getHeaders();
    foreach ($this->addresses as $name => $addresses) {
      $value = [];
      foreach ($addresses as $address) {
        $value[] = $address->getSymfony();
      }
      if ($value) {
        $headers->addMailboxListHeader($name, $value);
      }
    }

    $this->phase = self::PHASE_POST_SEND;
    return $this->inner;
  }

  /**
   * Checks that a function was called in the correct phase.
   *
   * @param int $phase
   *   The correct phase, one of the PHASE_ constants.
   * @param bool $exact
   *   If TRUE, require the exact phase, if FALSE allow earlier phases (later
   *   phases for post-render).
   *
   * @return $this
   */
  protected function valid(int $max_phase, int $min_phase = self::PHASE_BUILD) {
    $valid = ($this->phase <= $max_phase) && ($this->phase >= $min_phase);

    if (!$valid) {
      $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
      throw new \LogicException("$caller function is only valid in phases $min_phase-$max_phase, called in $this->phase.");
    }
    return $this;
  }

}
