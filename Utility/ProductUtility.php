<?php

namespace DanielMaier\AttributeMigration\Utility;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;

class ProductUtility
{
    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * ProductUtility constructor.
     * @param ProductFactory $productFactory
     */
    public function __construct(
        ProductFactory $productFactory
    )
    {
        $this->productFactory = $productFactory;
    }

    /**
     * @param Product $product
     * @return Product
     */
    public function loadForEdit($product)
    {
        $productId = $product->getEntityId();
        $productTypeId = $product->getTypeId();

        $product = $this->productFactory->create();
        $product->setStoreId(0);
        $product->setTypeId($productTypeId);
        $product->setData('_edit_mode', true);
        $product->load($productId);

        return $product;
    }
}