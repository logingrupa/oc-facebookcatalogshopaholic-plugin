<?php namespace Logingrupa\FacebookCatalogShopaholic\Classes\Helper;

use File;
use RuntimeException;
use Throwable;

/**
 * Trait SavesFeedFileAtomically
 *
 * The feed is written to a temp file in the same directory and renamed over the old one,
 * so a crawler fetching during an export gets the old or the new file, never a 404.
 * Each writer gets its own temp file, so concurrent exports never rename each other's half.
 *
 * @package Logingrupa\FacebookCatalogShopaholic\Classes\Helper
 */
trait SavesFeedFileAtomically
{
    /**
     * The XML is built in memory before save(), so a temp file only lives between put and
     * move; one older than this is the orphan of a killed process.
     */
    const STALE_TEMP_FILE_SECONDS = 3600;

    /**
     * Save generated content
     */
    protected function save()
    {
        $sFilePath = storage_path(static::getFilePath());
        $sTempFilePath = $sFilePath . '.' . bin2hex(random_bytes(8)) . '.tmp';

        $this->sweepStaleTempFiles($sFilePath);

        try {
            if (File::put($sTempFilePath, $this->sContent) !== strlen($this->sContent)) {
                throw new RuntimeException('Short write to feed temp file ' . $sTempFilePath);
            }
            if (!File::move($sTempFilePath, $sFilePath)) {
                throw new RuntimeException('Could not move feed temp file over ' . $sFilePath);
            }
        } catch (Throwable $obException) {
            File::delete($sTempFilePath);
            throw $obException;
        }
    }

    /**
     * @param string $sFilePath
     */
    protected function sweepStaleTempFiles($sFilePath)
    {
        $iStaleBefore = time() - self::STALE_TEMP_FILE_SECONDS;
        foreach (File::glob($sFilePath . '.*.tmp') as $sStaleTempPath) {
            // another writer may move or sweep the file between glob and stat
            $iModifiedAt = @filemtime($sStaleTempPath);
            if ($iModifiedAt !== false && $iModifiedAt < $iStaleBefore) {
                File::delete($sStaleTempPath);
            }
        }
    }
}
