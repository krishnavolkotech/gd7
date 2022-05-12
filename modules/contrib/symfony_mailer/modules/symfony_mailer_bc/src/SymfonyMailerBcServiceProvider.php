<?php

namespace Drupal\symfony_mailer_bc;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies the mail manager service.
 */
class SymfonyMailerBcServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('plugin.manager.mail');
    $definition->setClass('Drupal\symfony_mailer_bc\MailManagerReplacement')
      ->addArgument(new Reference('email_factory'))
      ->addArgument(new Reference('symfony_mailer.helper'))
      ->addArgument(new Reference('plugin.manager.email_builder'));
  }

}
