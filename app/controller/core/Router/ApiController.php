<?php

namespace Router;

use \Slim\Slim;
use Entity\Player;

abstract class ApiController extends Controller
{

    const API_KEY = 'kDjE9KfmD2Pd9KmcFkSdFqlK6Mfjz09dKdqS';

    const PLAYER_JOINED = 'player.joined';
    const OPPONENT_SCORES = 'opponent.scores';
    const OPPONENT_WINS = 'opponent.wins';

    protected function printJsonResponse($data = array())
    {
        $response = $this->slimInstance->response();
        $response->header('Content', 'application/json');

        $data = array_merge(array('result'=>true), $data);

        $response->body(json_encode($data));
        $response->finalize();

    }

    protected function printJsonError($error)
    {
        $this->printJsonResponse(array(
                'result' => false,
                'error' => $error,
            )
        );
    }

    protected function sendPushNotification($dest, $msg)
    {
        // do
    }

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
        $player = \Entity\Player::factory()->where('nick',$nick)->find_one();

        if(!$player){
            $this->printJsonError(self::ERROR_PLAYER_DOESNT_EXISTS);
        }
        if($player->password != $request->headers('PASSWORD')){
            $this->badCredentials();
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
        $response->finalize();
    }

}
