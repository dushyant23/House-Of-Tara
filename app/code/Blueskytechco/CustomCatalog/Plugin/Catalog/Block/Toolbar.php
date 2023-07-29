<?php
namespace Blueskytechco\CustomCatalog\Plugin\Catalog\Block;

use Magento\Catalog\Block\Product\ProductList\Toolbar as ProductListToolbar;

class Toolbar
{

    /**
    * Plugin
    *
    * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
    * @param \Closure $proceed
    * @param \Magento\Framework\Data\Collection $collection
    * @return \Magento\Catalog\Block\Product\ProductList\Toolbar
    */
    /*public function aroundSetCollection(
    \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
    \Closure $proceed,
    $collection
    ) {
    $currentOrder = $subject->getCurrentOrder();
    $result = $proceed($collection);

    if ($currentOrder) {
        if ($currentOrder == 'high_to_low') {
           return $subject->getCollection()->setOrder('price', 'desc');
        } elseif ($currentOrder == 'low_to_high') {
           return $subject->getCollection()->setOrder('price', 'asc');
        }
    }

    return $result;
    }*/

    public function afterSetCollection(ProductListToolbar $subject, $result, $collection)
    {
        switch ($subject->getCurrentOrder()) {
            case 'low_to_high':
                return $result->getCollection()->setOrder('price', 'asc');
            case 'high_to_low':
                return $result->getCollection()->setOrder('price', 'desc');
            default:
                return $result;
        }
    }

}