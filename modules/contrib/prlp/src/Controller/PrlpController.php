<?php

namespace Drupal\prlp\Controller;

use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\user\Controller\UserController;
use Drupal\user\Form\UserPasswordResetForm;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($uid);

    // Verify that the user exists and is active.
    if ($user === NULL || !$user->isActive()) {
      // Blocked or invalid user ID, so deny access. The parameters will be in
      // the watchdog's URL for the administrator to check.
      throw new AccessDeniedHttpException();
    }

    // Build form to call for validation and submit handlers.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    $expiration_date = $user->getLastLoginTime() ? $this->dateFormatter->format($timestamp + $timeout) : NULL;

    $form_state = new FormState();
    $form_state->addBuildInfo('args', array_values([
      $user,
      $expiration_date,
      $timestamp,
      $hash,
    ]));

    // The form tries to redirect and returns an error due to this. Catch
    // the error in order to use the form state.
    try {
      $this->formBuilder()
        ->buildForm(UserPasswordResetForm::class, $form_state);
    }
    catch (\Exception $exception) {
    }

    if ($form_state->getErrors()) {
      // We have errors. Go back to the form.
      $session = $request->getSession();
      $session->set('pass_reset_hash', $hash);
      $session->set('pass_reset_timeout', $timestamp);
      return $this->redirect(
        'user.reset.form',
        ['uid' => $uid]
      );
    }

    // Carry on with the response checking if there are no form errors.
    $response = parent::resetPassLogin($uid, $timestamp, $hash, $request);

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
            $user->setPassword($pass);
            $user->save();
            $this->messenger()->deleteByType(MessengerInterface::TYPE_STATUS);
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
