<?php

/**
 * This file is part of the Balu 2 Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Entity\Address;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Entity\SubscriptionPlan;
use App\Entity\Property;
use App\Entity\Apartment;
use App\Utils\ValidationUtility;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use App\Utils\ContainerUtility;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * GeneralUtility
 *
 * Utility class to handle general functions
 *
 * @package         PITS
 * @subpackage      App
 * @author          Rahul <rahul.rl@pitsolutions.com>
 */
class GeneralUtility
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    public function __construct(ManagerRegistry $doctrine, ParameterBagInterface $params)
    {
        $this->doctrine = $doctrine;
        $this->params = $params;
    }

    /**
     * Function to response data
     *
     * @param null $data
     * @param string|null $message
     * @param bool $isObject
     * @param int $status
     * @param bool $toJson
     *
     * @return array
     */
    public function handleSuccessResponse(?string $message, $data = null, bool $isObject = false,
                                          int $status = 200, bool $toJson = false): ?array
    {
        $response = [];
        if (!$toJson && $this->isJSON($data)) {
            $response['data'] = json_decode($data, true);
        } else if (!$toJson && $isObject && \is_array($data)) {
            $response['data'] = $this->buildResponseFromArray($data);
        } else {
            $response['data'] = $data;
        }
        $response['message'] = $message;
        $response['error'] = false;
        $response['status'] = $status;
        return $response;
    }

    /**
     * Function to build failed response
     *
     * @param string|null $message
     * @param int $status
     * @param null $object
     * @param null $data
     * @param bool $toJson
     * @return array
     */
    public function handleFailedResponse(?string $message, int $status = 400, $object = null, $data = null, bool $toJson = false): array
    {
        $response = [];
        $response['error'] = true;
        if (\is_array($object)) {
            $response['data'] = $object;
        } else if (!$toJson && $this->isJSON($data)) {
            $response['data'] = json_decode($data, true);
        } else if (!$toJson && $object && \is_array($data)) {
            $response['data'] = $this->buildResponseFromArray($data);
        } else {
            $response['data'] = $data;
        }
        $response['message'] = $message;
        $response['status'] = $status;

        return $response;
    }

    /**
     * Function to build response from array
     *
     * @param array $data
     *
     * @return array
     */
    private function buildResponseFromArray(array $data): array
    {
        $response = array();
        if (count($data) > 1) {
            foreach ($data as $key => $datum) {
                if ($this->isJSON($datum)) {
                    $response = json_decode($datum, true);
                } else {
                    foreach ($this->sanitizeKey((array)$datum) as $pKey => $pValue) {
                        $response[$pKey] = $pValue;
                    }
                }
            }
        }
        return $response;
    }

    /**
     * Function to check if a string is json or not
     *
     * @param $string
     *
     * @return bool
     */
    private function isJSON($string): bool
    {
        return \is_string($string) && \is_array(json_decode($string, true));
    }

    /**
     * Function to sanitize array key
     *
     * @param array $arr
     *
     * @return array
     */
    private function sanitizeKey(array $arr): array
    {
        $result = array();
        foreach ($arr as $key => $value) {
            $result[preg_replace('/[\x00-\x1F\x7F-\xFF-*]/', '', $key)] = $value;
        }
        return $result;
    }

    /**
     *
     * @param array $aErrors
     * @return array
     */
    public static function formatErrors(array $aErrors): array
    {
        $sFormattedMessage = [];
        foreach ($aErrors as $sLabel => $aError) {
            if (array_keys($aError) === range(0, count($aError) - 1)) {
                $sFormattedMessage[$sLabel] = implode('|', $aError);
            } else {
                $sFormattedMessage[$sLabel] = self::formatErrors($aError);
            }
        }

        return $sFormattedMessage;
    }

    /**
     * Function to get base url
     *
     * @param Request $request
     * @return string
     */
    public function getBaseUrl(Request $request): string
    {
        return $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
    }

    /***
     * getApartmentIcon
     *
     * @param int $count
     * @param  $request
     *
     * @return string
     */

    public function getApartmentIcon(int $count, Request $request): string
    {
        switch (true) {
            case ($count == 0):
                $icon = $this->params->get('apartment_count')['default_apartment_image_path'];
                break;
            case ($count == 1):
            case ($count == 2):
                $icon = $this->params->get('apartment_count')['single_apartment_image_path'];
                break;
            case ($count > 2):
                $icon = $this->params->get('apartment_count')['multiple_apartment_image_path'];
                break;
            default:
                $icon = null;
                break;
        }

        return $request->getScheme() . '://' . $request->getHttpHost() . $icon;
    }

    /**
     * Get date difference
     * @param \DateTime $date1
     * @param \DateTime $date2
     *
     * @return array|float|int
     */
    public function getDateInterval(\DateTime $date1, \DateTime $date2)
    {
        $date1Formatted = date_create($date1->format('y-m-d'));
        $date2Formatted = date_create($date2->format('y-m-d'));
        $interval = date_diff($date2Formatted, $date1Formatted)->format('%r%a');
        if ($interval >= 0) {
            return abs($interval);
        }
        return false;
    }

    /**
     * getGMTOffsetDate
     *
     * @param string $dateTime
     * @param int|null $gmtOffset
     * @param bool $isdst
     * @param bool $isSave
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getGMTOffsetDate(string $dateTime, ?int $gmtOffset = -1, ?bool $isdst = false, bool $isSave = true): \DateTime
    {
        $defaultTimeZone = $this->params->get('default_time_zone');
        $gmtOffset = abs($gmtOffset);
        $userTimeZone = timezone_name_from_abbr("", $gmtOffset * 60, $isdst);
        if (!$isSave) {
            $newDateTimeObj = new \DateTime($dateTime, new \DateTimeZone($defaultTimeZone));
            $newDateTimeObj->setTimeZone(new \DateTimeZone($userTimeZone));
        } else {
            $newDateTimeObj = new \DateTime($dateTime, new \DateTimeZone($userTimeZone));
            $newDateTimeObj->setTimeZone(new \DateTimeZone($defaultTimeZone));
        }

        return $newDateTimeObj;
    }

    /**
     * @param string $string
     * @param bool $capitalizeFirstCharacter
     * @return string
     */
    public function snakeToCamelCaseConverter(string $string, bool $capitalizeFirstCharacter = false): string
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }

        return $str;
    }
}