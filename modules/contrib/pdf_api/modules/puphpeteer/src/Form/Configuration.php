<?php

namespace Drupal\puphpeteer\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Configuration extends ConfigFormBase {

  /**
   * Constructs $messenger and $config_factory objects.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'puphpeteer.settings',
    ];
  }

  function getFormId() {
    return 'puphpeteer_configuration';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('puphpeteer.settings')->get();

    $form['executable_path'] = [
      '#type' => 'textfield',
      '#title' => 'Path to node',
      '#required' => TRUE,
      '#default_value' => $config['executable_path'],
    ];

    $form['idle_timeout'] = [
      '#type' => 'number',
      '#min' => 5,
      '#title' => 'Idle timeout',
      '#default_value' => $config['idle_timeout'],
    ];

    $form['read_timeout'] = [
      '#type' => 'number',
      '#min' => 5,
      '#title' => 'Read timeout',
      '#default_value' => $config['read_timeout'],
    ];

    $form['stop_timeout'] = [
      '#type' => 'number',
      '#min' => 5,
      '#title' => 'Stop timeout',
      '#default_value' => $config['stop_timeout'],
    ];

    $form['source'] = [
      '#type' => 'select',
      '#title' => 'Content source',
      '#options' => [
        'printable' => 'Normal printable rendering',
        'canonical' => 'Canonical URL for the entity',
        'print' => 'Print view mode for the entity',
      ],
      '#default_value' => $config['source'],
    ];

    $form['headless'] = [
      '#type' => 'checkbox',
      '#title' => 'Headless? (Normally on)',
      '#description' => 'To use Chrome in headful mode, you need to set DISPLAY in your PHP environment. If Chrome is in headful mode, it disables its ability to generate PDFs. PHP will wait for the browser to be closed before completing the request.',
      '#default_value' => $config['headless'],
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => 'Debug?',
      '#default_value' => $config['debug'],
    ];

    $form['log_to_node_console'] = [
      '#type' => 'checkbox',
      '#title' => 'Log to node console?',
      '#default_value' => $config['log_to_node_console'],
    ];

    $form['log_to_browser_console'] = [
      '#type' => 'checkbox',
      '#title' => 'Log to browser console?',
      '#default_value' => $config['log_to_browser_console'],
    ];

    $form['triggerDebugging'] = [
      '#type' => 'checkbox',
      '#title' => 'Trigger JS debugging immediately? (Normally off)',
      '#default_value' => $config['triggerDebugging'],
    ];

    $form['devTools'] = [
      '#type' => 'checkbox',
      '#title' => 'Open devtools? (Normally off)',
      '#default_value' => $config['devTools'],
    ];

    $form['slowMo'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => 'Delay to add between Pupeteer actions',
      '#default_value' => $config['slowMo'],
    ];

    $form['pagedjs'] = [
      '#type' => 'checkbox',
      '#title' => 'Load pagedjs in Chrome?',
      '#default_value' => $config['pagedjs'],
    ];

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => '<em>When debugging with headful Chrome, you can preview the print media version by pressing Control+Shift+P. Type "Rendering" and select Show Rendering. In the Emulate CSS media dropdown, select print.</em>'
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Check if config variable is overridden by the settings.php.
   *
   * Copied from the smtp module as it's better than what I've managed to
   * come up with but it is still imperfect - it willbe fooled if the override
   * is the same and the editable value.
   *
   * @param string $name
   *   SMTP settings key.
   *
   * @return bool
   *   Boolean.
   */
  protected function isOverridden($name) {
    $original = $this->configFactory->getEditable('puphpeteer.settings')
      ->get($name);
    $current = $this->configFactory->get('puphpeteer.settings')->get($name);
    return $original != $current;
  }

  public function validateExecutable(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $path = $values['executable_path'];
    $dir = dirname($path);
    if (!is_dir($dir)) {
      $form_state->setErrorByName('executable_path',
        $this->t('The directory :dir does not exist', [':dir' => $dir]));
      return;
    }

    if (!is_executable($path)) {
      $form_state->setErrorByName('executable_path',
        $this->t(':path is not an executable', [':path' => $path]));
      return;
    }

    $output = '';
    $result_code = 0;
    $invoke = "{$path} -v";
    exec($invoke, $output, $result_code);

    if ($result_code) {
      $form_state->setErrorByName('executable_path',
        $this->t('Seeking to execute :path gave result code :result', [
          ':path' => $path,
          ':result_code' => $result_code,
        ]));
      return;
    }

    $this->messenger()
      ->addMessage($this->t('Node :version found', [
        ':version' => $output[0],
      ]));
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateExecutable($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->getEditable('puphpeteer.settings');
    foreach ([
               'executable_path',
               'idle_timeout',
               'read_timeout',
               'stop_timeout',
               'log_to_node_console',
               'debug',
               'log_to_browser_console',
               'headless',
               'slowMo',
               'devTools',
               'triggerDebugging',
               'source',
               'pagedjs',
             ] as $key) {
      $config->set($key, $values[$key]);
    }
    $config->save();
  }

}
