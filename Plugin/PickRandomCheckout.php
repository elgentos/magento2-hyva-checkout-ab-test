<?php

namespace Elgentos\HyvaCheckoutABTest\Plugin;

use Exception;
use Hyva\Checkout\Model\CheckoutInformation\Luma;
use Hyva\Checkout\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\SerializerInterface;

class PickRandomCheckout
{
    public const HYVA_CHECKOUT_AB_TEST_ENABLED = 'hyva_themes_checkout/ab_test/enabled';
    public const HYVA_CHECKOUT_AB_TEST_CHECKOUTS = 'hyva_themes_checkout/ab_test/checkouts';

    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly State $appState,
        private readonly ScopeConfigInterface $config,
        private readonly SerializerInterface $serializer
    ) {

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
        // When the AB test is disabled, use the default configuration
        if (!$this->config->getValue(self::HYVA_CHECKOUT_AB_TEST_ENABLED)) {
            return $result;
        }

        try {
            $checkouts = $this->serializer->unserialize($this->config->getValue(self::HYVA_CHECKOUT_AB_TEST_CHECKOUTS));
        } catch (Exception $e) {
            $checkouts = [];
        }

        // When no checkouts are configured, use the default configuration
        if (count($checkouts) === 0) {
            return $result;
        }

        // When in developer mode, use the default configuration
        if ($this->appState->getMode() === State::MODE_DEVELOPER) {
            return $result;
        }

        // Retrieve the active checkout from the session, if present
        $activeCheckoutNamespace = $this->checkoutSession->getData('active_checkout_namespace');
        if ($activeCheckoutNamespace) {
            return $activeCheckoutNamespace;
        }

        // Pick a random result based on percentage given
        $result = $this->randomWithProbability($checkouts);

        // Save the randomly chosen checkout in the checkout session to make sure
        // this session always has the same checkout
        $this->checkoutSession->setData('active_checkout_namespace', $result);
        $this->checkoutSession->getQuote()->setData('active_checkout_namespace', $result)->save();
        return $result;
    }

    /**
     * @param $checkouts
     *
     * @return string
     */
    private function randomWithProbability($checkouts): string
    {
        $temp = [];
        foreach ($checkouts as $checkout) {
            $num = $checkout['percentage'];
            while ($num > 0) {
                $temp[] = $checkout['checkout'];
                $num--;
            }
        }
        return $temp[array_rand($temp)];
    }
}
