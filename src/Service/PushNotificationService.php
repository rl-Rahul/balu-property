<?php
/**
 * This file is part of the BaluProperty package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Push Notification Services
 *
 * Push Notification Service actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class PushNotificationService
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }


    /**
     * sendPushNotification
     *
     * @param array $params
     * @param array $deviceIds
     * @return type
     */
    public function sendPushNotification(array $params, array $deviceIds)
    {
        $content = array(
            "en" => $params['message']
        );
        $pushAppId = ($this->parameterBag->get('push_app_id')) ? $this->parameterBag->get('push_app_id') : '';
        unset($params['message']);
        $fields = array(
            'app_id' => $pushAppId,
            'include_player_ids' => $deviceIds,
            'data' => $params,
            'contents' => $content
        );
        $fields = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->parameterBag->get('one_signal_url'));
        $oneSignalHeader = $this->parameterBag->get('one_signal_header');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
            'Authorization: Basic ' . $oneSignalHeader));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
