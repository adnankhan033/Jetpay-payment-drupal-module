<?php

namespace Drupal\jetpay\PluginForm\JetPay;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\StringTranslation\StringTranslationTrait;


class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $form = parent::buildConfigurationForm($form, $form_state);
    $payment = $this->entity;
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $configuration = $payment_gateway_plugin->getConfiguration();
    $order = $payment->getOrder();
    $order_price = $order->get("total_price")->getValue()[0];
    $currency = Currency::load($order_price['currency_code']);
    $symbol = $currency->get('symbol');
    $id = $order->id();
    $form['#attached']['library'][] = 'jetpay/payment';
    $address = $order->getBillingProfile()->address->first();

    $title = $this->t(
        'Payment of/de&nbsp; %price &nbsp; %symbol',
        ['%price' => sprintf("%d", $order_price['number']), '%symbol' => $symbol]
    );
    $return_url = \Drupal\Core\Url::fromRoute('jetpay.on_return', ['commerce_order' => $id, 'step' => 'payment'], ['absolute' => TRUE])->toString();
    $form['#attached']['drupalSettings']['jetpay_order'] = [
      "partner_txn_id" => $id,
      "amount" => (double) $order_price["number"],
      "currency" => $order_price["currency_code"],
      "seller_email" => $configuration["seller_email"],
      "notify_url" =>  $return_url,
      'title' => $title,
      "country_code" => strtolower($address->getCountryCode()),
      "showCancelButton" => true,
      "text" => $this->t("Enter like this: +237xxxxxxxxx"),
    ];
 

    return $form;
  }

}