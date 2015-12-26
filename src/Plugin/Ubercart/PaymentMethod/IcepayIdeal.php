<?php

/**
 * @file
 * Contains \Drupal\uc_icepay\Plugin\Ubercart\PaymentMethod\IcepayIdeal.
 */

namespace Drupal\uc_icepay\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\Entity\PaymentMethod;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_store\Encryption;
use Drupal\uc_icepay\Plugin\IcepayApi;
use Drupal\uc_icepay\Plugin\IcepayStatics;

/**
 * Defines the check payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "icepay_ideal",
 *   name = @Translation("ICEPAY iDeal"),
 *   settings_form = "Drupal\uc_icepay\Form\IcepaySettingsForm",
 * )
 */
class IcepayIdeal extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $build = [];

//    $build['policy'] = array(
//      '#markup' => '<p>' . Html::escape($this->configuration['policy']) . '</p>'
//    );

    $build += $this->icepayIdealForm($order);
    return $build;
  }

  /**
   * Generate form fields needed for icepay ideal, such as iDeal issuers selection
   * @param OrderInterface $order
   * @return array
   */
  protected function icepayIdealForm(OrderInterface $order) {

    $icepay_static = new IcepayStatics();
    $config = \Drupal::config('uc_icepay.settings');

    $form['icepay_note'] = array(
      '#markup' => t('After order submission, you will be redirected to the ICEPAY page to complete the payment.'),
    );

    $ideal_issuers = [];
    foreach ($icepay_static->getIdealIssuersOption() as $code => $issuer) {
      if ($config->get('ideal_issuer.' . $code)) {
        $ideal_issuers[$code] = $issuer;
      }
    }

    $form['ideal_issuer'] = array(
      '#type' => 'select',
      '#title' => t('iDeal issuer'),
      '#options' => $ideal_issuers,
      '#default_value' => $order->payment_details['ideal_issuer'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {

    // Default our value for validation.
    $return = TRUE;

    if (!$form_state->hasValue(['panes', 'payment', 'details', 'ideal_issuer'])) {
      $form_state->setErrorByName('panes][payment][details][ideal_issuer', t('Please select an iDeal issuer'));
      $return = FALSE;
    }

    // We can still manipulate order when needed
    $order->payment_details = $form_state->getValue(['panes', 'payment', 'details']);

    $icepay_config = \Drupal::config('uc_icepay.settings');
    $order->currency = $icepay_config->get('currency');

    // need to save this order into table uc_payment_icepay with status cart_checkout
    $api = new IcepayApi();
    $api->enterPayment($order);

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    $review = [];

    // get selected ideal issuer on cart/checkout
    $ideal_issuer_code = $order->payment_details['ideal_issuer'];

    $icepay_static = new IcepayStatics();

    $review[] = array('title' => t('Payment method'), 'data' => "iDeal " . $icepay_static->getSingleIdealIssuer($ideal_issuer_code));

    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $build = array();

    $rows = array();

    if (!empty($order->payment_details['ideal_issuer'])) {
      $icepay_static = new IcepayStatics();
      $rows[] = t('iDeal issuer') . ': ' . $icepay_static->getSingleIdealIssuer($order->payment_details['ideal_issuer']);
    }

    $build['ideal_issuer'] = array(
      '#markup' => implode('<br />', $rows),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function customerView(OrderInterface $order) {
    $build = array();

    if (!empty($order->payment_details['ideal_issuer'])) {
      $icepay_static = new IcepayStatics();
      $build['#markup'] = t('iDeal issuer') . ':<br />' . $icepay_static->getSingleIdealIssuer($order->payment_details['ideal_issuer']);
    }

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(OrderInterface $order) {
    return $this->t('Edit iDeal issuer for this payment');
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(OrderInterface $order) {

    $api = new IcepayApi();
    $result = $api->getPaymentDetails($order);

    $order->payment_details = array(
      'ideal_issuer' => $result->ideal_issuer,
    );

  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(OrderInterface $order) {

  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
    // lets prepare datas and redirect to icepay

    $api = new IcepayApi();
    $api->submitOrderUsingIcepayPayment($order);

  }

  /**
   * {@inheritdoc}
   */
  public function orderDelete(OrderInterface $order) {
    // delete payment transaction recorded in table {uc_payment_icepay}
    Database::getConnection()
      ->delete('uc_payment_icepay')
      ->condition('order_id', $order->id())
      ->execute();
  }

}
