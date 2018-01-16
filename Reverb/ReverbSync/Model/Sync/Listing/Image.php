<?php
namespace Reverb\ReverbSync\Model\Sync\Listing;
class Image extends \Reverb\ProcessQueue\Model\Task
{
    const ERROR_INVALID_SKU = 'An attempt was made to transmit an image to Reverb for an invalid sku: %s';
    const ERROR_EMPTY_IMAGE_URL = 'No image url was set on the Reverb Task Arguments Object';

    protected $_productRepository;

    protected $_adapterListingsImage;

    public function __construct(
        \Reverb\ProcessQueue\Model\Task\Result $taskResult,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Reverb\ReverbSync\Helper\Api\Adapter\Listings\Image $adapterListingsImage
    ) {
        $this->_productRepository = $productRepository;
        $this->_adapterListingsImage = $adapterListingsImage;
        parent::__construct($taskResult, $datetime, $context, $registry);
    }

    /**
     * We expect the calling block to catch exceptions as part of the task processing
     *
     * @param stdClass $argumentsObject
     */
    public function transmitGalleryImageToReverb($argumentsObject)
    {
        $sku = isset($argumentsObject->sku) ? $argumentsObject->sku : null;
        // Validate the sku
        $magento_entity = $this->_productRepository->get($sku);
        if (empty($magento_entity->getId()))
        {
            $error_message = __(self::ERROR_INVALID_SKU, $sku);
            return $this->_returnAbortCallbackResult($error_message);
        }

        $image_url = isset($argumentsObject->url) ? $argumentsObject->url : null;
        if (empty($image_url))
        {
            $error_message = __(self::ERROR_EMPTY_IMAGE_URL);
            return $this->_returnAbortCallbackResult($error_message);
        }

        $this->_adapterListingsImage->transmitGalleryImageToReverb($sku, $image_url);
    }
}
