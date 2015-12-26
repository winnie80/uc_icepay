<?php

/**
 * @file
 * Contains \Drupal\uc_icepay\Controller\AdminController.
 */

namespace Drupal\uc_icepay\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class AdminController.
 *
 * @package Drupal\uc_icepay\Controller
 */
class AdminController extends ControllerBase {
  /**
   * Index.
   *
   * @return string
   *   Return Hello string.
   */
  public function index() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: index')
    ];
  }

}
