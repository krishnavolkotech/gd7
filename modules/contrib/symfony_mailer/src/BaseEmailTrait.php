<?php

namespace Drupal\symfony_mailer;

use Symfony\Component\Mime\Header\Headers;

/**
 * Trait that implements BaseEmailInterface, writing to a Symfony Email object.
 */
trait BaseEmailTrait {

  /**
   * The inner Symfony Email object.
   *
   * @var \Symfony\Component\Mime\Email
   */
  protected $inner;

  /**
   * The email subject.
   *
   * @var \Drupal\Component\Render\MarkupInterface|string
   */
  protected $subject;

  /**
   * The addresses.
   *
   * @var array
   */
  protected $addresses = [
    'From' => [],
    'Reply-To' => [],
    'To' => [],
    'Cc' => [],
    'Bcc' => [],
  ];

  /**
   * The sender.
   *
   * @var \Drupal\symfony_mailer\AddressInterface
   */
  protected $sender;

  public function setSubject($subject) {
    // We must not force conversion of the subject to a string as this could
    // cause translation before switching to the correct language.
    $this->subject = $subject;
    return $this;
  }

  public function getSubject() {
    return $this->subject;
  }

  public function setSender($address) {
    $this->sender = Address::create($address);
    return $this;
  }

  public function getSender(): ?AddressInterface {
    return $this->sender;
  }

  public function setAddress(string $name, $addresses) {
    assert(isset($this->addresses[$name]));
    $this->addresses[$name] = Address::convert($addresses);
    return $this;
  }

  public function setFrom($addresses) {
    return $this->setAddress('From', $addresses);
  }

  public function getFrom(): array {
    return $this->addresses['From'];
  }

  public function setReplyTo($addresses) {
    return $this->setAddress('Reply-To', $addresses);
  }

  public function getReplyTo(): array {
    return $this->addresses['Reply-To'];
  }

  public function setTo($addresses) {
    $this->valid(self::PHASE_BUILD);
    return $this->setAddress('To', $addresses);
  }

  public function getTo(): array {
    return $this->addresses['To'];
  }

  public function setCc($addresses) {
    return $this->setAddress('Cc', $addresses);
  }

  public function getCc(): array {
    return $this->addresses['Cc'];
  }

  public function setBcc($addresses) {
    return $this->setAddress('Bcc', $addresses);
  }

  public function getBcc(): array {
    return $this->addresses['Bcc'];
  }

  public function setPriority(int $priority) {
    $this->inner->priority($priority);
    return $this;
  }

  public function getPriority(): int {
    return $this->inner->getPriority();
  }

  public function setTextBody(string $body) {
    $this->inner->text($body);
    return $this;
  }

  public function getTextBody(): ?string {
    return $this->inner->getTextBody();
  }

  public function setHtmlBody(?string $body) {
    $this->valid(self::PHASE_POST_RENDER, self::PHASE_POST_RENDER);
    $this->inner->html($body);
    return $this;
  }

  public function getHtmlBody(): ?string {
    $this->valid(self::PHASE_POST_SEND, self::PHASE_POST_RENDER);
    return $this->inner->getHtmlBody();
  }

  // public function attach(string $body, string $name = null, string $contentType = null);

  // public function attachFromPath(string $path, string $name = null, string $contentType = null);

  // public function embed(string $body, string $name = null, string $contentType = null);

  // public function embedFromPath(string $path, string $name = null, string $contentType = null);

  // public function attachPart(DataPart $part);

  // public function getAttachments();

  public function getHeaders(): Headers {
    return $this->inner->getHeaders();
  }

  public function addTextHeader(string $name, string $value) {
    $this->getHeaders()->addTextHeader($name, $value);
    return $this;
  }

}
