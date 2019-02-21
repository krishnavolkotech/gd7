<?php

/*
 * UpdateProblemsNotificationforAllServices
 */

namespace Drupal\problem_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class UpdateProblemTicketstore extends FormBase {

  /**
   * @return string
   */
  public function getFormId() {
    return 'update_problem_ticketstore';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['demo'] = [
      '#type' => 'checkbox',
      '#title' => t('Dry run (This will not update the actual data).'),
      '#default_value' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Update Problem ticketstore'
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    self::prepareBatch($form_state);
  }

  /**
   * @param FormStateInterface $form_state
   */
  static public function prepareBatch(FormStateInterface $form_state) {
    $count = 0;
    $dry_run = $form_state->getValue('demo');
    $data = \Drupal::database()->select('node__field_ticketstore_link', 'nftl')
      ->fields('nftl', ['entity_id', 'field_ticketstore_link_value'])
      ->condition('bundle', 'problem', '=')
      ->execute()
      ->fetchAll();

    foreach ($data as $rec) {
      $entity_id = $rec->entity_id;
      $ticketstore = $rec->field_ticketstore_link_value;
      if ($ticketstore) {
        preg_match("/href=\"(.*?)\"/i", $ticketstore, $matches);
        if (count($matches)) {
          if ($matches[1]) {
            $operations[] = [
              '\Drupal\problem_management\Form\UpdateProblemTicketstore::update',
              [
                $entity_id,
                $matches,
                $ticketstore,
                $dry_run,
                t('(DB @operation)', ['@operation' => $entity_id]),
              ],
            ];
            $count++;
          }
        }
      }
    }

    $batch = [
      'title' => t('Reading Ticketstore link from @num records', ['@num' => $count]),
      'operations' => $operations,
      'finished' => '\Drupal\problem_management\Form\UpdateProblemTicketstore::finishedCallBack',
    ];
    return batch_set($batch);
  }

  /**
   * @param $entity_id
   * @param $ticketstore
   * @param $dry_run
   * @param $operation_details
   * @param $context
   */
  static public function update($entity_id, $ticketstore, $ticketstore_raw, $dry_run, $operation_details, &$context) {
    $context['results'][$entity_id][] = $ticketstore_raw;
    if (!$dry_run) {
      self::update_problem_ticketstore_table($entity_id, $ticketstore[1]);
    }

    $context['message'] = t('Running Batch on entity id "@id" for @details',
      ['@id' => $entity_id, '@details' => $operation_details]
    );
  }


  /**
   * @param $entity_id
   * @param $ticketstore
   */
  public static function update_problem_ticketstore_table($entity_id, $ticketstore) {
    if (isset($ticketstore)) {
      \Drupal::database()->update('node__field_ticketstore_link')
        ->fields(['field_ticketstore_link_value' => $ticketstore])
        ->condition('entity_id', $entity_id)
        ->execute();
    }
  }

  /**
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function finishedCallBack($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Here we could do something meaningful with the results.
      // We just display the number of nodes we processed...
      $messenger->addMessage(t('@count record processed.', ['@count' => count($results)]));
      // $messenger->addMessage(t('The final result was "%final"', ['%final' => dsm($results)]));
    } else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }
}
