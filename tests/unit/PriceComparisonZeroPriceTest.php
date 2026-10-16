<?php

require_once __DIR__ . '/../FeedExportPluginTestCase.php';

use Logingrupa\FacebookCatalogShopaholic\Classes\Helper\AbstractPriceComparisonHelper;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * KurPirkt.lv and Salidzini.lv list a 0.00 offer as free and Salidzini.lv sorts it first.
 */
class PriceComparisonZeroPriceTest extends FeedExportPluginTestCase
{
    /** No table */
    protected $autoMigrate = false;

    #[DataProvider('priceProvider')]
    public function testOnlyOffersWithAPositivePriceAreListed($mPrice, bool $bIsListed)
    {
        $obHelper = new class extends AbstractPriceComparisonHelper {
            protected function initOffer($obOffer, $obProduct)
            {
                $this->arData['offers'][] = $obOffer;
            }

            public function offerRowsOf($obProduct)
            {
                $this->initOfferListData($obProduct);

                return $this->arData['offers'];
            }
        };

        $obOffer = (object) ['price_value' => $mPrice];
        $obProduct = (object) [
            'category' => new class {
                public function isEmpty()
                {
                    return false;
                }
            },
            'offer'    => [$obOffer],
        ];

        $this->assertSame($bIsListed ? [$obOffer] : [], $obHelper->offerRowsOf($obProduct));
    }

    public static function priceProvider(): array
    {
        return [
            'zero'     => [0.0, false],
            'no price' => [null, false],
            'negative' => [-1.5, false],
            'one cent' => [0.01, true],
            'regular'  => [12.5, true],
        ];
    }
}
