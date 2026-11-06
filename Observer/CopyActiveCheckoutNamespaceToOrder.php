<?php

declare(strict_types=1);

namespace Elgentos\HyvaCheckoutABTest\Observer;

use Elgentos\HyvaCheckoutABTest\Plugin\PickRandomCheckout;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class CopyActiveCheckoutNamespaceToOrder implements ObserverInterface
{
    public function __construct(
        private readonly ScopeConfigInterface $config,
    ) {
    }

    /**
     * Copy the active checkout namespace from the quote to the order.
     *
     * This cannot be done through a sales_convert_quote fieldset entry because
     * QuoteManagement::submitQuote() merges the converted order into a fresh
     * order instance via DataObjectHelper::mergeDataObjects(OrderInterface::class, ...),
     * which drops any data not exposed through OrderInterface getters.
     */
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getData('quote');
        $order = $observer->getEvent()->getData('order');

        // With the A/B test disabled every visitor is shown the default checkout, while the quote may still
        // hold the variant of a session that was assigned while the test ran
        if (!$this->config->isSetFlag(
            PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        )) {
            return;
        }

        $activeCheckoutNamespace = $quote->getData('active_checkout_namespace');
        if ($activeCheckoutNamespace) {
            $order->setData('active_checkout_namespace', $activeCheckoutNamespace);
        }
    }
}
