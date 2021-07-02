<?php

namespace Drupal\cust_group\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class ResponseSubscriber.
 *
 * Subscribe drupal events.
 *
 * @package Drupal\cust_group
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ResponseSubscriber instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = 'alterResponse';
    return $events;
  }

  /**
   * Redirect if 403 and node an event.
   *
   * @param GetResponseEvent $event
   *   The route building event.
   */
  public function alterResponse(GetResponseEvent $event) {
    if ($event->getException()->getStatusCode() == 403) {
      if (\Drupal::currentUser()->isAuthenticated()) {
        $currentPath = \Drupal::service('path.current')->getPath();
        $group = \Drupal\cust_group\CustGroupHelper::getGroupFromRouteMatch();

        if ($group) {
          $groupType = $group->getGroupType()->id();
        }

        if ($group and in_array($groupType, ['open', 'moderate'])) {

          // Generates Link to FAQ.
          $link = \Drupal\Core\Link::fromTextAndUrl(
            'FAQ',
             \Drupal\Core\Url::fromUri('internal:/betriebsportal-konsens/faq/mitgliedschaft')
             )->toString();

          if ($groupType === 'open') {
            $url = \Drupal\Core\Url::fromRoute('entity.group.join', ['group' => $group->id()]);
            // 'Die angeforderte Seite gehört zur Gruppe @group. Bitte treten Sie der Gruppe bei, um den Zugriff auf die Gruppeninhalte freizuschalten. Weitere Informationen zum Gruppenkonzept des Betriebsportal KONSENS finden Sie in unseren @faq.'
            $message = t('Die angeforderte Seite gehört zur Gruppe @group. Bitte treten Sie der Gruppe bei, um den Zugriff auf die Gruppeninhalte freizuschalten. Weitere Informationen zum Gruppenkonzept des Betriebsportal KONSENS finden Sie in unseren @faq.', ['@faq' => $link, '@group' => $group->label()]);
          }

          if ($groupType === 'moderate') {
            $url = \Drupal\Core\Url::fromRoute('entity.group.group_request_membership', ['group' => $group->id()]);
            // 'Die angeforderte Seite gehört zur moderierten Gruppe @group. Bitte beantragen Sie eine Gruppenmitgliedschaft, um den Zugriff auf die Gruppeninhalte freizuschalten. Weitere Informationen zum Gruppenkonzept des Betriebsportal KONSENS finden Sie in unseren @faq.'
            $message = t('Die angeforderte Seite gehört zur moderierten Gruppe @group. Bitte beantragen Sie eine Gruppenmitgliedschaft, um den Zugriff auf die Gruppeninhalte freizuschalten. Weitere Informationen zum Gruppenkonzept des Betriebsportal KONSENS finden Sie in unseren @faq.', ['@faq' => $link, '@group' => $group->label()]);
          }

          $joinPath = $url->toString();
          drupal_set_message($message, 'warning');
          
          global $base_url;
          header('Location: ' . $base_url . $joinPath);
          exit;
        }
      }
    }
  }
}
