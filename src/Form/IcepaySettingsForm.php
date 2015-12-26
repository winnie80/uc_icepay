<?php

/**
 * @file
 * Contains \Drupal\uc_icepay\Form\IcepaySettingsForm.
 */

namespace Drupal\uc_icepay\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_icepay\Plugin\IcepayStatics;

/**
 * Credit card settings form.
 */
class IcepaySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_icepay_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $account = $this->currentUser();
    if (!$account->hasPermission('administer icepay config')) {
      $form['notice'] = array(
        '#markup' => '<div>' . $this->t('You must have access to <b>administer icepay config</b> to adjust these settings.') . '</div>',
      );
      return $form;
    }

    $ice_config = $this->config('uc_icepay.settings');

    $form['icepay_merchant'] = array(
      '#type' => 'details',
      '#title' => $this->t('Merchant details'),
      '#description' => $this->t('Please insert merchant details you have received from IcePay.'),
      '#open' => false,
      '#weight' => 1,
      '#attached' => array(
        'library' => array(
          'uc_icepay/uc_icepay.hideshow',
          'uc_icepay/uc_icepay.admin-merchant',
        ),
      ),
    );

    $form['icepay_merchant']['uc_icepay_merchant_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $ice_config->get('merchant_id'),
    );

    $form['icepay_merchant']['uc_icepay_secret_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Secret code'),
      '#default_value' => $ice_config->get('secret_code'),
      '#suffix' => '<p><a href="#" id="icepay-merchant-showhide-secret">show pass</a></p>'
    );

    $form['icepay_general'] = array(
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#description' => $this->t('Needed for Icepay API can be used properly.'),
      '#weight' => 3,
      '#open' => false,
    );

    $icepay_statics = new IcepayStatics;

    $form['icepay_general']['uc_icepay_country'] = array(
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $icepay_statics->getCountriesOption(),
      '#default_value' => $ice_config->get('country'),
    );

    $form['icepay_general']['uc_icepay_language'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $icepay_statics->getLanguagesOption(),
      '#default_value' => $ice_config->get('language'),
    );

    $form['icepay_general']['uc_icepay_currency'] = array(
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => $icepay_statics->getCurrenciesOption(),
      '#default_value' => $ice_config->get('currency'),
    );

    $form['icepay_general']['uc_icepay_mailing'] = array(
      '#default_value' => $ice_config->get('mailing')
    );

    $form['icepay_general']['uc_icepay_stream_method'] = array(
      '#type' => 'select',
      '#title' => $this->t('API connect method'),
      '#options' => array(
        'CURL' => $this->t('CURL (recommended)'),
        'FOPEN' => $this->t('FOPEN (requires allow_url_fopen)'),
      ),
      '#default_value' => $ice_config->get('stream_method')
    );

    $form['icepay_general']['uc_icepay_ipcheck'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('IP check on postback'),
      '#default_value' => $ice_config->get('ipcheck')
    );

    $form['icepay_general']['uc_icepay_https_protocol'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Use API HTTPS protocol'),
      '#default_value' => $ice_config->get('https_protocol')
    );

    $form['icepay_url'] = array(
      '#type' => 'details',
      '#title' => $this->t('ICEPAY merchant URL'),
      '#description' => $this->t('You can set another URL to handle success or error returned from Icepay provider.'),
      '#weight' => 5,
      '#open' => false,
    );

    global $base_url;

    $form['icepay_url']['uc_icepay_url_ok'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('OK / success URL'),
      '#default_value' => $ice_config->get('url.ok'),
      '#description' => $this->t('When changed, do not forget to change it on your ICEPAY merchant account.'),
      '#field_prefix' => $base_url .'/',
    );

    $form['icepay_url']['uc_icepay_url_err'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Error URL'),
      '#default_value' => $ice_config->get('url.err'),
      '#description' => $this->t('When changed, do not forget to change it on your ICEPAY merchant account.'),
      '#field_prefix' => $base_url .'/',
    );

    $form['icepay_url']['uc_icepay_url_notify'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Notify / Postback URL'),
      '#default_value' => $ice_config->get('url.notify'),
      '#description' => $this->t('When changed, do not forget to change it on your ICEPAY merchant account.'),
      '#field_prefix' => $base_url .'/',
    );

    $form['icepay_disclaimer'] = array(
      '#type' => 'details',
      '#title' => $this->t('Disclaimer'),
      '#description' => $this->t("The merchant is entitled to change de ICEPAY plug-in code, any changes will be at merchant's own risk.")
        .'<br>'.
        $this->t("Requesting ICEPAY support for a modified plug-in will be charged in accordance with the standard ICEPAY rates."),
      '#open' => true,
      'icepay_support_link' => array(
        '#type' => 'item',
        '#markup' => $this->t('For ICEPAY technical suppport, !link',
          ['!link' => '<a href="https://icepay.com/support" target="_blank">https://icepay.com/support</a>']
        ),
      )
    );

    $form['icepay_ideal_issuers'] = array(
      '#type' => 'details',
      '#title' => t('iDeal issuers'),
      '#description' => t('You can set which iDeal issuers to be made available as option during checkout process.'),
      '#weight' => 6
    );

    $static = new IcepayStatics();

    foreach ($static->getIdealIssuersOption() as $code => $issuer) {
      $form['icepay_ideal_issuers']['uc_icepay_ideal_issuer_' . $code] = array(
        '#type' => 'checkbox',
        '#title' => $issuer,
        '#default_value' => $ice_config->get('ideal_issuer.' . $code)
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    require_once(drupal_get_path('module', 'uc_icepay') . '/api/ParameterValidation.php');

    if (!\Icepay_ParameterValidation::merchantID($form_state->getValue('uc_icepay_merchant_id'))) {
      $form_state->setErrorByName('uc_icepay_merchant_id', t('You have specified an invalid Icepay Merchant ID.'));
    }

    if (!\Icepay_ParameterValidation::secretCode($form_state->getValue('uc_icepay_secret_code'))) {
      $form_state->setErrorByName('uc_icepay_secret_code', t('You have specified an invalid Icepay Secret Code.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Need to use configFactory() and getEditable() here, because this form is
    // wrapped by PaymentMethodSettingsForm so $this->getEditableConfigNames()
    // never gets called

    $ice_config = \Drupal::configFactory()->getEditable('uc_icepay.settings');
    $ice_config
      ->set('merchant_id', $form_state->getValue('uc_icepay_merchant_id'))
      ->set('secret_code', $form_state->getValue('uc_icepay_secret_code'))
      ->set('currency', $form_state->getValue('uc_icepay_currency'))
      ->set('country', $form_state->getValue('uc_icepay_country'))
      ->set('language', $form_state->getValue('uc_icepay_language'))
      ->set('mailing', $form_state->getValue('uc_icepay_mailing'))
      ->set('stream_method', $form_state->getValue('uc_icepay_stream_method'))
      ->set('ipcheck', $form_state->getValue('uc_icepay_ipcheck'))
      ->set('https_protocol', $form_state->getValue('uc_icepay_https_protocol'))
      ->set('url.ok', $form_state->getValue('uc_icepay_url_ok'))
      ->set('url.err', $form_state->getValue('uc_icepay_url_err'))
      ->set('url.notify', $form_state->getValue('uc_icepay_url_notify'));

    $static = new IcepayStatics();
    foreach ($static->getIdealIssuersOption() as $code => $issuer) {
      $ice_config->set('ideal_issuer.' . $code, $form_state->getValue('uc_icepay_ideal_issuer_' . $code));
    }

    $ice_config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_icepay.settings',
    ];
  }

}
