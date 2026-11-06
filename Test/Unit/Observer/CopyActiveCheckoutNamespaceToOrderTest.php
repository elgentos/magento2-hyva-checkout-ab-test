<?php

declare(strict_types=1);

namespace Elgentos\HyvaCheckoutABTest\Test\Unit\Observer;

use Elgentos\HyvaCheckoutABTest\Observer\CopyActiveCheckoutNamespaceToOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CopyActiveCheckoutNamespaceToOrderTest extends TestCase
{
    public function testRunningTestCopiesTheCheckoutToTheOrder(): void
    {
        $order = new DataObject();

        $this->observe(true, new DataObject(['active_checkout_namespace' => 'dls', 'store_id' => 1]), $order);

        $this->assertSame('dls', $order->getData('active_checkout_namespace'));
    }

    public function testStoppedTestLeavesTheOrderWithoutCheckout(): void
    {
        $order = new DataObject();

        $this->observe(false, new DataObject(['active_checkout_namespace' => 'magento_luma', 'store_id' => 1]), $order);

        $this->assertNull($order->getData('active_checkout_namespace'));
    }

    private function observe(bool $testEnabled, DataObject $quote, DataObject $order): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn($testEnabled);

        $observer = new Observer(['event' => new Event(['quote' => $quote, 'order' => $order])]);

        (new CopyActiveCheckoutNamespaceToOrder($scopeConfig))->execute($observer);
    }
}
