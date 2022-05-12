<?php

namespace Drupal\symfony_mailer;

use Drupal\Core\Session\AccountInterface;
use Drupal\simplenews\SubscriberInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;

/**
 * Defines the class for an Email address.
 *
 * This class is used for the address headers on an email. For the to address,
 * it encodes extra information to customise the email for the recipients:
 * langcode and account.
 */
class Address implements AddressInterface {

  protected $email;
  protected $displayName;
  protected $langcode;
  protected $address;

  /**
   * Constructs an address object.
   *
   * @param string $email
   *   The email address.
   * @param string $display_name
   *   (Optional) The display name.
   * @param string $langcode
   *   (Optional) The language code.
   * @param \Drupal\Core\Session\AccountInterface $address
   *   (Optional) The account.
   */
  public function __construct(string $email, string $display_name = NULL, string $langcode = NULL, AccountInterface $address = NULL) {
    $this->email = $email;
    $this->displayName = $display_name;
    $this->langcode = $langcode;
    $this->account = $address;
  }

  /**
   * {@inheritdoc}
   */
  public static function create($address) {
    if ($address instanceof AddressInterface) {
      return $address;
    }
    elseif (is_string($address)) {
      if ($address == '<site>') {
        $site_config = \Drupal::config('system.site');
        $site_mail = $site_config->get('mail') ?: ini_get('sendmail_from');
        return new static($site_mail, $site_config->get('name'));
      }
      else {
        return new static($address);
      }
    }
    elseif ($address instanceof AccountInterface) {
      return new static($address->getEmail(), $address->getDisplayName(), $address->getPreferredLangcode(), $address);
    }
    elseif ($address instanceof SymfonyAddress) {
      return new static($address->getAddress(), $address->getName());
    }
    else {
      throw new LogicException('Cannot convert to address.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayName() {
    return $this->displayName;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function getSymfony() {
    return new SymfonyAddress($this->email, $this->displayName ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public static function convert($addresses) {
    if (!is_array($addresses)) {
      $addresses = [$addresses];
    }

    foreach ($addresses as $address) {
      $result[] = static::create($address);
    }

    return $result;
  }

};
