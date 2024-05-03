<?php

/**
 * This file is part of the Wedo Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;

/**
 * ImageOptimizerService
 *
 * Class to handle image functions
 *
 * @package         Wedo
 * @subpackage      App
 * @author          Vidya
 */
class ImageOptimizerService
{

    /**
     * @var CacheManager $cacheManager
     */
    private CacheManager $cacheManager;

    /**
     * @var DataManager $dataManager
     */
    private DataManager $dataManager;

    /**
     * @var FilterManager $filterManager
     */
    private FilterManager $filterManager;

    /**
     * Constructor
     *
     * @param CacheManager $cacheManager
     * @param DataManager $dataManager
     * @param FilterManager $filterManager
     */
    public function __construct(CacheManager $cacheManager, DataManager $dataManager, FilterManager $filterManager)
    {
        $this->cacheManager = $cacheManager;
        $this->dataManager = $dataManager;
        $this->filterManager = $filterManager;
    }

    /**
     * Function for resize image and store it
     *
     * @param string $fileName
     * @param string $newFileName
     * @param string $filter
     * @param integer $width
     * @param integer $height
     * @return void
     */
    public function resizeAndStoreImage(string $fileName, string $newFileName, string $filter, int $width, int $height): void
    {
        if (!$this->cacheManager->isStored($fileName, $filter)) {
            $binary = $this->dataManager->find($filter, $fileName);
            $filteredBinary = $this->filterManager->applyFilter($binary, $filter, [
                'filters' => [
                    'thumbnail' => [
                        'size' => [$width, $height]
                    ]
                ]
            ]);
            $thumb = $filteredBinary->getContent();
            $f = fopen($newFileName, 'w+');
            fwrite($f, $thumb);
            fclose($f);
        }
    }
}
