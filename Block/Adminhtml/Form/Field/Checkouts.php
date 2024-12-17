<?php

declare(strict_types=1);

namespace Elgentos\HyvaCheckoutABTest\Block\Adminhtml\Form\Field;

use Hyva\Checkout\Block\Adminhtml\Element\FieldArray\TypeRenderer;
use Magento\Backend\Block\Template\Context;
use \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Checkouts extends AbstractFieldArray
{
    private \Magento\Framework\View\Element\BlockFactory $blockFactory;
    private \Hyva\Checkout\Model\Config\Source\Checkout $checkoutSource;

    public function __construct(
        Context $context,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Hyva\Checkout\Model\Config\Source\Checkout $checkoutSource,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
        $this->blockFactory = $blockFactory;
        $this->checkoutSource = $checkoutSource;
    }

    protected function _prepareToRender()
    {
        $checkoutOptions = array_combine(
            array_column($this->checkoutSource->toOptionArray(), 'value'),
            array_column($this->checkoutSource->toOptionArray(), 'label')
        );
        $this->addColumn('checkout', [
            'label' => __('Checkout'),
            'class' => 'required-entry',
            'renderer' => $this->blockFactory->createBlock(TypeRenderer::class)
                ->setElementType('select')
                ->setElementOptions($checkoutOptions),
            'style' => 'width: 150px'
        ]);
        $this->addColumn('percentage', ['label' => __('Percentage Shown During Test'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Checkout');
    }
}
