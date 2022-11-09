<?php

declare(strict_types=1);

namespace Elgentos\HyvaCheckoutABTest\Block\Adminhtml\Form\Field;

use \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class Checkouts extends AbstractFieldArray
{
    protected function _prepareToRender()
    {
        $this->addColumn('firstbox', ['label' => __('First Text Field'), 'class' => 'required-entry']);
        $this->addColumn('secondbox', ['label' => __('Second Text Field'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New');
    }
}
