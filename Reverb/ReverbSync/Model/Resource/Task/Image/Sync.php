<?php
namespace Reverb\ReverbSync\Model\Resource\Task\Image;
class Sync extends \Reverb\ReverbSync\Model\Resource\Task\Unique
{
    const ORDER_CREATION_OBJECT = '\Reverb\ReverbSync\Model\Sync\Listing\Image';
    const ORDER_CREATION_METHOD = 'transmitGalleryImageToReverb';

    protected $_task_code = 'listing_image_sync';
    protected $_imageSyncHelper = null;

    public function queueListingImageSync($sku, \Magento\Framework\DataObject $galleryImageObject)
    {
        $unique_id_key = $this->getImageSyncUniqueIdValue($sku, $galleryImageObject);

        $relative_image_file_path = $galleryImageObject->getFile();
        $exploded_file_path = explode('/', $relative_image_file_path);
        $image_file_name = end($exploded_file_path);

        $insert_data_array = $this->_getUniqueInsertDataArrayTemplate(self::ORDER_CREATION_OBJECT, self::ORDER_CREATION_METHOD,
                                                                        $unique_id_key, $image_file_name);
        $arguments_data_to_serialize = $galleryImageObject->getData();
        $arguments_data_to_serialize['sku'] = $sku;

        $serialized_arguments_value = serialize($arguments_data_to_serialize);
        $insert_data_array['serialized_arguments_object'] = $serialized_arguments_value;

        $number_of_inserted_rows = $this->getConnection()->insert($this->getMainTable(), $insert_data_array);

        return $number_of_inserted_rows;
    }

    public function getTaskCode()
    {
        return $this->_task_code;
    }

    public function getImageSyncUniqueIdValue($sku, \Magento\Framework\DataObject $galleryImageObject)
    {
        $catalog_product_entity_media_gallery_id = $galleryImageObject->getValueId();
        $unique_id = $sku . '_' . $catalog_product_entity_media_gallery_id;
        return $unique_id;
    }
}
