<?php

require_once __DIR__ . '/../FeedExportPluginTestCase.php';

use Logingrupa\FacebookCatalogShopaholic\Classes\Helper\AbstractPriceComparisonHelper;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * 1C sends negative stock for oversold offers; KurPirkt.lv and Salidzini.lv read the
 * stock field as units on hand.
 */
class PriceComparisonStockTest extends FeedExportPluginTestCase
{
    /** No table */
    protected $autoMigrate = false;

    #[DataProvider('quantityProvider')]
    public function testStockIsNeverNegative($mQuantity, int $iExpected)
    {
        $obHelper = new class extends AbstractPriceComparisonHelper {
            protected function initOffer($obOffer, $obProduct)
            {
            }

            public function stockOf($obOffer)
            {
                return $this->getStockQuantity($obOffer);
            }
        };

        $this->assertSame($iExpected, $obHelper->stockOf((object) ['quantity' => $mQuantity]));
    }

    public static function quantityProvider(): array
    {
        return [
            'oversold'  => [-16, 0],
            'minus one' => [-1, 0],
            'sold out'  => [0, 0],
            'in stock'  => [5, 5],
            'null'      => [null, 0],
        ];
    }
}
