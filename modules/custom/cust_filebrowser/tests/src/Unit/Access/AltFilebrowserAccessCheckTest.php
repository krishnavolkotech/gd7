<?php

namespace Drupal\Tests\cust_filebrowser\Unit\Access;

use Drupal\Tests\UnitTestCase;
use Drupal\cust_filebrowser\Access\AltFilebrowserAccessCheck;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Access\AccessResultAllowed;

/**
 * Skeleton functional test.
 *
 * @group testing_example
 * @group examples
 */
class AltFilebrowserAccessCheckTest extends UnitTestCase {

  /**
   * Dataprovider for testAccess.
   */
  public function provideTestAccess() {
    $groupMock = $this->createMock('Drupal\group\Entity\Group');

    $accessResultNeutral = new AccessResultNeutral();
    $accessResultNeutral->setReason("The 'delete files' permission is required.")
      ->addCacheContexts(['user.permissions']);

    return [
      [new AccessResultAllowed(), $groupMock],
      [$accessResultNeutral, NULL],
    ];
  }

  /**
   * Tests access method.
   *
   * @dataProvider provideTestAccess
   */
  public function testAccess($expected, $groupMock) {
    // Mock dependency RouteMatchInterface.
    $map = [
      ['nid', 2],
      ['op', 'delete'],
    ];
    $routeMatchInterface = $this->createMock('Drupal\Core\Routing\RouteMatchInterface');
    $routeMatchInterface->expects($this->any())
      ->method('getParameter')
      ->will($this->returnValueMap($map));

    // Mock dependency AccountInterface.
    $accountInterface = $this->createMock('Drupal\Core\Session\AccountInterface');

    // Mock dependency FilebrowserHelper.
    $filebrowserHelper = $this->createMock('Drupal\cust_filebrowser\Services\FilebrowserHelper');
    $filebrowserHelper
      ->expects($this->once())
      ->method('getGroupFromNodeId')
      ->willReturn($groupMock);
    $filebrowserHelper->expects($this->any())
      ->method('checkGroupPermission')
      ->willReturn(new AccessResultAllowed());

    // Mock the class, that provides the method we want to test.
    $accessChecker = $this->getMockBuilder(AltFilebrowserAccessCheck::class)
      ->setConstructorArgs([$filebrowserHelper])
      ->setMethods(NULL)
      ->getMock();

    $this->assertEquals($expected, $accessChecker->access($routeMatchInterface, $accountInterface));
  }

}
