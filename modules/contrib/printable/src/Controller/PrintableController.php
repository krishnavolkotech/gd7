<?php

/**
 * @file
 * Contains \Drupal\printable\Controller\PrintableController.
 */

namespace Drupal\printable\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\printable\PrintableFormatPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller to display an entity in a particular printable format.
 */
class PrintableController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The printable format plugin manager.
   *
   * @var \Drupal\printable\PrintableFormatPluginManager
   */
  protected $printableFormatManager;

  /**
   * Constructs a \Drupal\printable\Controller\PrintableController object.
   *
   * @param \Drupal\printable\PrintableFormatPluginManager $printable_format_manager
   *   The printable format plugin manager.
   */
  public function __construct(PrintableFormatPluginManager $printable_format_manager) {
    $this->printableFormatManager = $printable_format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('printable.format_plugin_manager')
    );
  }

  /**
   * Returns the entity rendered via the given printable format.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be printed.
   * @param string $printable_format
   *   The identifier of the hadcopy format plugin.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The printable response.
   */
  public function showFormat(EntityInterface $entity, $printable_format) {
    $node = \Drupal::routeMatch()->getParameter('entity');
    if ($node->getType() == 'quickinfo') {
      $checkGroupNode = \Drupal::database()->select('group_content_field_data', 'gcfd')
              ->fields('gcfd', ['gid'])
              ->condition('gcfd.entity_id', $node->id())
              ->execute()->fetchField();
      $access_status = \Drupal\cust_group\Controller\CustNodeController::hzdGroupAccess($checkGroupNode);
      if (!$access_status) {
        throw new AccessDeniedHttpException();
      }
    }

    if ($this->printableFormatManager->getDefinition($printable_format)) {
      $format = $this->printableFormatManager->createInstance($printable_format);
      $content = $this->entityManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, 'printable');
      $format->setContent($content);
      if ($printable_format == 'print') {
        return $format->getResponse();
      }
      else {
        $format->getResponse($content);
        $source_url = \Drupal::request()->getRequestUri();
        $pos = strpos($source_url, "printable");
        $pos_node = strpos($source_url, '/', $pos + 11);
        $source_url = substr($source_url, 0, $pos) . substr($source_url, $pos_node + 1);
        return new RedirectResponse($source_url);
      }
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
