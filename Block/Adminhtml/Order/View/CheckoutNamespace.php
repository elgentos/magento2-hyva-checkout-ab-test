<?php

declare(strict_types=1);

namespace Elgentos\HyvaCheckoutABTest\Block\Adminhtml\Order\View;

use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;

class CheckoutNamespace extends AbstractOrder
{
    public function getCheckoutNamespace(): ?string
    {
        $checkoutNamespace = $this->getOrder()->getData('active_checkout_namespace');

        return $checkoutNamespace ? (string) $checkoutNamespace : null;
    }

    public function toHtml(): string
    {
        if ($this->getCheckoutNamespace() === null) {
            return '';
        }

        return parent::toHtml();
    }
}
