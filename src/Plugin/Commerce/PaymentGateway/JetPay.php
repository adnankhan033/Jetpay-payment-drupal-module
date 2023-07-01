<?php

namespace Drupal\jetpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\MinorUnitsConverterInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Provides the Off-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "jetpay",
 *   label = "JetPay",
 *   display_label = "JetPay",
 *   forms = {
 *     "offsite-payment" = "Drupal\jetpay\PluginForm\JetPay\PaymentOffsiteForm",
 *   },
 *   requires_billing_information = FALSE,
 * )
 */
class JetPay extends OffsitePaymentGatewayBase implements JetPayInterface
{

  /**
   * {@inheritdoc}
   */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, MinorUnitsConverterInterface $minor_units_converter) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time, $minor_units_converter);

        // You can create an instance of the SDK here and assign it to $this->api.
        // Or inject Guzzle when there's no suitable SDK.
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration() {
        return [
            'seller_email' => '',
            'seller_phone' => '',
        ] + parent::defaultConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        $form = parent::buildConfigurationForm($form, $form_state);
        

        // Example credential. Also needs matching schema in
        // config/schema/$your_module.schema.yml.
        $form['seller_email'] = [
            '#type' => 'email',
            '#title' => $this->t('Seller Email...'),
            '#default_value' => $this->configuration['seller_email'],
            '#required' => true,
        ]; 
        $form['seller_phone'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Seller phone'),
            '#default_value' => $this->configuration['seller_phone'],
            '#required' => true,
        ];

           $form['profile_id'] = [
            '#type' => 'number',
            '#title' => $this->t('Merchant Profile id'),
            '#required' => TRUE,
            '#description' => $this->t('Your merchant profile id , you can find the profile id on your ayTabs Merchant’s Dashboard- profile.'),
            '#default_value' => $this->configuration['profile_id'],
        ];  
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
        parent::submitConfigurationForm($form, $form_state);

        if (!$form_state->getErrors()) {
            $values = $form_state->getValue($form['#parents']);
            $this->configuration['seller_email'] = $values['seller_email'];
            $this->configuration['seller_phone'] = $values['seller_phone'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onNotify(Request $request) {
    }

    /**
     * {@inheritdoc}
     */
    public function onReturn(OrderInterface $order, Request $request) {
    }

    /**
     * {@inheritdoc}
     */
    public function refundPayment(PaymentInterface $payment, Price $amount = null) {
    }


}
