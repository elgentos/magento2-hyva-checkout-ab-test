<?php

namespace Elgentos\HyvaCheckoutABTest\Plugin;

use Exception;
use Hyva\CheckoutCore\Model\CheckoutInformation\Luma;
use Hyva\CheckoutCore\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\State;

class PickRandomCheckout
{
    public function __construct(private CheckoutSession $checkoutSession, private State $appState) {

    }

    /**
     * @param Config $subject
     * @param string $result
     *
     * @return string
     * @throws Exception
     */
    public function afterGetActiveCheckoutNamespace(
        Config $subject,
        string $result
    ): string {
        // Retrieve the active checkout from the session, if present
        $activeCheckoutNamespace = $this->checkoutSession->getData('active_checkout_namespace');
        if ($activeCheckoutNamespace) {
            return $activeCheckoutNamespace;
        }

        // 50% of the time, use the Luma checkout.
        // Otherwise, default to the Hyva Checkout config
        // Only do the AB test in production
        if (
            random_int(0, 1) &&
            $this->appState->getMode() !== State::MODE_DEVELOPER
        ) {
            $result = Luma::NAMESPACE;
        }

        // Save the randomly chosen checkout in the checkout session to make sure
        // this session always has the same checkout
        $this->checkoutSession->setData('active_checkout_namespace', $result);
        $this->checkoutSession->getQuote()->setData('active_checkout_namespace', $result)->save();
        return $result;
    }
}
