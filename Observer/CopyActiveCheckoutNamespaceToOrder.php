<?php

declare(strict_types=1);

namespace Elgentos\HyvaCheckoutABTest\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CopyActiveCheckoutNamespaceToOrder implements ObserverInterface
{
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

        $activeCheckoutNamespace = $quote->getData('active_checkout_namespace');
        if ($activeCheckoutNamespace) {
            $order->setData('active_checkout_namespace', $activeCheckoutNamespace);
        }
    }
}
