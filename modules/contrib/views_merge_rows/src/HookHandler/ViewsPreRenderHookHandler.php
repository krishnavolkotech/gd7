<?php

declare(strict_types = 1);

namespace Drupal\views_merge_rows\HookHandler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hook handler for the hook_views_pre_render() hook.
 */
class ViewsPreRenderHookHandler implements ContainerInjectionInterface {

  /**
   * The pager manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The pager manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(
    PagerManagerInterface $pagerManager,
    RequestStack $requestStack
  ) {
    $this->pagerManager = $pagerManager;
    $this->currentRequest = $requestStack->getCurrentRequest() ?: new Request();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('pager.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Alter the view according to merge rows configuration.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to alter.
   */
  public function process(ViewExecutable $view) : void {
    $extenders = $view->getDisplay()->getExtenders();
    if (!isset($extenders['views_merge_rows'])) {
      // If the ID of the plugin is not in the list then do nothing.
      return;
    }
    /** @var \Drupal\views_merge_rows\Plugin\views\display_extender\ViewsMergeRowsDisplayExtenderPlugin $extender */
    $extender = $extenders['views_merge_rows'];

    $options = $extender->getOptions();
    if (!$options['merge_rows']) {
      // If merge rows is not enabled on this view then do nothing.
      return;
    }

    if ($view->getItemsPerPage() > 0) {
      $view->setItemsPerPage(0);
    }

    $view->style_plugin->renderFields($view->result);
    $rendered_fields = $view->style_plugin->getRenderedFields();

    $filters = [];
    $merged_rows = [];

    // Necessary for 'count_minus_count_unique'. Not used anywhere else.
    $merged_rows_copy = [];
    $first = TRUE;

    foreach ((array) $rendered_fields as $row_index => $rendered_row) {
      $filter_value = '';
      foreach ($options['field_config'] as $field_name => $field_config) {
        if ($field_config['merge_option'] == 'filter') {
          $filter_value .= $rendered_row[$field_name];
        }
      }
      $is_filter_row = [];
      $merged_row_replaced = FALSE;

      if (!array_key_exists($filter_value, $filters)) {
        $filters[$filter_value] = $row_index;
        $merged_row = [];
        $merged_row_copy = [];
        $is_filter_row[$row_index] = TRUE;
        $merged_row_index = $row_index;
      }
      else {
        $merged_row_index = $filters[$filter_value];
        $merged_row = $merged_rows[$merged_row_index];
        if (isset($merged_rows_copy[$merged_row_index])) {
          $merged_row_copy = $merged_rows_copy[$merged_row_index];
        }
        $is_filter_row[$row_index] = FALSE;
      }
      foreach ($options['field_config'] as $field_name => $field_config) {
        switch ($field_config['merge_option']) {
          case 'count_unique':
            $this->preRenderCountUnique($view, $merged_rows, $merged_row, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'count':
            $this->preRenderCount($view, $merged_rows, $merged_row, $is_filter_row[$row_index], $row_index, $field_name, $merged_row_index);
            break;

          case 'count_minus_count_unique':
            if ($first) {
              $merged_row_copy = $merged_row;
              $first = FALSE;
            }
            $this->preRenderCountMinusCountUnique($view, $merged_rows, $merged_rows_copy, $merged_row, $merged_row_copy, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'merge_unique':
            $this->preRenderMergeUnique($view, $merged_rows, $merged_row, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'merge':
            $this->preRenderMerge($view, $merged_rows, $merged_row, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'sum':
            $this->preRenderSum($view, $merged_rows, $merged_row, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'average':
            $this->preRenderAverage($view, $merged_rows, $merged_row, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'std_deviation':
            $this->preRenderStandardDeviation($view, $merged_rows, $merged_row, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'filter':
            $this->preRenderFilter($view, $merged_rows, $merged_row, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'first_value':
            $this->preRenderFirstValue($view, $merged_rows, $merged_row, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'highest_value':
            $this->preRenderHighestValue($view, $merged_rows, $merged_row, $merged_row_replaced, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;

          case 'lowest_value':
            $this->preRenderLowestValue($view, $merged_rows, $merged_row, $merged_row_replaced, $is_filter_row[$row_index], $rendered_row, $row_index, $field_name, $merged_row_index);
            break;
        }
      }
    }

    // Store the merged rows back to the view's style plugin.
    foreach ($merged_rows as $row_index => $merged_row) {
      foreach ($options['field_config'] as $field_name => $field_config) {
        switch ($field_config['merge_option']) {
          case 'count_unique':
            $view->style_plugin->setRenderedField(count($merged_row[$field_name]), $row_index, $field_name);
            break;

          case 'count_minus_count_unique':
            $view->style_plugin->setRenderedField(intval($merged_rows_copy[$row_index][$field_name]) - count($merged_row[$field_name]), $row_index, $field_name);
            break;

          case 'merge_unique':
          case 'merge':
            // Difference of behavior between 'merge' and 'merge_unique' is done
            // in the preRender methods.
            $this->renderMerge($view, $merged_row, $row_index, $field_name, $field_config);
            break;

          case 'sum':
            $this->renderSum($view, $merged_row, $row_index, $field_name);
            break;

          case 'average':
            $this->renderAverage($view, $merged_row, $row_index, $field_name);
            break;

          case 'std_deviation':
            $this->renderStandardDeviation($view, $merged_row, $row_index, $field_name);
            break;

          case 'count':
          case 'filter':
          case 'first_value':
          case 'highest_value':
          case 'lowest_value':
            $view->style_plugin->setRenderedField($merged_row[$field_name], $row_index, $field_name);
            break;
        }
      }
    }
  }

  /**
   * Manipulates data if merge option is 'count_unique'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderCountUnique(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      if (!array_key_exists($field_name, $merged_row)) {
        $merged_row[$field_name] = [];
      }
      if (!in_array($rendered_row[$field_name], $merged_row[$field_name])) {
        $merged_row[$field_name][] = $rendered_row[$field_name];
      }
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      if (!empty($rendered_row[$field_name]) && !in_array($rendered_row[$field_name], $merged_row[$field_name])) {
        $merged_row[$field_name][] = $rendered_row[$field_name];
      }
      $this->unsetRow($view, $row_index);
      $merged_rows[$merged_row_index] = $merged_row;
    }
  }

  /**
   * Manipulates data if merge option is 'count'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderCount(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool $is_filter_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row[$field_name] = 1;
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      $merged_row[$field_name] = intval($merged_row[$field_name]) + 1;
      $this->unsetRow($view, $row_index);
      $merged_rows[$merged_row_index] = $merged_row;
    }
  }

  /**
   * Manipulates data if merge option is 'count_minus_count_unique'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows. Used for 'count_unique' data manipulation.
   * @param array $merged_rows_copy
   *   Copy of $merged_rows. Used for 'count' data manipulation.
   * @param array $merged_row
   *   Current merged row. Used for 'count_unique' data manipulation.
   * @param array $merged_row_copy
   *   Current merged row. Used for 'count' data manipulation.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderCountMinusCountUnique(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_rows_copy,
    array &$merged_row,
    array &$merged_row_copy,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      if (!array_key_exists($field_name, $merged_row)) {
        $merged_row[$field_name] = [];
      }
      if (!in_array($rendered_row[$field_name], $merged_row[$field_name])) {
        $merged_row[$field_name][] = $rendered_row[$field_name];
      }
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      if (!empty($rendered_row[$field_name]) && !in_array($rendered_row[$field_name], $merged_row[$field_name])) {
        $merged_row[$field_name][] = $rendered_row[$field_name];
      }
      $merged_rows[$merged_row_index] = $merged_row;
    }

    if ($is_filter_row) {
      $merged_row_copy[$field_name] = 1;
      $merged_rows_copy[$row_index] = $merged_row_copy;
    }
    else {
      $merged_row_copy[$field_name] = intval($merged_row_copy[$field_name]) + 1;
      $this->unsetRow($view, $row_index);
      $merged_rows_copy[$merged_row_index] = $merged_row_copy;
    }
  }

  /**
   * Manipulates data if merge option is 'merge_unique'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderMergeUnique(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row[$field_name] = [$rendered_row[$field_name]];
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      if (!empty($rendered_row[$field_name]) && !in_array($rendered_row[$field_name], $merged_row[$field_name])) {
        $merged_row[$field_name][] = $rendered_row[$field_name];
      }
      $this->unsetRow($view, $row_index);
      $merged_rows[$merged_row_index] = $merged_row;
    }
  }

  /**
   * Manipulates data if merge option is 'merge'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderMerge(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool $is_filter_row,
    array $rendered_row,
    $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row[$field_name] = [$rendered_row[$field_name]];
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      $merged_row[$field_name][] = $rendered_row[$field_name];
      $this->unsetRow($view, $row_index);
      $merged_rows[$merged_row_index] = $merged_row;
    }
  }

  /**
   * Manipulates data if merge option is 'sum'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderSum(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row[$field_name] = [$rendered_row[$field_name]];
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      $merged_row[$field_name][] = $rendered_row[$field_name];
      $this->unsetRow($view, $row_index);
      $merged_rows[$merged_row_index] = $merged_row;
    }
  }

  /**
   * Manipulates data if merge option is 'average'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderAverage(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row[$field_name] = [$rendered_row[$field_name]];
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      $merged_row[$field_name][] = $rendered_row[$field_name];
      $this->unsetRow($view, $row_index);
      $merged_rows[$merged_row_index] = $merged_row;
    }
  }

  /**
   * Manipulates data if merge option is 'std_deviation'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderStandardDeviation(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row[$field_name] = [$rendered_row[$field_name]];
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      $merged_row[$field_name][] = $rendered_row[$field_name];
      $this->unsetRow($view, $row_index);
      $merged_rows[$merged_row_index] = $merged_row;
    }
  }

  /**
   * Manipulates data if merge option is 'filter'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderFilter(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row[$field_name] = $rendered_row[$field_name];
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      $merged_rows[$merged_row_index] = $merged_row;
    }
  }

  /**
   * Manipulates data if merge option is 'first_value'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderFirstValue(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row[$field_name] = $rendered_row[$field_name];
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      $this->unsetRow($view, $row_index);
      $merged_rows[$merged_row_index] = $merged_row;
    }
  }

  /**
   * Manipulates data if merge option is 'highest_value'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $merged_row_replaced
   *   Indicates whether $merged_row has been replaced.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderHighestValue(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool &$merged_row_replaced,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row[$field_name] = $rendered_row[$field_name];
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      // Strip the HTML from the rendered and merged fields data and grab
      // the raw value.
      $rendered_row_data = floatval((string) $rendered_row[$field_name]);
      $merged_row_data = floatval((string) $merged_row[$field_name]);

      // Place the higher value into the merged row array.
      if ($rendered_row_data > $merged_row_data) {
        $merged_row[$field_name] = $rendered_row[$field_name];
        $merged_row_replaced = TRUE;
      }
      // Remove the lower value row from the view.
      else {
        $this->unsetRow($view, $row_index);
        $merged_row_replaced = FALSE;
      }
    }

    // If we replaced the row with a higher value, then update all array
    // indexes with the new index values.
    if ($merged_row_replaced == TRUE) {
      $this->updateArrayIndexes($view, $merged_row, $row_index, $merged_row_index);
    }
  }

  /**
   * Manipulates data if merge option is 'lowest_value'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_rows
   *   Array of merged rows.
   * @param array $merged_row
   *   Current merged row.
   * @param bool $merged_row_replaced
   *   Indicates whether $merged_row has been replaced.
   * @param bool $is_filter_row
   *   TRUE if row merges rows of one or more filtered fields; FALSE otherwise.
   * @param array $rendered_row
   *   Row from $rendered_fields from which data is extracted.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function preRenderLowestValue(
    ViewExecutable $view,
    array &$merged_rows,
    array &$merged_row,
    bool &$merged_row_replaced,
    bool $is_filter_row,
    array $rendered_row,
    int $row_index,
    string $field_name,
    int $merged_row_index
  ) : void {
    if ($is_filter_row) {
      $merged_row = [];
      $merged_row[$field_name] = $rendered_row[$field_name];
      $merged_rows[$row_index] = $merged_row;
    }
    else {
      // Strip the HTML from the rendered and merged fields data and grab
      // the raw value.
      $rendered_row_data = floatval((string) $rendered_row[$field_name]);
      $merged_row_data = floatval((string) $merged_row[$field_name]);

      // Place the lower value into the merged row array.
      if (!empty($rendered_row[$field_name])) {
        $merged_row[$field_name] = $rendered_row[$field_name];
        $merged_row_replaced = TRUE;
      }
      elseif ($rendered_row_data <= $merged_row_data && !empty($rendered_row[$field_name])) {
        $merged_row[$field_name] = $rendered_row[$field_name];
        $merged_row_replaced = TRUE;
      }
      // Remove the higher value row from the view.
      else {
        $this->unsetRow($view, $row_index);
        $merged_row_replaced = FALSE;
      }
    }

    // If we replaced the row with a higher value, then update all array
    // indexes with the new index values.
    if ($merged_row_replaced == TRUE) {
      $this->updateArrayIndexes($view, $merged_row, $row_index, $merged_row_index);
    }
  }

  /**
   * Sets data into $view->style_plugin->rendered_fields.
   *
   * If merge option is 'merge' or 'merge_unique'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_row
   *   Current merged row.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   * @param array $field_config
   *   The Views Merge Rows config for the field.
   */
  protected function renderMerge(
    ViewExecutable $view,
    array &$merged_row,
    int $row_index,
    string $field_name,
    array $field_config
  ) : void {
    foreach ($merged_row[$field_name] as $field_index => $field_value) {
      if (empty($field_value)) {
        unset($merged_row[$field_name][$field_index]);
      }
    }
    if ($field_config['exclude_first']) {
      array_shift($merged_row[$field_name]);
    }
    $value_count = count($merged_row[$field_name]);
    $iteration = 1;
    foreach ($merged_row[$field_name] as $field_index => $field_value) {
      if ($iteration <> $value_count) {
        $merged_row[$field_name][$field_index] = $field_config['prefix'] . $field_value . $field_config['separator'] . $field_config['suffix'];
      }
      else {
        $merged_row[$field_name][$field_index] = $field_config['prefix'] . $field_value . $field_config['suffix'];
      }
      $iteration++;
    }
    unset($iteration);
    unset($value_count);
    $view->style_plugin->setRenderedField(implode($merged_row[$field_name]), $row_index, $field_name);
  }

  /**
   * Sets data into $view->style_plugin->rendered_fields.
   *
   * If merge option is 'sum'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_row
   *   Current merged row.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   */
  protected function renderSum(
    ViewExecutable $view,
    array &$merged_row,
    int $row_index,
    string $field_name
  ) : void {
    $sum = 0;
    foreach ($merged_row[$field_name] as $field_value) {
      if (!empty($field_value)) {
        $sum += floatval((string) $field_value);
      }
    }
    $view->style_plugin->setRenderedField($sum, $row_index, $field_name);
  }

  /**
   * Sets data into $view->style_plugin->rendered_fields.
   *
   * If merge option is 'average'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_row
   *   Current merged row.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   */
  protected function renderAverage(
    ViewExecutable $view,
    array &$merged_row,
    int $row_index,
    string $field_name
  ) : void {
    $sum = 0;
    $count_field_values = 0;
    foreach ($merged_row[$field_name] as $field_value) {
      if (!empty($field_value)) {
        $sum += floatval((string) $field_value);
        $count_field_values++;
      }
    }
    $sum = $sum / $count_field_values;
    $view->style_plugin->setRenderedField($sum, $row_index, $field_name);
  }

  /**
   * Sets data into $view->style_plugin->rendered_fields.
   *
   * If merge option is 'std_deviation'.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_row
   *   Current merged row.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param string $field_name
   *   Name of the field being under work.
   */
  protected function renderStandardDeviation(
    ViewExecutable $view,
    array &$merged_row,
    int $row_index,
    string $field_name
  ) : void {
    $sum = 0;
    $sum_square = 0;
    $count_field_values = 0;
    foreach ($merged_row[$field_name] as $field_value) {
      if (!empty($field_value)) {
        $sum += floatval((string) $field_value);
        $sum_square += floatval((string) $field_value) * floatval((string) $field_value);
        $count_field_values++;
      }
    }
    $average = $sum / $count_field_values;
    $average_square = $sum_square / $count_field_values;
    if ($count_field_values == 1) {
      $sum = 0;
    }
    else {
      $sum = ($count_field_values / ($count_field_values - 1)) *
        ($average_square - $average * $average);
      $sum = sqrt($sum);
    }
    $view->style_plugin->setRenderedField($sum, $row_index, $field_name);
  }

  /**
   * Unsets the given row from the current display.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param int $row_index
   *   Supplied row index value.
   */
  protected function unsetRow(ViewExecutable $view, int $row_index) : void {
    if (isset($view->result[$row_index])) {
      unset($view->result[$row_index]);
      --$view->total_rows;
    }
  }

  /**
   * Update array indexes.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   View object.
   * @param array $merged_row
   *   Current merged row.
   * @param int $row_index
   *   Row index to which data shall be reported if the row
   *   is a filtered one ($merged_rows).
   * @param int $merged_row_index
   *   Row index to which data shall be reported if the row
   *   is not a filtered one ($merged_rows).
   */
  protected function updateArrayIndexes(
    ViewExecutable $view,
    array &$merged_row,
    int $row_index,
    int $merged_row_index
  ) : void {
    $merged_rows[$row_index] = $merged_row;
    // Getting the items per page setting from the view display.
    $items_per_page = $view->getItemsPerPage();
    $total_items = $view->total_rows;
    // Getting pager values as per merged rows.
    $merged_rows_total_num = count($merged_rows);

    if ($items_per_page > 0 && $merged_rows_total_num > $items_per_page) {
      $current_page_num = 0;
      /** @var \Drupal\views\Plugin\views\pager\PagerPluginBase|null $pager */
      $pager = $view->getPager();
      if (!is_null($pager) && !is_null($pager->getCurrentPage())) {
        $current_page_num = $pager->getCurrentPage();
      }
      $page_rows = $all_pages = 0;
      foreach ($merged_rows as $row_index => $merged_row) {
        if ($page_rows >= $items_per_page) {
          $all_pages++;
          $page_rows = 1;
        }
        else {
          $page_rows++;
        }
        // Unsetting all results but the ones from current page.
        if ($all_pages != $current_page_num) {
          $this->unsetRow($view, $row_index);
        }
      }
      // Attaching the pager with correct values.
      $this->pagerManager->createPager($merged_rows_total_num, $items_per_page);
    }
    $view->total_rows = $total_items;

    // Remove the previous highest or lowest value from the view.
    $this->unsetRow($view, $merged_row_index);
  }

}
