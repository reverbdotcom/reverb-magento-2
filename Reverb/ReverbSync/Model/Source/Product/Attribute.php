<?php
namespace Reverb\ReverbSync\Model\Source\Product;
class Attribute implements \Magento\Framework\Option\ArrayInterface
{
    public function __construct(
         \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Returns an array which has Magento product attribute codes mapped to the attribute labels
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options_array = [];
        
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributeRepository = $this->attributeRepository->getList(
            'catalog_product',
            $searchCriteria
        );

        foreach ($attributeRepository->getItems() as $items) {
            $attribute_code = $items->getAttributeCode();
            $attribute_label = $items->getFrontendLabel();
            $attribute_label = (!empty($attribute_label)) ? $attribute_label : $attribute_code;
            $options_array[$attribute_code] = $attribute_label;
        }

        asort($options_array);
        array_unshift($options_array, ['value'=>'','label'=>'Please Select']);
        return $options_array;
    }
}
