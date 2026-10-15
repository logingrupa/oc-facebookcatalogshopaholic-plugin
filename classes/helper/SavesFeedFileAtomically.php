<?php namespace Logingrupa\FacebookCatalogShopaholic\Classes\Helper;

use File;

/**
 * Trait SavesFeedFileAtomically
 *
 * The feed is written to a temp file in the same directory and renamed over the old one,
 * so a crawler fetching during an export gets the old or the new file, never a 404.
 *
 * @package Logingrupa\FacebookCatalogShopaholic\Classes\Helper
 */
trait SavesFeedFileAtomically
{
    /**
     * Save generated content
     */
    protected function save()
    {
        $sFilePath = storage_path(static::getFilePath());
        $sTempFilePath = $sFilePath . '.tmp';

        File::put($sTempFilePath, $this->sContent);
        File::move($sTempFilePath, $sFilePath);
    }
}
