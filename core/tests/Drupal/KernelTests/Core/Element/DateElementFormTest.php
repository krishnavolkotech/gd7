<?php

namespace Drupal\KernelTests\Core\Element;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests Date element validation and conversion functionality.
 *
 * @group Form
 */
class DateElementFormTest extends KernelTestBase implements FormInterface {

  /**
   * User for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user'];

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences', 'key_value_expire']);
    $this->installEntitySchema('user');
    \Drupal::service('router.builder')->rebuild();
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::create([
      'id' => 'admin',
      'label' => 'admin',
    ]);
    $role->save();
    $this->testUser = User::create([
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $this->testUser->addRole($role->id());
    $this->testUser->save();
    \Drupal::service('current_user')->setAccount($this->testUser);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_date_element';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // A plain date element, bare minimum inclusions.
    $form['plain'] = [
      '#type' => 'date',
      '#title' => 'plain',
    ];

    // A date element with the type attribute set to date.
    $form['type_date'] = [
      '#type' => 'date',
      '#title' => 'type_date',
      '#attributes' => [
        'type' => 'date',
      ],
    ];

    // A date element with the type attribute set to text.
    $form['type_text'] = [
      '#type' => 'date',
      '#title' => 'type_text',
      '#attributes' => [
        'type' => 'text',
      ],
    ];

    // A date element with the type attribute set to date, with a class.
    $form['type_date_with_class'] = [
      '#type' => 'date',
      '#title' => 'type_date_with_class',
      '#attributes' => [
        'type' => 'date',
        'class' => [
          'unicorns',
        ],
      ],
    ];

    // A date element without the type attribute, with a class.
    $form['class_only'] = [
      '#type' => 'date',
      '#title' => 'class_only',
      '#attributes' => [
        'class' => [
          'unicorns',
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Tests that default handlers are added even if custom are specified.
   */
  public function testDateElement() {
    $form_state = (new FormState())
      ->setValues([
        'plain' => '2018-04-27',
        'type_date' => '2018-04-27',
        'type_text' => '2018-04-27',
        'type_date_with_class' => '2018-04-27',
        'class_only' => '2018-04-27',
      ]);
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    // Valid form state.
    $this->assertEqual(count($form_state->getErrors()), 0);

  }

}
