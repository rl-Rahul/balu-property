<?php


namespace App\Service;

use App\Utils\ContainerUtility;
use App\Utils\GeneralUtility;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class BaseService
 * @package App\Service
 */
abstract class BaseService
{
    /**
     * @param string $input
     * @return string
     */
    protected function camelCaseConverter(string $input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * @param string $string
     * @param bool $capitalizeFirstCharacter
     * @return string
     */
    protected function snakeToCamelCaseConverter(string $string, bool $capitalizeFirstCharacter = false): string
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }

    /**
     * @param string $name
     * @param bool $capitalizeFirstCharacter
     * @return string
     */
    public function getFunctionName(string $name, bool $capitalizeFirstCharacter = false): string
    {
        return $this->snakeToCamelCaseConverter($name, $capitalizeFirstCharacter);
    }

    /**
     * Get date difference
     * @param \DateTime $date1
     * @param \DateTime $date2
     *
     * @return int|null
     */
    protected function getDateInterval(\DateTime $date1, \DateTime $date2): ?int
    {
        $date1Formatted = date_create($date1->format('y-m-d'));
        $date2Formatted = date_create($date2->format('y-m-d'));
        $interval = date_diff($date2Formatted, $date1Formatted)->format('%r%a');
        if ($interval >= 0) {
            return abs($interval);
        }
        return false;
    }
}