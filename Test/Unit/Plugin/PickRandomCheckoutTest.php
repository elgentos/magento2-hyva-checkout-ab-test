<?php

declare(strict_types=1);

namespace Elgentos\HyvaCheckoutABTest\Test\Unit\Plugin;

use Elgentos\HyvaCheckoutABTest\Plugin\PickRandomCheckout;
use Hyva\Checkout\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PickRandomCheckoutTest extends TestCase
{
    private const DEFAULT_CHECKOUT = 'default';

    private CheckoutSession $checkoutSession;

    private Quote&MockObject $quote;

    private State&MockObject $appState;

    private ScopeConfigInterface&MockObject $scopeConfig;

    private RequestInterface&MockObject $request;

    private array $configValues = [];

    protected function setUp(): void
    {
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'save'])
            ->getMock();
        $this->quote->method('getId')->willReturn(1);

        $this->checkoutSession = $this->createCheckoutSession($this->quote);

        $this->appState = $this->createMock(State::class);
        $this->appState->method('getMode')->willReturn(State::MODE_PRODUCTION);

        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->scopeConfig->method('getValue')
            ->willReturnCallback(fn (string $path) => $this->configValues[$path] ?? null);
        $this->scopeConfig->method('isSetFlag')
            ->willReturnCallback(fn (string $path) => (bool) ($this->configValues[$path] ?? false));

        $this->request = $this->createMock(RequestInterface::class);

        $this->configValues = [
            PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_ENABLED => '1',
            PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_CHECKOUTS => $this->checkouts(['magento_luma' => 0, 'dls' => 100]),
        ];
    }

    public function testDisabledTestUsesDefaultCheckout(): void
    {
        $this->configValues[PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_ENABLED] = '0';
        $this->checkoutSession->setData('active_checkout_namespace', 'magento_luma');

        $this->assertSame(self::DEFAULT_CHECKOUT, $this->pick());
        $this->assertSame('magento_luma', $this->checkoutSession->getData('active_checkout_namespace'));
    }

    public function testSessionAssignedToCheckoutAtZeroPercentIsAssignedAgain(): void
    {
        $this->checkoutSession->setData('active_checkout_namespace', 'magento_luma');
        $this->quote->setData('active_checkout_namespace', 'magento_luma');
        $this->quote->expects($this->once())->method('save');

        $this->assertSame('dls', $this->pick());
        $this->assertSame('dls', $this->checkoutSession->getData('active_checkout_namespace'));
        $this->assertSame('dls', $this->quote->getData('active_checkout_namespace'));
    }

    public function testSessionAssignedToRemovedCheckoutIsAssignedAgain(): void
    {
        $this->configValues[PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_CHECKOUTS] = $this->checkouts(['dls' => 100]);
        $this->checkoutSession->setData('active_checkout_namespace', 'magento_luma');

        $this->assertSame('dls', $this->pick());
    }

    public function testSessionAssignmentIsKeptWhileItsCheckoutTakesPart(): void
    {
        $this->configValues[PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_CHECKOUTS] = $this->checkouts(['magento_luma' => 1, 'dls' => 99]);
        $this->checkoutSession->setData('active_checkout_namespace', 'magento_luma');

        $this->assertSame('magento_luma', $this->pick());
    }

    public function testQuoteCreatedAfterAssignmentReceivesTheSessionCheckout(): void
    {
        $this->checkoutSession->setData('active_checkout_namespace', 'dls');
        $this->quote->expects($this->once())->method('save');

        $this->assertSame('dls', $this->pick());
        $this->assertSame('dls', $this->quote->getData('active_checkout_namespace'));
    }

    public function testQuoteAlreadyHoldingTheCheckoutIsNotSavedAgain(): void
    {
        $this->checkoutSession->setData('active_checkout_namespace', 'dls');
        $this->quote->setData('active_checkout_namespace', 'dls');
        $this->quote->expects($this->never())->method('save');

        $this->assertSame('dls', $this->pick());
    }

    public function testVisitorWithoutCartDoesNotCreateAQuote(): void
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'save'])
            ->getMock();
        $quote->method('getId')->willReturn(null);
        $quote->expects($this->never())->method('save');
        $this->checkoutSession = $this->createCheckoutSession($quote);

        $this->assertSame('dls', $this->pick());
        $this->assertSame('dls', $this->checkoutSession->getData('active_checkout_namespace'));
    }

    public function testUrlParameterForcesACheckoutThatTakesPart(): void
    {
        $this->configValues[PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_CHECKOUTS] = $this->checkouts(['magento_luma' => 50, 'dls' => 50]);
        $this->configValues[PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_ALLOW_URL_PARAM_OVERRIDE] = '1';
        $this->checkoutSession->setData('active_checkout_namespace', 'dls');
        $this->request->method('getParam')->with('active_checkout_namespace')->willReturn('magento_luma');

        $this->assertSame('magento_luma', $this->pick());
        $this->assertSame('magento_luma', $this->checkoutSession->getData('active_checkout_namespace'));
    }

    public function testUrlParameterCannotForceACheckoutAtZeroPercent(): void
    {
        $this->configValues[PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_ALLOW_URL_PARAM_OVERRIDE] = '1';
        $this->request->method('getParam')->with('active_checkout_namespace')->willReturn('magento_luma');

        $this->assertSame('dls', $this->pick());
        $this->assertSame('dls', $this->checkoutSession->getData('active_checkout_namespace'));
    }

    public function testNoCheckoutAboveZeroPercentUsesDefaultCheckout(): void
    {
        $this->configValues[PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_CHECKOUTS] = $this->checkouts(['magento_luma' => 0, 'dls' => 0]);

        $this->assertSame(self::DEFAULT_CHECKOUT, $this->pick());
        $this->assertNull($this->checkoutSession->getData('active_checkout_namespace'));
    }

    public function testDeveloperModeUsesDefaultCheckoutWithoutAssigning(): void
    {
        $this->inDeveloperMode();

        $this->assertSame(self::DEFAULT_CHECKOUT, $this->pick());
        $this->assertNull($this->checkoutSession->getData('active_checkout_namespace'));
    }

    public function testDeveloperModeKeepsACheckoutForcedEarlier(): void
    {
        $this->inDeveloperMode();
        $this->configValues[PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_CHECKOUTS] = $this->checkouts(['magento_luma' => 50, 'dls' => 50]);
        $this->checkoutSession->setData('active_checkout_namespace', 'magento_luma');

        $this->assertSame('magento_luma', $this->pick());
    }

    public function testDeveloperModeAssignsWhenConfiguredToRunTheTest(): void
    {
        $this->inDeveloperMode();
        $this->configValues[PickRandomCheckout::HYVA_CHECKOUT_AB_TEST_RUN_IN_DEVELOPER_MODE] = '1';

        $this->assertSame('dls', $this->pick());
        $this->assertSame('dls', $this->checkoutSession->getData('active_checkout_namespace'));
    }

    private function pick(): string
    {
        $plugin = new PickRandomCheckout(
            $this->checkoutSession,
            $this->appState,
            $this->scopeConfig,
            new Json(),
            $this->request
        );

        return $plugin->afterGetActiveCheckoutNamespace($this->createMock(Config::class), self::DEFAULT_CHECKOUT);
    }

    private function inDeveloperMode(): void
    {
        $this->appState = $this->createMock(State::class);
        $this->appState->method('getMode')->willReturn(State::MODE_DEVELOPER);
    }

    /**
     * @param array<string, int> $percentages
     */
    private function checkouts(array $percentages): string
    {
        $rows = [];
        foreach ($percentages as $checkout => $percentage) {
            $rows['_' . $checkout] = ['checkout' => $checkout, 'percentage' => (string) $percentage];
        }

        return (string) json_encode($rows);
    }

    /**
     * The session stores custom keys through magic methods, which cannot be doubled, so a small in-memory
     * session is used instead.
     */
    private function createCheckoutSession(Quote $quote): CheckoutSession
    {
        return new class ($quote) extends CheckoutSession {
            private array $values = [];

            public function __construct(private readonly Quote $currentQuote)
            {
            }

            public function getData($key = '', $clear = false)
            {
                return $this->values[$key] ?? null;
            }

            public function setData(string $key, mixed $value): self
            {
                $this->values[$key] = $value;

                return $this;
            }

            public function getQuote()
            {
                return $this->currentQuote;
            }
        };
    }
}
