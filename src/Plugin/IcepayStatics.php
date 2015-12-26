<?php
/**
 * @file
 * Contains \Drupal\uc_icepay\Plugin\IcepayStatics.
 */

namespace Drupal\uc_icepay\Plugin;

class IcepayStatics {

  private $icepay_url = 'pay.icepay.eu';
  private $icepay_type = 'basic';

  private $config;

  public function __construct() {
    $this->config = \Drupal::config('uc_icepay.settings');
  }

  public function getPostUrl() {
    $protocol = $this->config->get('uc_icepay_https_protocol') == 1 ? 'https' : 'http';
    return "{$protocol}://{$this->icepay_url}/{$this->icepay_type}/";
  }

  /**
   * ICEPAY Supported Languages
   * @return array
   */
  protected function supportedLanguages() {
    return array(
      array('code' => 'NL', 'descr'=> 'Dutch'),
      array('code' => 'DE', 'descr'=> 'German'),
      array('code' => 'EN', 'descr'=> 'English'),
      array('code' => 'FR', 'descr'=> 'French'),
      array('code' => 'ES', 'descr'=> 'Spanish'),
      array('code' => 'IT', 'descr'=> 'Italian'),
      array('code' => 'LV', 'descr'=> 'Latvian'),
      array('code' => 'RU', 'descr'=> 'Russian')
    );
  }

  public function getLanguagesOption() {
    $options = [];
    foreach ($this->supportedLanguages() as $lang) {
      $options[$lang['code']] = $lang['descr'];
    }
    return $options;
  }

  /**
   * @param $code string
   * @return string
   */
  public function getSingleLanguage($code) {
    return $this->getLanguagesOption()[$code];
  }

  /**
   * ICEPAY Supported Currencies
   * @return array
   */
  protected function supportedCurrencies() {
    return array(
      array('code' => 'EUR', 'descr'=> 'Euro'),
      array('code' => 'GBP', 'descr'=> 'Pound sterling'),
      array('code' => 'USD', 'descr'=> 'U.S. dollar')
    );
  }

  public function getCurrenciesOption() {
    $options = [];
    foreach ($this->supportedCurrencies() as $curr) {
      $options[$curr['code']] = $curr['descr'];
    }
    return $options;
  }

  /**
   * @param $code string
   * @return string
   */
  public function getSingleCurrency($code) {
    return $this->getCurrenciesOption()[$code];
  }

  /**
   * ICEPAY Supported Countries
   * @return array
   */
  protected function supportedCountries() {
    return array(
      array('code' => 'NL', 'descr' => 'Netherlands'),
      array('code' => 'AT', 'descr' => 'Austria'),
      array('code' => 'AU', 'descr' => 'Australia'),
      array('code' => 'BE', 'descr' => 'Belgium'),
      array('code' => 'CA', 'descr' => 'Canada'),
      array('code' => 'CH', 'descr' => 'Switzerland'),
      array('code' => 'CZ', 'descr' => 'Czech Republic'),
      array('code' => 'DE', 'descr' => 'Germany'),
      array('code' => 'ES', 'descr' => 'Spain'),
      array('code' => 'FR', 'descr' => 'France'),
      array('code' => 'IT', 'descr' => 'Italy'),
      array('code' => 'LU', 'descr' => 'Luxembourg'),
      array('code' => 'PL', 'descr' => 'Poland'),
      array('code' => 'PT', 'descr' => 'Portugal'),
      array('code' => 'SK', 'descr' => 'Slovakia'),
      array('code' => 'GB', 'descr' => 'United Kingdom'),
      array('code' => 'US', 'descr' => 'United States')
    );
  }

  public function getCountriesOption() {
    $options = [];
    foreach ($this->supportedCountries() as $country) {
      $options[$country['code']] = $country['descr'];
    }
    return $options;
  }

  /**
   * @param $code string
   * @return string
   */
  public function getSingleCountry($code) {
    return $this->getCountriesOption()[$code];
  }

  /**
   * ICEPAY Supported iDeal Issuers
   * @return array
   */
  protected function iDealIssuers() {
    return array(
      array('code' => 'ABNAMRO', 'descr' => 'ABN AMRO Bank'),
      array('code' => 'ASNBANK', 'descr' => 'ASN Bank'),
      array('code' => 'ING', 'descr' => 'ING Bank'),
      array('code' => 'RABOBANK', 'descr' => 'Rabobank'),
      array('code' => 'SNSBANK', 'descr' => 'SNS Bank'),
      array('code' => 'SNSREGIOBANK', 'descr' => 'SNS Regio Bank'),
      array('code' => 'VANLANSCHOT', 'descr' => 'Van Lanschot'),
      array('code' => 'TRIODOSBANK', 'descr' => 'Triodos Bank')
    );
  }

  public function getIdealIssuersOption() {
    $options = [];
    foreach ($this->iDealIssuers() as $issuer) {
      $options[$issuer['code']] = $issuer['descr'];
    }
    return $options;
  }

  /**
   * @param $code string
   * @return string
   */
  public function getSingleIdealIssuer($code) {
    return $this->getIdealIssuersOption()[$code];
  }

}