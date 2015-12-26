<?php
/**
 * @file to connect drupal plugin to Icepay API
 */

namespace Drupal\uc_icepay\Plugin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\Entity\PaymentMethod;

require_once(drupal_get_path('module', 'uc_icepay') .'/api/icepay_api_basic.php');

class IcepayApi {

  /**
   * Convert payment plugin id into valid Icepay payment method name
   *
   * @param string $payment_plugin_id
   * @return string
   */
  public function paymentPluginToIcepayPaymentMethod($payment_pluginId) {
    // all need to be done is removing prefix 'icepay_'
    return substr($payment_pluginId, 7, strlen($payment_pluginId) - 7);
  }

  public function getPaymentPluginId($uc_payment_methodId) {
    return PaymentMethod::load($uc_payment_methodId)->getPlugin()->getPluginId();
  }

  /**
   * Grab Icepay payment method complete setup based on provided name
   *
   * @param string $payment_method
   * @return object
   */
  public function getIcepayPaymentMethodClass($payment_pluginId) {
    $paymentMethod = paymentPluginToIcepayPaymentMethod($payment_pluginId);

    return \Icepay_Api_Basic::getInstance()
      ->readFolder()
      ->getClassByPaymentmethodCode($paymentMethod);
  }

  /**
   * Get the payment url
   *
   * @param OrderInterface $order
   * @return string url
   */
  public function getPaymentURL(OrderInterface $order)
  {

    $icepay_config = \Drupal::config("uc_icepay.settings");

    $country = $icepay_config->get("country");
    $language = $icepay_config->get("language");
    $merchant_id = $icepay_config->get("merchant_id");
    $secret_code = $icepay_config->get("secret_code");
    $protocol = $icepay_config->get("https_protocol");

    $paymentObj = new \Icepay_PaymentObject();
    $paymentObj->setOrderID($order->id() . date('is'))
      ->setReference($order->id())
      ->setAmount(intval($order->getTotal() * 100))
      ->setCurrency($order->getCurrency())
      ->setCountry($country)
      ->setLanguage($language);

    $payment_plugin_id = IcepayApi::getPaymentPluginId($order->getPaymentMethodId());
    $payment_method = IcepayApi::paymentPluginToIcepayPaymentMethod($payment_plugin_id);

    $paymentObj->setPaymentMethod($payment_method);

    if (isset($order->payment_details['ideal_issuer'])) {
      $paymentObj->setIssuer($order->payment_details['ideal_issuer']);
    }

    $basicmode = \Icepay_Basicmode::getInstance();
    $basicmode->setMerchantID($merchant_id)
      ->setSecretCode($secret_code)
      ->validatePayment($paymentObj);

    $protocol = ($protocol == true) ? 'https' : 'http';
    $basicmode->setProtocol($protocol);

    return $basicmode->getURL();
  }

  /**
   * Enter new payment or update old one in icepay table
   *
   * @param OrderInterface $data
   */
  public function enterPayment(OrderInterface $order) {

    $result = IcepayApi::getPaymentDetails($order);

    $icepay_status = isset($order->icepay_status) ? $order->icepay_status : \ICEPAY_STATUSCODE::OPEN;

    if (!$result) {
      // insert as new
      $fields = [
        'order_id' => $order->id(),
        'payment_plugin' => IcepayApi::getPaymentPluginId($order->getPaymentMethodId()),
        'payment_method' => $order->getPaymentMethodId(),
        'ideal_issuer' => $order->payment_details['ideal_issuer'],
        'icepay_status' => $icepay_status,
        'transaction_id' => $order->transaction_id,
        'created' => REQUEST_TIME,
        'updated' => REQUEST_TIME
      ];
      Database::getConnection()
        ->insert('uc_payment_icepay')
        ->fields($fields)
        ->execute();
    }
    else {
      // otherwise run update
      $fields = [
        'payment_plugin' => IcepayApi::getPaymentPluginId($order->getPaymentMethodId()),
        'payment_method' => $order->getPaymentMethodId(),
        'ideal_issuer' => $order->payment_details['ideal_issuer'],
        'icepay_status' => $icepay_status,
        'transaction_id' => $order->transaction_id,
        'updated' => REQUEST_TIME
      ];
      Database::getConnection()
        ->update('uc_payment_icepay')
        ->condition('order_id', $order->id())
        ->fields($fields)
        ->execute();
    }

  }

  /**
   * Get payment details stored within table {uc_payment_icepay}
   *
   * @param OrderInterface $order
   * @return mixed
   */
  public function getPaymentDetails(OrderInterface $order) {
    $result = Database::getConnection()
      ->select('uc_payment_icepay', 'i')
      ->fields('i')
      ->condition("i.order_id", $order->id())
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (isset($result)) {
      return $result;
    }

    return false;
  }

  /**
   * Get payment details stored within table {uc_payment_icepay} based on given id
   *
   * @param int $orderId
   * @return mixed
   */
  public function getPaymentDetailsByOrderId(int $orderId) {
    $order = uc_order_load($orderId);
    if ($order) {
      return IcepayApi::getPaymentDetails($order);
    }
    return false;
  }

  /**
   * When order is submitted, send user to Icepay page to complete payment process
   *
   * @param OrderInterface $order
   */
  public function submitOrderUsingIcepayPayment(OrderInterface $order) {
    $order->icepay_status = \ICEPAY_STATUSCODE::OPEN;
    $order->icepay_transactionID = null;

    IcepayApi::enterPayment($order);

    $url = IcepayApi::getPaymentURL($order);

    header("location: {$url}");
    exit();

  }

  public function getUbercartStatusCode($status = \Icepay_StatusCode::OPEN) {
    return "icepay_" . strtolower($status);
  }

  /**
   * Handler when cart/icepay_result is callback
   *
   * @return string
   */
  public function runPageCartResult() {

    $logger = \Icepay_Api_Logger::getInstance();
    $logger->enableLogging()
      ->setLoggingLevel(\Icepay_Api_Logger::LEVEL_ERRORS_AND_TRANSACTION)
      ->logToFunction("logWrapper", "log");

    $config = \Drupal::config("uc_icepay.settings");

    /* postback */

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

      $icepay = \Icepay_Project_Helper::getInstance()->postback();
      $icepay->setMerchantID($config->get("merchant_id"))
        ->setSecretCode($config->get("secret_code"))
        ->doIPCheck(true);

      if ($config->get("ipcheck") && ($config->get("ipcheck_list") != '')) {
        $ipRanges = explode(",", $config->get("ipcheck_list"));

        foreach ($ipRanges as $ipRange) {
          $ip = explode("-", $ipRange);
          $icepay->setIPRange($ip[0], $ip[1]);
        }
      }

      if ($icepay->validate()) {
        $data = $icepay->GetPostback();
        $orderID = $data->reference;

        $order = uc_order_load($orderID);

        if (!$order) {
          return t("Order not exists");
        }

        $firstPostback = Database::getConnection()
          ->select('uc_payment_icepay', 'i')
          ->fields('i', array('transaction_id'))
          ->condition('transaction_id', $data->transactionID, '=')
          ->execute()
          ->fetchAssoc();

        $paymentDetails = IcepayApi::getPaymentDetailsByOrderId($orderID);

        if ($icepay->canUpdateStatus($paymentDetails->icepay_status)) {
          $order->icepay_status = $data->status;
          $order->transaction_id = $data->transactionID;

          IcepayApi::enterPayment($order);

          // updating order status, this one is deprecated
          //uc_order_update_status($orderID, IcepayApi::getUbercartStatusCode($data->status));

          // updating order status, using direct save into order
          $order->setStatusId(IcepayApi::getUbercartStatusCode($data->status))->save();
        }

        // adding new comment order
        uc_order_comment_save($orderID, 1, t($data->statusCode), 'order', IcepayApi::getUbercartStatusCode($data->status), true);

        // need to save into order payment if postback from Icepay is confirming payment received
        // @see Drupal/uc_payment/Form/OrderPaymentsForm::submitForm()
        if (strtoupper($data->status) == "OK" || strtoupper($data->status) == "REFUND") {
          $orderTotal = $order->getTotal();
          // when refund, means order total is requested back
          if (strtoupper($data->status) == "REFUND") {
            $orderTotal *= -1;
          }
          uc_payment_enter($orderID,
            $paymentDetails->payment_method, $orderTotal,
            \Drupal::currentUser()->id(), '',
            $data->statusCode, REQUEST_TIME
          );
        }

        // best to record this into watch log
        // https://drupalize.me/blog/201510/how-log-messages-drupal-8
        \Drupal::logger('uc_icepay')->info('Icepay Postback :: ' . $data->statusCode);

        // need to send notification due to order status update
        if (isset($firstPostback['transaction_id'])) {
          // this rules invoke to send order status update by email is deprecated
          //rules_invoke_event('uc_order_status_email_update', $order);
        }

      } else {

        if ($icepay->isVersionCheck()) {
          $dump = array(
            "module" => sprintf(t("Version %s using PHP API 2 version %s"), ICEPAY_VERSION, Icepay_Project_Helper::getInstance()->getReleaseVersion()), //<--- Module version and PHP API version
            "notice" => "Checksum validation passed!"
          );

          if ($icepay->validateVersion()) {

            $name = "uc_cart";
            $path = drupal_get_path('module', $name) . '/' . $name . '.info';
            $data = drupal_parse_info_file($path);

            $dump["additional"] = array(
              "Drupal" => VERSION, //<--- CMS name & version
              "Ubercart" => $data["version"] //<--- Webshop name & version
            );
          } else {
            $dump["notice"] = "Checksum failed! Merchant ID and Secret code probably incorrect.";
          }
          var_dump($dump);
          exit();
        }
      }

      return t("Postback script functions properly");

    } else {

      $icepay = \Icepay_Project_Helper::getInstance()->result();
      $icepay->setMerchantID($config->get("merchant_id"))
        ->setSecretCode($config->get("secret_code"));

      if (!$icepay->validate()) {
        $data = $icepay->getResultData();

        //$output = $data->statusCode;
        //return $output;

        drupal_set_message($data->statusCode, 'error');

        $response = new RedirectResponse(\Drupal::url('uc_cart.checkout'));
        $response->send();

      } else {
        $data = $icepay->getResultData();

        if ($data->status == 'ERR') {
          //$output = $data->statusCode;
          //return $output;

          drupal_set_message($data->statusCode, 'error');

          return new RedirectResponse(\Drupal::url('uc_cart.checkout'));

        }

        $order = uc_order_load($data->reference);

        if (!$order) {
          return t("Order with id :orderId not exist", array(":orderId" => $data->reference));
        }
        $session = \Drupal::service('session');

        if (!$session->get('cart_order')) {

          drupal_set_message(t("Cart is currently empty."), 'error');

          return new RedirectResponse(\Drupal::url('uc_cart.checkout'));
        }

        //$order->icepay_status = \ICEPAY_STATUSCODE::SUCCESS;
        $order->icepay_status = $data->status;
        $order->transaction_id = $data->transactionID;
        IcepayApi::enterPayment($order);

        // update order status
        $order->setStatusId(IcepayApi::getUbercartStatusCode($data->status))->save();

        $_SESSION['uc_checkout'][$session->get('cart_order')]['do_complete'] = TRUE;

//        $response = new RedirectResponse(Url::fromRoute('uc_cart.checkout_complete')->toString());
//        $response->send();
        return new RedirectResponse(\Drupal::url('uc_cart.checkout_complete'));

      }
    }
  }

}