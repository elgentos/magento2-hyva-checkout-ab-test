<?php

namespace Elgentos\HyvaCheckoutABTest\Plugin;

use Exception;
use Hyva\Checkout\Model\CheckoutInformation\Luma;
use Hyva\Checkout\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\SerializerInterface;

class PickRandomCheckout
{
    public const HYVA_CHECKOUT_AB_TEST_ENABLED = 'hyva_themes_checkout/ab_test/enabled';
    public const HYVA_CHECKOUT_AB_TEST_CHECKOUTS = 'hyva_themes_checkout/ab_test/checkouts';
    public const HYVA_CHECKOUT_AB_TEST_ALLOW_URL_PARAM_OVERRIDE = 'hyva_themes_checkout/ab_test/allow_url_param_override';
    public const HYVA_CHECKOUT_AB_TEST_RUN_IN_DEVELOPER_MODE = 'hyva_themes_checkout/ab_test/run_in_developer_mode';

    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly State $appState,
        private readonly ScopeConfigInterface $config,
        private readonly SerializerInterface $serializer,
        private readonly RequestInterface $request,
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
        if (!$this->config->getValue(self::HYVA_CHECKOUT_AB_TEST_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return $result;
        }

        try {
            $checkouts = $this->serializer->unserialize($this->config->getValue(self::HYVA_CHECKOUT_AB_TEST_CHECKOUTS, ScopeInterface::SCOPE_STORE));
        } catch (Exception $e) {
            $checkouts = [];
        }

        // Only checkouts with a percentage above zero take part in the test
        $activeCheckouts = $this->getActiveCheckouts($checkouts);

        // When no checkout takes part in the test, use the default configuration
        if (count($activeCheckouts) === 0) {
            return $result;
        }

        // Check if URL parameter override is allowed:
        // - Always in developer mode
        // - In production only if explicitly enabled in config
        $allowUrlParamOverride = ($this->appState->getMode() === State::MODE_DEVELOPER)
            || $this->config->getValue(self::HYVA_CHECKOUT_AB_TEST_ALLOW_URL_PARAM_OVERRIDE, ScopeInterface::SCOPE_STORE);

        if ($allowUrlParamOverride) {
            $paramCheckout = $this->request->getParam('active_checkout_namespace');
            if (is_string($paramCheckout) && in_array($paramCheckout, $activeCheckouts, true)) {
                return $this->assign($paramCheckout);
            }
        }

        // Retrieve the active checkout from the session, if present. An assignment to a checkout that has
        // since been removed or set to 0% is dropped, so the visitor is assigned again below
        $activeCheckoutNamespace = $this->checkoutSession->getData('active_checkout_namespace');
        if (is_string($activeCheckoutNamespace) && in_array($activeCheckoutNamespace, $activeCheckouts, true)) {
            return $this->assign($activeCheckoutNamespace);
        }

        // When in developer mode and no valid URL parameter was provided, use the default configuration,
        // unless the A/B test is configured to run in developer mode as well
        if ($this->appState->getMode() === State::MODE_DEVELOPER
            && !$this->config->isSetFlag(self::HYVA_CHECKOUT_AB_TEST_RUN_IN_DEVELOPER_MODE, ScopeInterface::SCOPE_STORE)
        ) {
            return $result;
        }

        // Pick a random result based on percentage given
        return $this->assign($this->randomWithProbability($checkouts));
    }

    /**
     * Save the checkout in the checkout session to make sure this session always has the same checkout,
     * and on the current quote, which may have been created after the checkout was assigned to the session
     *
     * @param string $checkoutNamespace
     *
     * @return string
     */
    private function assign(string $checkoutNamespace): string
    {
        if ($this->checkoutSession->getData('active_checkout_namespace') !== $checkoutNamespace) {
            $this->checkoutSession->setData('active_checkout_namespace', $checkoutNamespace);
        }

        // Only a quote that already exists is updated; saving the empty quote of a visitor without a cart
        // would create a new quote record on every request
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getId() && $quote->getData('active_checkout_namespace') !== $checkoutNamespace) {
            $quote->setData('active_checkout_namespace', $checkoutNamespace)->save();
        }

        return $checkoutNamespace;
    }

    /**
     * @param array $checkouts
     *
     * @return string[]
     */
    private function getActiveCheckouts(array $checkouts): array
    {
        $activeCheckouts = [];
        foreach ($checkouts as $checkout) {
            if ((int) ($checkout['percentage'] ?? 0) > 0 && !empty($checkout['checkout'])) {
                $activeCheckouts[] = (string) $checkout['checkout'];
            }
        }

        return $activeCheckouts;
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
