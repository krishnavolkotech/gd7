<?php

namespace Drupal\hzd_customizations\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'file_table_alter' formatter.
 *
 * @FieldFormatter(
 *   id = "file_table_alter",
 *   label = @Translation("Table of files alter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class TableFormatterAlter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($files = $this->getEntitiesToView($items, $langcode)) {
      $header = [t('Attachment')];
      $rows = [];
      foreach ($files as $delta => $file) {
        $rows[] = [
          [
            'data' => [
              '#theme' => 'file_link',
              '#file' => $file,
              '#cache' => [
                'tags' => $file->getCacheTags(),
              ],
            ],
          ],
        ];
      }

      $elements[0] = [];
      if (!empty($rows)) {
        $elements[0] = [
          '#theme' => 'table__file_formatter_table',
          '#header' => $header,
          '#rows' => $rows,
        ];
      }
    }

    return $elements;
  }

}
