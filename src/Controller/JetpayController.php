<?php

namespace Drupal\jetpay\Controller;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessException;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Controller\PaymentCheckoutController;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\Core\Url;
use Exception;
use Laminas\Diactoros\Response\RedirectResponse;

/**
 * Returns responses for Jetpay Payment Gateway routes.
 */
class JetpayController extends PaymentCheckoutController
{
    use StringTranslationTrait;

    /**
     * Provides the "return" checkout payment page.
     *
     * Redirects to the next checkout page, completing checkout.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
     *   The route match.
     */
    public function returnPage(Request $request, RouteMatchInterface $route_match) {
        
        
        /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
        $order = $route_match->getParameter('commerce_order');
        $order_price = $order->get("total_price")->getValue()[0];
        $payload = $request->request->all();
        /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
        $payment_gateway = $order->get('payment_gateway')->entity;
        $payment_gateway_plugin = $payment_gateway->getPlugin();
        if (!$payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
            throw new AccessException('The payment gateway for the order does not implement ' . OffsitePaymentGatewayInterface::class);
        }
        $pay_config = $payment_gateway_plugin->getConfiguration();
        if ($order->id() == $payload["partner_txn_id"]
            && $pay_config["seller_email"] == $payload["seller_email"]
            && (double) $order_price["number"] == $payload["amount"]
            && $order_price["currency_code"] == $payload["currency"]
        ) {
            $step_id = $route_match->getParameter('step');
            $this->validateStepId($step_id, $order);
            
            /** @var \Drupal\commerce_checkout\Entity\CheckoutFlowInterface $checkout_flow */
            $checkout_flow = $order->get('checkout_flow')->entity;
            $checkout_flow_plugin = $checkout_flow->getPlugin();
    
            try {
                $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
                user_login_finalize($order->getCustomer());
                $payment = $payment_storage->create([
                    'state' => 'authorization',
                    'amount' => $order->getTotalPrice(),
                    'payment_gateway' => $payment_gateway->id(),
                    'order_id' => $order->id(),
                    'remote_id' => $payload['partner_txn_id'],
                    'remote_state' => 'completed',
                ]);
                $payment->save();

                $payment->setState('completed');
                $payment->save();
                $redirect_step_id = $checkout_flow_plugin->getNextStepId($step_id);
                
            } catch (PaymentGatewayException $e) {
                $this->logger->error($e->getMessage());
                $this->messenger->addError($this->t('Payment failed at the payment server. Please review your information and try again.'));
                $redirect_step_id = $checkout_flow_plugin->getPreviousStepId($step_id);
            } catch (Exception $l) {
                $this->logger->error($l->getMessage());
            }
            $checkout_flow_plugin->redirectToStep($redirect_step_id);
        }
        
        user_logout();
    }

    public function access() {
        // Early exit for users without checkout permission.
        if (!\Drupal::currentUser()->hasPermission('access checkout')) {
            return AccessResult::forbidden();
        }
        return AccessResult::allowed();
    }
}
