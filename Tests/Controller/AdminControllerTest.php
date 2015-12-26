<?php

/**
 * @file
 * Contains \Drupal\uc_icepay\Tests\AdminController.
 */

namespace Drupal\uc_icepay\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the uc_icepay module.
 */
class AdminControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "uc_icepay AdminController's controller functionality",
      'description' => 'Test Unit for module uc_icepay and controller AdminController.',
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
  public function testAdminController() {
    // Check that the basic functions of module uc_icepay.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
