<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AdvancedLayeredNavigation
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\AdvancedLayeredNavigation\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;

use Magento\Ui\Component\Listing\Columns\Column;

class Category extends Column
{
    /**
     * @var [type]
     */
    private $attributeLoading;
    
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ProductFactory $attributeLoading
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Category $category,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->category = $category;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$fieldName] = $this->getCategoryName($item['categories']);
            }
        }
        return $dataSource;
    }

    /**
     * get category label
     *
     * @param string $categoryId
     * @return void
     */
    private function getCategoryName($categoryId)
    {
        return $this->category->load($categoryId)->getName();
    }
}
