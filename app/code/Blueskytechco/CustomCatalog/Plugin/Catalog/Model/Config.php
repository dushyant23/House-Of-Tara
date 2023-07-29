<?php
 
namespace Blueskytechco\CustomCatalog\Plugin\Catalog\Model;
 
class Config
{
   


    public function afterGetAttributeUsedForSortByArray(\Magento\Catalog\Model\Config $catalogConfig, $options)
    {
        unset($options['position']);
        unset($options['name']);
        unset($options['price']);
        $options['low_to_high'] = __('Price - Low To High');
        $options['high_to_low'] = __('Price - High To Low');
        $options['created_at'] = __( ' Whats New' );
        return $options;
    }

}