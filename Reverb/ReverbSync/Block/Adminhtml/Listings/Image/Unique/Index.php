<?php
namespace Reverb\ReverbSync\Block\Adminhtml\Listings\Image\Unique;
class Index extends \Reverb\ProcessQueue\Block\Adminhtml\Unique\Index
{
    public function getTaskCodeToFilterBy()
    {
        return 'listing_image_sync';
    }

    protected function _expediteTasksButtonLabel()
    {
        return 'Expedite Image Sync Tasks';
    }

    protected function _getHeaderTextTemplate()
    {
        return '%s of %s Listings Image Sync Tasks have completed syncing';
    }
}
