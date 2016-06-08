<?php
namespace Drupal\reroute_email;

/**
 * Test default reroute destination email address when it is empty or unset.
 */
class RerouteEmailDefaultAddressTestCase extends RerouteEmailTestCase {

  /**
   * Implements DrupalWebTestCase::getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Default or empty reroute destination email address',
      'description' => "When reroute email addresses field is not configured, attempt to use the site email address, otherwise use sendmail_from system variable.",
      'group' => 'Reroute Email',
    );
  }

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
    // Add more permissions to access recent log messages in test.
    $this->permissions[] = 'access site reports';
    parent::setUp();
  }

  /**
   * Test reroute email address is set to site_mail, sendmail_from or empty.
   *
   * When reroute email addresses field is not configured and settings haven't
   * been configured yet, check if the site email address or the sendmail_from
   * system variable are properly used as fallbacks. Additionally, check that
   * emails are aborted and a watchdog entry logged if reroute email address is
   * set to an empty string.
   */
  public function testRerouteDefaultAddress() {
    // Check default value for reroute_email_address when not configured.
    // If Site email is not empty, it should be the default value.
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $default_destination = variable_get('site_mail', NULL);

    $this->assertTrue(isset($default_destination), format_string('Site mail is not empty: @default_destination.', array('@default_destination' => $default_destination)));

    // Programmatically enable email rerouting.
    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// variable_set(REROUTE_EMAIL_ENABLE, TRUE);

    // Load the Reroute Email Settings form page. Ensure rerouting is enabled.
    $this->drupalGet("admin/config/development/reroute_email/reroute_email");
    $this->assertFieldByName(REROUTE_EMAIL_ENABLE, TRUE, 'Email rerouting was programmatically successfully enabled.');
    // Check Email addresses field default value should be site_mail.
    $this->assertFieldByName(REROUTE_EMAIL_ADDRESS, $default_destination, format_string('Site email address is configured and is the default value of the Email addresses field: @default_destination.', array('@default_destination' => $default_destination)));

    // Ensure reroute_email_address is not set yet.
    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// $reroute_email_address = variable_get(REROUTE_EMAIL_ADDRESS, NULL);

    $this->assertFalse(isset($reroute_email_address), 'Reroute email destination address is not configured.');

    // Submit a test email and check if it is rerouted to site_mail address.
    $this->drupalPost("admin/config/development/reroute_email/test", array('to' => "to@example.com"), t("Send email"));
    $this->assertText(t("Test email submitted for delivery."));
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // Check rerouted email is the site email address.
    $this->assertMail('to', $default_destination, format_string('Email was properly rerouted to site email address: @default_destination.', array('@default_destination' => $default_destination)));

    // Now unset site_mail to check if system sendmail_from is properly used.
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// variable_del('site_mail');

    // If it is defined, try to test the default sendmail_from system variable.
    $system_email = ini_get('sendmail_from');
    // Fallback to default placeholder if no system variable configured.
    $default_destination = empty($system_email) ? REROUTE_EMAIL_ADDRESS_EMPTY_PLACEHOLDER : $system_email;

    // Reload the Reroute Email Settings form page.
    $this->drupalGet("admin/config/development/reroute_email/reroute_email");
    // Check Email addresses field default value should be system default.
    $this->assertFieldByName('reroute_email_address', $system_email, format_string('Site email address is not configured, Email addresses field defaults to system sendmail_from: <em>@default_destination</em>.', array('@default_destination' => $system_email)));

    // Submit a test email to check if it is rerouted to sendmail_from address.
    $this->drupalPost("admin/config/development/reroute_email/test", array('to' => "to@example.com"), t("Send email"));
    $this->assertText(t("Test email submitted for delivery."));
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // Check rerouted email is the system sendmail_from email address.
    $this->assertMail('to', $default_destination, format_string('Email was properly rerouted to system sendmail_from email address: @default_destination.', array('@default_destination' => $default_destination)));

    // Configure reroute email address to be emtpy: ensure emails are aborted.
    $this->configureRerouteEmail('');

    // Make sure reroute_email_address variable is an empty string.
    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// $reroute_email_address = variable_get(REROUTE_EMAIL_ADDRESS, NULL);

    $this->assertTrue(is_string($reroute_email_address), 'Reroute email destination address is configured to be an empty string.');
    // Flush the Test Mail collector to ensure it is empty for this tests.
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// variable_set('drupal_test_email_collector', array());


    // Submit a test email to check if it is aborted.
    $this->drupalPost("admin/config/development/reroute_email/test", array('to' => "to@example.com"), t("Send email"));
    $mails = $this->drupalGetMails();
    $mail_aborted = end($mails);
    $this->assertFalse($mail_aborted, 'Email sending was properly aborted because rerouting email address is an empty string.');
    // Check status message is displayed properly after email form submission.
    $this->assertRaw(t('<em>@message_id</em> was aborted by reroute email, please check the <a href="@dblog">recent log entries</a> for complete details on the rerouted email.', array('@message_id' => $mail['id'], '@dblog' => \Drupal\Core\Url::fromRoute('dblog.overview'))), format_string('Status message displayed as expected to the user with the mail ID <em>(@message_id)</em> and a link to recent log entries.', array('@message_id' => $mail['id'])));

    // Check the watchdog entry logged with aborted email message.
    $this->drupalGet('admin/reports/dblog');
    // Check the link to the watchdog detailed message.
    $watchdog_link = $this->xpath('//table[@id="admin-dblog"]/tbody/tr[contains(@class,"dblog-reroute-email")][1]/td[text()="reroute_email"]/following-sibling::td/a[contains(text(),"reroute_email")]');
    $link_label = (string) $watchdog_link[0];
    $this->assertTrue(isset($watchdog_link[0]), format_string("Recorded successfully a watchdog log entry in recent log messages: <em>@link</em>.", array('@link' => $link_label)));
    // Open the full view page of the log message found for reroute_email.
    $this->clickLink($link_label);

    // Recreate expected logged message based on email submitted previously.
    $mail['send'] = FALSE;
    $mail['body'] = array($mail['body'], NULL);
    // Ensure the correct email is logged with default 'to' placeholder.
    $mail['to'] = REROUTE_EMAIL_ADDRESS_EMPTY_PLACEHOLDER;
    $this->assertRaw(t('Aborted email sending for <em>@message_id</em>. <br/>Detailed email data: Array $message <pre>@message</pre>', array(
      '@message_id' => $mail['id'],
      '@message' => print_r($mail, TRUE),
    )), format_string('The log entry recorded by Reroute Email contains a full dump of the aborted email message <em>@message_id</em> and is formatted as expected.', array('@message_id' => $mail['id'])));
  }
}
