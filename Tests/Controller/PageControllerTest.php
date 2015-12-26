<?php

/**
 * @file
 * Contains \Drupal\uc_icepay\Tests\PageController.
 */

namespace Drupal\uc_icepay\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the uc_icepay module.
 */
class PageControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "uc_icepay PageController's controller functionality",
      'description' => 'Test Unit for module uc_icepay and controller PageController.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests uc_icepay functionality.
   */
  public function testPageController() {
    // Check that the basic functions of module uc_icepay.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
