<?php

/**
 * @file
 * Contains \Drupal\uc_icepay\Controller\PageController.
 */

namespace Drupal\uc_icepay\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_icepay\Plugin\IcepayApi;
use Drupal\uc_icepay\Plugin\IcepayStatics;

/**
 * Class PageController.
 *
 * @package Drupal\uc_icepay\Controller
 */
class PageController extends ControllerBase {
  /**
   * Index.
   *
   * @return string
   *   Return Hello string.
   */
  public function index($name = 'unnamed') {
    return [
        '#type' => 'markup',
        '#markup' => $this->t('Implement method: index with parameter(s): %name', ['%name' => $name])
    ];
  }
  /**
   * Banks.
   *
   * @return string
   *   Return Hello string.
   */
  public function banks() {

    $static = new IcepayStatics();
    $issuers = $static->getIdealIssuersOption();

    return [
        '#type' => 'markup',
        '#markup' => $this->t('Available iDeal issuers on ICEPAY provider') .': <ul><li>' . implode('</li><li>', $issuers) .'</li></ul>'
    ];
  }

  /**
   * Page callback by ICEPAY as return handler during checkout submit process, cart/icepay_result
   * @return string
   */
  public function cart_result() {

    $printStatusCode = "System can not find any Icepay payment datas.";

    if ($_GET['Status']) {
      $api = new IcepayApi();
      $printStatusCode = $api->runPageCartResult();

      if (!is_string($printStatusCode)) {
        return $printStatusCode;
      }
    }

    return [
      '#type' => 'markup',
      '#markup' => '<h2>'. $this->t("Error on processing Icepay payment.") . '</h2>' . $printStatusCode,
    ];
  }

  /**
   * Page callback by ICEPAY for posting cashback or refund request
   */
  public function cart_postback() {

    $printStatusCode = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $api = new IcepayApi();
      $printStatusCode = $api->runPageCartResult();

      if (!is_string($printStatusCode)) {
        return $printStatusCode;
      }
    }

    return [
      '#type' => 'markup',
      '#markup' => 'ICEPAY postback page ' . $printStatusCode,
    ];
  }

}
