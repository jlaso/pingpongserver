<?php

namespace Router;

use JLaso\SlimRoutingManager\Controller\Controller;
use \Slim\Slim;
use Entity\Player;

abstract class ApiController extends Controller
{

    const API_KEY = 'kDjE9KfmD2Pd9KmcFkSdFqlK6Mfjz09dKdqS';

    const PLAYER_JOINED   = 'player.joined';
    const OPPONENT_SCORES = 'opponent.scores';
    const OPPONENT_WINS   = 'opponent.wins';

    const ERROR_PLAYER_DOESNT_EXISTS   = 'error.player_doesnt_exists';
    const ERROR_MATCH_DOESNT_EXISTS    = 'error.match_doesnt_exists';
    const ERROR_MATCH_NOT_STARTED      = 'error.match_not_started';
    const ERROR_PLAYER_NOT_PLAYING     = 'error.player_not_playing';
    const ERROR_MATCH_STARTED          = 'error.match_started';
    const ERROR_CLOUD_ID_NOT_SPECIFIED = 'error.cloud_id_not_specified';

    const NOTIF_MATCH_STARTS = 1;  // An opponent has joined to your match
    const NOTIF_YOU_WIN = 2;
    const NOTIF_OTHER_WINS = 3;
    const NOTIF_SCORE_UPDATE = 4;

    protected function printJsonResponse($data = array())
    {
        $response = $this->slimInstance->response();
        $response->header('Content', 'application/json');
        $response->header('Cache-Control', 'no-cache, must-revalidate');
        $response->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GTM');

        $data = array_merge(array('result'=>true), $data);

        $response->body(json_encode($data));
        $response->finalize();
        $this->slimInstance->stop();
    }

    protected function printJsonError($error)
    {
        $this->printJsonResponse(array(
                'result' => false,
                'error' => $error,
            )
        );
    }


    /**
     * @param int|array $dest
     * @param int $type
     * @param $msg
     */
    protected function sendPushNotification($dest, $type, $msg, $data = array())
    {
        $tmpFile = realpath(__DIR__ . "/../../..") . "/app/cache/cookie.txt";

        $channel = 'notifications';
        $to_ids = is_array($dest) ? implode(',', $dest) : $dest;

        $json = json_encode(
            array_merge(
                array(
                    'badge' => '+1',
                    'type'  => $type,
                    'sound' => 'default',
                    'icon'  => 'appicon',
                    'alert' => utf8_encode($msg),
                ),
                $data
            )
        );

        $curl = curl_init();

        $c_opt = array(
            CURLOPT_URL            => 'https://api.cloud.appcelerator.com/v1/users/login.json?key=' . ACS_APP_KEY,
            CURLOPT_COOKIEJAR      => $tmpFile,
            CURLOPT_COOKIEFILE     => $tmpFile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => "login=" . ACS_USER . "&password=" . ACS_PASSWORD,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_TIMEOUT        => 60
        );

        /*** LOGIN **********************************************/
        curl_setopt_array($curl, $c_opt);
        $session = curl_exec($curl);

        $c_opt[CURLOPT_URL] = "https://api.cloud.appcelerator.com/v1/push_notification/notify.json?key=" . ACS_APP_KEY;
        $c_opt[CURLOPT_POSTFIELDS] = "channel={$channel}&to_ids={$to_ids}&payload={$json}";
        curl_setopt_array($curl, $c_opt);
        $result = curl_exec($curl);
        curl_close($curl);

        print_r($result);
    }

    /**
     * Checks if the credentials passed are OK
     */
    protected function checkApiKey()
    {
        $request = $this->slimInstance->request();

        if(self::API_KEY != $request->headers('API-KEY')){
            $this->notFound();
        }
    }

    /**
     * @return Player
     */
    protected function getUserAndCheckPassword()
    {
        $request = $this->slimInstance->request();
        $nick = $request->headers('PLAYER');

        /** @var Player $player */
        $player = \Entity\Player::factory()->where('nick', $nick)->find_one();

        if(!$player){
            $this->printJsonError(self::ERROR_PLAYER_DOESNT_EXISTS);
        }else{
            if($player->password != $request->headers('PASSWORD')){
                $this->badCredentials();
            }
        }
        return $player;
    }

    protected function notFound()
    {
        $this->slimInstance->pass();
    }

    protected function badCredentials()
    {
        $response = $this->slimInstance->response();
        $response->status(403);
        $response->header('HTTP/1.0 403 Forbbiden');
        //$response->body('error 403');
        $response->finalize();
        $this->slimInstance->stop();
    }

}
