<?php
/**
 * Class Indexer
 *
 * PHP version 7
 *
 * @category Sparsh
 * @package  Sparsh_AdvancedSorting
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\AdvancedSorting\Model\Indexer;

/**
 * Class Index
 *
 * @category Sparsh
 * @package  Sparsh_AdvancedSorting
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Index implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Action
     */
    private $action;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * Index constructor.
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\ResourceModel\Product\Action $action
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Product\Action $action
    )
    {
        $this->_resource = $resource;
        $this->action = $action;
    }

    /*
     * Used by mview, allows process indexer in the "Update on schedule" mode
     */
    public function execute($ids){

        $this->executeFull();
    }

    /*
     * Will take all of the data and reindex
     * Will run when reindex via command line
     */
    public function executeFull(){

        $connection = $this->_getConnection();
        $select = $connection->select()
            ->from(
                ['soi' => $this->_resource->getTableName('sales_order_item')],
                [
                    'product_id' => 'product_id',
                    'best_seller' => 'SUM(qty_ordered)'
                ]
            )
            ->join(
                ['cpe' => $this->_resource->getTableName('catalog_product_entity')],
                'cpe.entity_id = soi.product_id'
            )
            ->group('soi.product_id');

        $data = $connection->fetchAll($select);

        foreach ($data as $item) {
            $this->action->updateAttributes(
                [$item['product_id']],
                ['best_seller' => $item['best_seller']],
                0);
        }

        $select = $connection->select()
            ->from(
                ['res' => $this->_resource->getTableName('review_entity_summary')],
                [
                    'product_id' => 'entity_pk_value',
                    'top_rated' => 'rating_summary'
                ]
            )
            ->join(
                ['cpe' => $this->_resource->getTableName('catalog_product_entity')],
                'cpe.entity_id = res.entity_pk_value'
            )
            ->group('res.entity_pk_value');

        $data = $connection->fetchAll($select);

        foreach ($data as $item) {
            $this->action->updateAttributes(
                [$item['product_id']],
                ['top_rated' => $item['top_rated']],
                0);
        }

        $select = $connection->select()
            ->from(
                ['rvpi' => $this->_resource->getTableName('report_viewed_product_index')],
                [
                    'product_id' => 'product_id',
                    'most_viewed' => 'COUNT(rvpi.product_id)'
                ]
            )
            ->join(
                ['cpe' => $this->_resource->getTableName('catalog_product_entity')],
                'cpe.entity_id = rvpi.product_id'
            )
            ->group('rvpi.product_id');

        $data = $connection->fetchAll($select);

        foreach ($data as $item) {
            $this->action->updateAttributes(
                [$item['product_id']],
                ['most_viewed' => $item['most_viewed']],
                0);
        }

        $select = $connection->select()
            ->from(
                ['res' => $this->_resource->getTableName('review_entity_summary')],
                [
                    'product_id' => 'entity_pk_value',
                    'review_count' => 'reviews_count'
                ]
            )
            ->join(
                ['cpe' => $this->_resource->getTableName('catalog_product_entity')],
                'cpe.entity_id = res.entity_pk_value'
            )
            ->group('res.entity_pk_value');

        $data = $connection->fetchAll($select);

        foreach ($data as $item) {
            $this->action->updateAttributes(
                [$item['product_id']],
                ['review_count' => $item['review_count']],
                0);
        }
    }

    /**
     * Retrieve connection instance
     *
     * @return bool|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function _getConnection()
    {
        if (null === $this->_connection) {
            $this->_connection = $this->_resource->getConnection();
        }
        return $this->_connection;
    }


    /*
     * Works with a set of entity changed (may be massaction)
     */
    public function executeList(array $ids){
        $this->executeFull();
    }


    /*
     * Works in runtime for a single entity using plugins
     */
    public function executeRow($id){
        $this->executeFull();
    }
}
