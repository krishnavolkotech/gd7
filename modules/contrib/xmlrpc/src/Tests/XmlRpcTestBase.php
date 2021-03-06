<?php

namespace Drupal\xmlrpc\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * A base class simplifying xmlrpc() calls testing.
 */
abstract class XmlRpcTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Provides detailed response information if verbose is enabled.
   *
   * @param mixed $result
   *   A XML-RPC result.
   */
  protected function verboseResult($result) {
    if ($result === FALSE) {
      $this->verbose(new FormattableMarkup('Result: <pre>@result</pre><br />Errno: @errno<br />Message: @message', [
        '@result' => var_export($result, TRUE),
        '@errno' => xmlrpc_errno(),
        '@message' => xmlrpc_error_msg(),
      ]));
    }
    else {
      $this->verbose('<pre>' . var_export($result, TRUE) . '</pre>');
    }
  }

  /**
   * Invokes xmlrpc method.
   *
   * @param array $args
   *   An associative array whose keys are the methods to call and whose values
   *   are the arguments to pass to the respective method. If multiple methods
   *   are specified, a system.multicall is performed.
   * @param array $headers
   *   (optional) An array of headers to pass along.
   *
   * @return mixed
   *   The result of xmlrpc() function call.
   *
   * @see xmlrpc()
   */
  protected function xmlRpcGet(array $args, array $headers = []) {

    $url = Url::fromRoute('xmlrpc', [], ['absolute' => TRUE])->toString();

    $result = xmlrpc($url, $args, $headers);
    $this->verboseResult($result);
    return $result;
  }

}
