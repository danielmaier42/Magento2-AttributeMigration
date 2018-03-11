<?php

namespace DanielMaier\AttributeMigration\Command;

use DanielMaier\AttributeMigration\Utility\AttributeUtility;
use DanielMaier\AttributeMigration\Utility\ProductUtility;
use DanielMaier\ConsoleUtility\Command\UtilityCommand;
use DanielMaier\ConsoleUtility\Helper\TimeMessureHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Question\Question;

class BooleanToSelect extends UtilityCommand
{
    const FIELD_OLD_ATTRIBUTE = 'old_attribute';
    const FIELD_NEW_ATTRIBUTE = 'new_attribute';

    /**
     * @var AttributeUtility
     */
    private $attributeUtility;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var ProductUtility
     */
    private $productUtility;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * TextToSelect constructor.
     *
     * @param AttributeUtility $attributeUtility
     * @param ProductUtility $productUtility
     * @param ProductFactory $productFactory
     *
     * @param ProductRepositoryInterface $productRepository
     * @param ObjectManagerInterface $objectManager
     * @param TimeMessureHelper $timeMessureHelper
     * @param State $state
     */
    public function __construct(
        AttributeUtility $attributeUtility,
        ProductUtility $productUtility,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,

        ObjectManagerInterface $objectManager,
        TimeMessureHelper $timeMessureHelper,
        State $state
    )
    {
        parent::__construct($objectManager, $timeMessureHelper, $state);

        $this->attributeUtility = $attributeUtility;
        $this->productFactory = $productFactory;
        $this->productUtility = $productUtility;
        $this->productRepository = $productRepository;
    }

    /**
     * Configure Command
     *
     * @return void
     */
    public function configureCommand()
    {
        $this->setName('attribute-migration:boolean-to-select')->setDescription('Migrating an Boolean-Attribute to another Select-Attribute');

        $this->addInteractiveQuestion(self::FIELD_OLD_ATTRIBUTE, new Question('Old Attribute Key: '));
        $this->addInteractiveQuestion(self::FIELD_NEW_ATTRIBUTE, new Question('New Attribute Key: '));
    }

    /**
     * Execute Command
     *
     * @return void
     *
     * @throws \Exception
     */
    public function executeCommand()
    {
        $oldAttributeKey = $this->interactiveArguments[self::FIELD_OLD_ATTRIBUTE];
        $newAttributeKey = $this->interactiveArguments[self::FIELD_NEW_ATTRIBUTE];

        $this->output->logInfo('Migrating ' . $oldAttributeKey . ' to ' . $newAttributeKey . '...');

        $oldAttribute = $this->attributeUtility->getAttribute($oldAttributeKey);
        $newAttribute = $this->attributeUtility->getAttribute($newAttributeKey);

        if ($oldAttribute->getFrontendInput() != 'boolean' || $newAttribute->getFrontendInput() != 'select') {
            throw new \Exception('Unsupported Attribute Types (' . $oldAttribute->getFrontendInput() . ' | ' . $newAttribute->getFrontendInput() . ')');
        }

        /** @var ProductCollection $productCollection */
        $productCollection = $this->productFactory->create()->getCollection();
        $productCollection->addStoreFilter(0);
        $productCollection->addAttributeToFilter($oldAttribute->getAttributeCode(), ['notnull' => true]);
        $productCollection->addAttributeToSelect('*');
        $productCollection->setPageSize(100);

        $this->walkProgress($productCollection, function ($product) use ($oldAttribute, $newAttribute) {
            /** @var $product Product */

            $oldValue = (bool) $product->getData($oldAttribute->getAttributeCode());
            $newValue = $product->getData($newAttribute->getAttributeCode());
            $newLabel = $oldValue ? __('Yes') : __('No');

            if (!empty($newValue)) {
                return $product->getSku() . ' (new attribute already filled)';
            }

            $newValueId = $this->attributeUtility->createOrGetOptionId($newAttribute->getAttributeCode(), $newLabel);

            $product->setData($newAttribute->getAttributeCode(), $newValueId);
            $product->getResource()->saveAttribute($product, $newAttribute->getAttributeCode());

            return $product->getSku();
        });
    }
}