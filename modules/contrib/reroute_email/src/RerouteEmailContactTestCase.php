<?php
namespace Drupal\reroute_email;

/**
 * Tests email rerouting for the site-wide Core Contact form.
 */
class RerouteEmailContactTestCase extends RerouteEmailTestCase {

  /**
   * Implements DrupalWebTestCase::getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Site-wide Core Contact form email rerouting',
      'description' => "Test Reroute Email module's ability to reroute mail sent from the Core Contact module forms.",
      'group' => 'Reroute Email',
    );
  }

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
    // Add more permissions to be able to manipulate the contact forms.
    $this->permissions[] = 'administer contact forms';
    $this->permissions[] = 'access site-wide contact form';
    // Include Core Contact module.
    parent::setUp('contact');
  }

  /**
   * Basic tests of email rerouting for emails sent through the Contact forms.
   *
   * The Core Contact email form is submitted several times with different
   * Email Rerouting settings: Rerouting enabled or disabled, Body injection
   * enabled or disabled, several recipients with or without whitelist.
   */
  public function testBasicNotification() {
    // Additional destination email address used for testing the whitelist.
    $additional_destination = "additional@example.com";

    // Configure to reroute normally to rerouted@example.com.
    $this->configureRerouteEmail();

    // Configure the contact settings to send to $original_destination.
    $this->drupalPost('admin/structure/contact/edit/1', array('recipients' => $this->originalDestination), t('Save'));

    // Go to the contact page and send an email.
    $post = array('subject' => "Test test test", 'message' => 'This is a test');
    $this->drupalPost("contact", $post, t("Send message"));
    $this->assertText(t("Your message has been sent"));
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    $this->assertMail('to', $this->rerouteDestination, format_string("Email was rerouted to @address", array('@address' => $this->rerouteDestination)));
    // Check if original destination email address is in rerouted email body.
    $this->assertOriginallyTo($mail['body'], 'Found the correct "Originally to" line in the body');
    $this->assertTrue(strpos($mail['body'], 'Originally to') !== FALSE, 'Body does contain "Originally to"');

    // Now try sending to one of the additional email addresses that should
    // not be rerouted. Configure two email addresses in reroute form.
    // Body injection is still turned on.
    $this->configureRerouteEmail("{$this->rerouteDestination}, $additional_destination");

    // Configure the contact settings to point to the additional recipient.
    $this->drupalPost('admin/structure/contact/edit/1', array('recipients' => $additional_destination), t('Save'));

    // Go to the contact page and send an email.
    $post = array('subject' => "Test test test", 'message' => 'This is a test');
    $this->drupalPost("contact", $post, t("Send message"));
    $this->assertText(t("Your message has been sent"));
    $mails = $this->drupalGetMails();
    $mail = end($mails);;
    $this->assertMail('to', $additional_destination, 'Email was not rerouted because destination was in whitelist');

    // Now change the configuration to disable reroute and set the original
    // email recipients.
    $this->configureRerouteEmail(NULL, FALSE);

    // Set the contact form to send to original_destination.
    $this->drupalPost('admin/structure/contact/edit/1', array('recipients' => $this->originalDestination), t('Save'));
    // Go to the contact page and send an email.
    $post = array('subject' => "Test test test", 'message' => 'This is a test');
    $this->drupalPost("contact", $post, t("Send message"));
    $this->assertText(t("Your message has been sent"));
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // Mail should not be rerouted - should go to $original_destination.
    $this->assertMail('to', $this->originalDestination, 'Mail not rerouted - sent to original destination.');
    $this->verbose(t("Email 'to' was: <pre>@mail_to</pre>", array('@mail_to' => $mail['to'])));

    // Configure to reroute without body injection.
    $this->configureRerouteEmail(NULL, TRUE, FALSE);

    // Go to the contact page and send an email.
    $post = array('subject' => "Test test test", 'message' => 'This is a test');
    $this->drupalPost("contact", $post, t("Send message"));
    $this->assertText(t("Your message has been sent"));
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // There should be nothing in the body except the contact message - no
    // body injection like 'Originally to'.
    $this->assertTrue(strpos($mail['body'], 'Originally to') === FALSE, 'Body does not contain "Originally to"');
    $this->assertTrue($mail['headers']['X-Rerouted-Original-To'] == $this->originalDestination, 'X-Rerouted-Original-To is correctly set to the original destination email');
  }
}
