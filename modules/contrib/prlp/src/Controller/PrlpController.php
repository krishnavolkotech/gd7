<?php

namespace Drupal\prlp\Controller;

use Drupal\Core\Url;
use Drupal\user\Controller\UserController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for prlp routes.
 */
class PrlpController extends UserController {

  /**
   * Override resetPassLogin() to redirect to the configured path.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $uid
   *   User ID of the user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns parent result object.
   */
  public function prlpResetPassLogin(Request $request, $uid, $timestamp, $hash) {
    $response = parent::resetPassLogin($uid, $timestamp, $hash);

    try {
      // Deconstruct the redirect url from the response.
      $parsed_url = parse_url($response->getTargetUrl());
      $response_route = Url::fromUserInput($parsed_url['path']);

      // Check that the response route matches the "success" route from core and
      // if it does apply the password change and update the redirect
      // destination.
      if ($response_route && $response_route->getRouteName() == 'entity.user.edit_form') {
        if ($request->request->has('pass') && $passwords = $request->request->get('pass')) {
          // $passwords should be an array, if that's not the case nothing
          // should be done to the user.
          $pass = is_array($passwords) ? reset($passwords) : NULL;
          if (!empty($pass)) {
            /** @var \Drupal\user\UserInterface $user */
            $user = $this->userStorage->load($uid);
            $user->setPassword($pass);
            $user->save();
            $this->messenger()->addStatus($this->t('Your new password has been saved.'));
          }
        }

        $login_destination = $this->config('prlp.settings')->get('login_destination');
        if (!$login_destination) {
          $login_destination = '/user/%user/edit';
        }
        $login_destination = str_replace('%user', $uid, $login_destination);
        $login_destination = str_replace('%front', $this->config('system.site')->get('page.front'), $login_destination);
        if (substr($login_destination, 0, 1) !== '/') {
          $login_destination = '/' . $login_destination;
        }

        return new RedirectResponse($login_destination);
      }
    }
    catch (\InvalidArgumentException $exception) {
      // This exception is an edge case scenario thrown by Url::fromUserInput()
      // Should fromUserInput() throw this treat it as a failed authentication
      // and log the user out then clear success messages and add a failure
      // message.
      user_logout();
      $this->messenger()->deleteAll();
      $this->messenger()->addError($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'));
    }

    return $this->redirect('user.pass');
  }

}
