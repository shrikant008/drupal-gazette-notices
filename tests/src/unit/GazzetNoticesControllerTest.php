<?php

namespace Drupal\Tests\gazzet_notices\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\gazzet_notices\Controller\GazzetNoticesController;

/**
 * @coversDefaultClass \Drupal\gazzet_notices\Controller\GazzetNoticesController
 * @group gazette_notices
 */
class GazzetNoticesControllerTest extends UnitTestCase {

  /**
   * @covers ::__invoke
   */
  public function testInvokeReturnsArray() {
    $controller = new GazzetNoticesController();
    $result = $controller->__invoke();
    $this->assertIsArray($result);
    $this->assertArrayHasKey('content', $result);
  }

}