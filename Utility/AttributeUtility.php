<?php

namespace DanielMaier\AttributeMigration\Utility;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Model\Entity\Attribute\OptionLabel;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection;
use Magento\Eav\Model\Entity\Attribute\Source\TableFactory;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Store\Model\StoreManagerInterface;

class AttributeUtility
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;
    /**
     * @var AttributeOptionLabelInterfaceFactory
     */
    private $optionLabelFactory;
    /**
     * @var AttributeOptionInterfaceFactory
     */
    private $optionFactory;
    /**
     * @var Collection
     */
    private $attributeOptionCollection;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var AttributeOptionManagementInterface
     */
    private $attributeOptionManagement;

    /**
     * @var array
     */
    private $attributeValues;
    /**
     * @var TableFactory
     */
    private $tableFactory;

    /**
     * AttributeUtility constructor.
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param AttributeOptionLabelInterfaceFactory $optionLabelFactory
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param Collection $attributeOptionCollection
     * @param StoreManagerInterface $storeManager
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     * @param TableFactory $tableFactory
     */
    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        AttributeOptionInterfaceFactory $optionFactory,
        Collection $attributeOptionCollection,
        StoreManagerInterface $storeManager,
        AttributeOptionManagementInterface $attributeOptionManagement,
        TableFactory $tableFactory
    )
    {
        $this->productAttributeRepository = $productAttributeRepository;
        $this->optionLabelFactory = $optionLabelFactory;
        $this->optionFactory = $optionFactory;
        $this->attributeOptionCollection = $attributeOptionCollection;
        $this->storeManager = $storeManager;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->tableFactory = $tableFactory;
    }

    /**
     * @param string $code
     * @return \Magento\Eav\Api\Data\AttributeInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAttribute($code)
    {
        return $this->productAttributeRepository->get($code);
    }

    /**
     * Create or Get An Attributes Option Id by Label
     *
     * @param $attributeCode
     * @param $label
     * @return int
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createOrGetOptionId($attributeCode, $label)
    {
        $label = trim($label);

        if (empty($label)) {
            throw new \Exception('Label for Attribute ' . $attributeCode . ' must not be empty.');
        }

        $optionId = $this->getOptionId($attributeCode, $label);

        if (!$optionId)
        {
            /** @var OptionLabel $adminLabel */
            $adminLabel = $this->optionLabelFactory->create();
            $adminLabel->setStoreId(0);
            $adminLabel->setLabel($label);

            $optionLabels = [
                $adminLabel
            ];

            $option = $this->optionFactory->create();
            $option->setLabel($adminLabel);
            $option->setStoreLabels($optionLabels);
            $option->setSortOrder(0);
            $option->setIsDefault(false);

            $this->attributeOptionManagement->add(
                Product::ENTITY,
                $this->getAttribute($attributeCode)->getAttributeId(),
                $option
            );

            $optionId = $this->getOptionId($attributeCode, $label, true);
        }

        return $optionId;
    }

    /**
     * Get An Attributes Option Id by Label
     *
     * @param string $attributeCode
     * @param string $label
     * @param bool $force
     * @return int|false
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOptionId($attributeCode, $label, $force = false)
    {
        /** @var Attribute $attribute */
        $attribute = $this->getAttribute($attributeCode);

        // Build option array if necessary
        if ($force === true || !isset($this->attributeValues[$attribute->getAttributeId()])) {
            $this->attributeValues[$attribute->getAttributeId()] = [];

            /** @var \Magento\Eav\Model\Entity\Attribute\Source\Table $sourceModel */
            $sourceModel = $this->tableFactory->create();
            $sourceModel->setAttribute($attribute);

            foreach ($sourceModel->getAllOptions(true, true) as $option) {
                $this->attributeValues[$attribute->getAttributeId()][$option['label']] = $option['value'];
            }
        }

        if (isset($this->attributeValues[$attribute->getAttributeId()][$label])) {
            return $this->attributeValues[$attribute->getAttributeId()][$label];
        }

        return false;
    }
}