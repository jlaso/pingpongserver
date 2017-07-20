<?php

namespace Router;

use Entity\Notification;
use JLaso\SlimRoutingManager\Controller\Controller;
use \Slim\Slim;
use Entity\Player;

abstract class ApiController extends Controller
{

    const API_KEY = 'kDjE9KfmD2Pd9KmcFkSdFqlK6Mfjz09dKdqS';

    const PLAYER_JOINED   = 'player.joined';
    const OPPONENT_SCORES = 'opponent.scores';
    const OPPONENT_QUITS = 'opponent.quits';
    const OPPONENT_WINS   = 'opponent.wins';

    const ERROR_PLAYER_DOESNT_EXISTS        = 'error.player_doesnt_exists';
    const ERROR_PLAYER_EMAIL_EXISTS_ALREADY = 'error.player_email_exists_already';
    const ERROR_PLAYER_NICK_EXISTS_ALREADY  = 'error.player_nick_exists_already';
    const ERROR_MATCH_DOESNT_EXISTS         = 'error.match_doesnt_exists';
    const ERROR_MATCH_NOT_STARTED           = 'error.match_not_started';
    const ERROR_PLAYER_NOT_PLAYING          = 'error.player_not_playing';
    const ERROR_MATCH_STARTED               = 'error.match_started';
    const ERROR_CLOUD_ID_NOT_SPECIFIED      = 'error.cloud_id_not_specified';
    const ERROR_EMAIL_NOT_VALID             = 'error.email_not_valid';
    const ERROR_NICK_TOO_SHORT              = 'error.nick_too_short';
    const ERROR_PASSWORD_NOT_STRENGTH       = 'error.password_not_strength';

    const NOTIF_MATCH_STARTS = 1;  // An opponent has joined to your match
    const NOTIF_YOU_WIN = 2;
    const NOTIF_OTHER_WINS = 3;
    const NOTIF_SCORE_UPDATE = 4;
    const NOTIF_OPPONENT_QUITS = 5;

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
     * @param \Entity\Player $player
     * @param \Entity\Match $match
     *
     * @return array
     */
    protected function getScore(\Entity\Player $player, \Entity\Match $match)
    {
        $score = array();
        /** @var \Entity\Player $opponent */
        if($player->id == $match->player1){
            $score['you'] = $match->score1 ?: 0;
            $score['other'] = $match->score2 ?: 0;
        }else{
            $score['you'] = $match->score2 ?: 0;
            $score['other'] = $match->score1 ?: 0;
        }

        return $score;
    }

    /**
     * Send a $type notification to player
     *
     * @param string $player
     * @param int $type
     * @param $msg
     *
     * @return Notification
     */
    protected function sendInternalNotification($player, $type, $msg)
    {
        /** @var Notification $notification */
        $notification = Notification::factory()->create();
        $notification->nick = $player;
        $notification->type = $type;
        $notification->message = $msg;
        $notification->save();

        return $notification;
    }

    /**
     * Send a $type notification to device containing the $data and $msg as alert title
     *
     * @param int|array $dest
     * @param int $type
     * @param $msg
     * @param array $data
     */
    protected function sendPushNotification($dest, $type, $msg, $data = array())
    {
        /*
        $tmpFile = realpath(__DIR__ . "/../../..") . "/app/cache/cookie.txt";
        $channel = 'notifications';
        $to_ids  = is_array($dest) ? implode(',', $dest) : $dest;
        $payload = json_encode(
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

        // login in ACS system
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

        curl_setopt_array($curl, $c_opt);
        $session = curl_exec($curl);

        $c_opt[CURLOPT_URL]        = "https://api.cloud.appcelerator.com/v1/push_notification/notify.json?key=" . ACS_APP_KEY;
        $c_opt[CURLOPT_POSTFIELDS] = "channel={$channel}&to_ids={$to_ids}&payload={$payload}";
        curl_setopt_array($curl, $c_opt);
        $result = curl_exec($curl);
        curl_close($curl);
        */
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
