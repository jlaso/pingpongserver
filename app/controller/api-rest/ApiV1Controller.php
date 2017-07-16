<?php

use Router\ApiController;

class ApiV1Controller extends ApiController
{

    /**
     * @Route('/api/v1/version')
     * @Name('api.v1.version')
     */
    public function versionAction()
    {
        //$this->checkApiKey();

        $this->printJsonResponse(array('version' => 'v1'));
    }

    /**
     * @Route('/api/v1/players.json')
     * @Name('api.v1.players.json')
     */
    public function playersIndexAction()
    {
        $this->checkApiKey();

        $criteria = $this->slimInstance->request()->get('criteria');
        $players = array();
        /** @var Player[] $allPlayers */
        $allPlayers = \Entity\Player::factory()->find_many();

        foreach($allPlayers as $player){
            if(!$criteria || preg_match("/{$criteria}/", $player->nick)){
                $players[] = $player->asArray();
            }
        }

        $this->printJsonResponse(array('players'=>$players));
    }

    /**
     * @Route('/api/v1/login')
     * @Name('api.v1.login')
     * @Method('POST')
     */
    public function loginAction()
    {
        $this->checkApiKey();
        $player = $this->getUserAndCheckPassword();

        $this->printJsonResponse(array('cloudId'=>$player->cloud_id));
    }

    /**
     * @Route('/api/v1/register-user')
     * @Name('api.v1.register-user')
     * @Method('POST')
     */
    public function registerUserAction()
    {
        $this->checkApiKey();

        /** @var \Slim\Http\Request $request */
        $request = $this->slimInstance->request();

        $email = trim(strtolower($request->post('email')));
        $nick = trim(strtolower($request->post('nick')));
        $password = $request->post('password');

        // check if email is an email, etc ... (validate fields)
        if(! \lib\MyFunctions::check_email($email)){
            $this->printJsonError(self::ERROR_EMAIL_NOT_VALID);
        }
        if(strlen($nick)<5){
            $this->printJsonError(self::ERROR_NICK_TOO_SHORT);
        }

        /** @var Player $player */
        $player = \Entity\Player::factory()->where('nick', $nick)->find_one();
        //var_dump($player); die;
        if($player){
            $this->printJsonError(self::ERROR_PLAYER_EMAIL_EXISTS_ALREADY);
        }
        /** @var Player $player */
        $player = \Entity\Player::factory()->where('email', $email)->find_one();
        if($player){
            $this->printJsonError(self::ERROR_PLAYER_EMAIL_EXISTS_ALREADY);
        }
        $pwdCheck = new \Psecio\Pwdcheck\Password();
        $pwdCheck->evaluate($password);
        if($pwdCheck->getScore()<70){
            $this->printJsonError(self::ERROR_PASSWORD_NOT_STRENGTH);
        };

        /** @var Player $player */
        $player = \Entity\Player::factory()->create();
        $player->email = $email;
        $player->nick = $nick;
        $player->password = sha1($password);
        $player->save();

        $body = <<<EOD
        Welcome to <b>PingPongCounter app</b> <br/><br/>
        Your nick is <b>{$nick}</b><br/>
        and your password is <b>{$password}</b><br/>
        <br/>
        Enjoy the app.<br/><br/>
        The <b>PingPongCounter app</b> team.<br/>
EOD;
        \lib\MyFunctions::sendEmail($email, 'Welcome to PingPongCounter', $body);

        $this->printJsonResponse(array('id'=>$player->id));
    }

    /**
     * @Route('/api/v1/set-cloud-id')
     * @Name('api.v1.set-cloud-id')
     * @Method('POST')
     */
    public function setCloudIdAction()
    {
        $this->checkApiKey();
        $player = $this->getUserAndCheckPassword();

        /** @var \Slim\Http\Request $request */
        $request = $this->slimInstance->request();
        $cloudId = $request->post('cloudId') ?: 0;

        if(!$cloudId){
            $this->printJsonError(self::ERROR_CLOUD_ID_NOT_SPECIFIED);
        }

        $player->cloud_id = $cloudId;
        $player->save();

        $this->printJsonResponse();
    }

    /**
     * @Route('/api/v1/start-match')
     * @Name('api.v1.start-match')
     * @Method('PUT')
     */
    public function playerStartMatchAction()
    {
        $this->checkApiKey();
        $player = $this->getUserAndCheckPassword();

        $request   = $this->slimInstance->request();
        $toPoints  = $request->get('toPoints') ? : 21;
        $longitude = $request->params("longitude");
        $latitude  = $request->params("latitude");

        /** @var \Entity\Match[] $matches */
        $matches = \Entity\Match::factory()
            ->where('player1', $player->id)
            ->where_null('player2')
            ->where_null('finished_at')
            ->find_many();

        if(count($matches)){
            foreach($matches as $match){
                $match->delete();
            }
        }

        /** @var \Entity\Match $match */
        $match = \Entity\Match::factory()->create();
        $match->player1 = $player->id;
        $match->created_at = date("Y-m-d h:i:s");
        $match->to_points = $toPoints;
        $match->longitude = $longitude;
        $match->latitude = $latitude;
        $match->save();

        $player->match_id = $match->id;
        $player->save();

        $this->printJsonResponse(array('match'=>$match->asArray()));
    }

    /**
     * @Route('/api/v1/search-match')
     * @Name('api.v1.search-match')
     */
    public function playerSearchMatchAction()
    {
        $this->checkApiKey();
        $player = $this->getUserAndCheckPassword();

        $request   = $this->slimInstance->request();
        $criteria = $request->get('criteria');
        $longitude = $request->get("longitude");
        $latitude  = $request->get("latitude");

        $players = array();
        /** @var Player[] $allPlayers */
        $allPlayers = \Entity\Player::factory()
            ->where_not_equal('id', $player->id)
            ->find_many();

        foreach($allPlayers as $p){
            if(!$criteria || preg_match("/{$criteria}/", $p->nick)){
                $players[$p->id] = $p->nick;
            }
        }
        $result = array();
        /** @var \Entity\Match[] $matches */
        $matches = \Entity\Match::factory()
            ->where_null('player2')
            ->where_null('finished_at')
            ->where_in('player1', array_keys($players))
            ->find_many()
        ;

        $matchesByDistance = array();
        $playersFound = array();
        if(count($matches)){
            foreach($matches as $match){
                $result[$match->id] = $match->asArray();
                $matchesByDistance[$match->id] = \lib\MyFunctions::distance($match->latitude, $match->longitude, $latitude, $longitude);
                //$players[$match->player1] = $match->player1;
                $playersFound[$match->player1] = $players[$match->player1];
            }
        }

        // sort by distance
        $resultSorted = array();
        asort($matchesByDistance);
        foreach($matchesByDistance as $id=>$distance){
            $resultSorted[] = array_merge($result[$id], array('distance'=>$distance));
        }

        $this->printJsonResponse(
            array(
                'matches' => $resultSorted,
                'players' => $playersFound,
            )
        );
    }

    /**
     * @Route('/api/v1/join-match/:matchId')
     * @Name('api.v1.join-match')
     * @Method('PUT')
     */
    public function playerJoinMatchAction($matchId)
    {
        $this->checkApiKey();

        /** @var \Entity\Match $match */
        $match = \Entity\Match::factory()->find_one($matchId);
        $player = $this->getUserAndCheckPassword();
        /** @var \Entity\Player $opponent */
        $opponent = \Entity\Player::factory()->find_one($match->player1);

        if(!$match){
            $this->printJsonError(self::ERROR_MATCH_DOESNT_EXISTS);
        }elseif($match->started()){
            $this->printJsonError(self::ERROR_MATCH_STARTED);
        }else{

            $match->player2 = $player->id;
            $match->save();

            $player->match_id = $match->id;
            $player->save();

            $this->sendPushNotification($opponent->cloud_id, self::NOTIF_MATCH_STARTS, self::PLAYER_JOINED, array('match' => $match->asArray()));

            $this->printJsonResponse(
                array(
                    'match'    => $match->asArray(),
                    'opponent' => $opponent->asArray(),
                    'score'    => $this->getScore($player, $match),
                )
            );
        }
    }

    /**
     * @Route('/api/v1/quit-match')
     * @Name('api.v1.quit-match')
     * @Method('PUT')
     */
    public function playerQuitMatchAction()
    {
        $this->checkApiKey();

        $player = $this->getUserAndCheckPassword();
        $matchId = $player->match_id;
        if(!$matchId){
            $this->printJsonError(self::ERROR_PLAYER_NOT_PLAYING);
        }
        /** @var \Entity\Match $match */
        $match = \Entity\Match::factory()->find_one($matchId);
        /** @var \Entity\Player $opponent */
        if($player->id == $match->player1){
            $opponent = \Entity\Player::factory()->find_one($match->player2);
        }else{
            $opponent = \Entity\Player::factory()->find_one($match->player1);
        }

        switch(true){

            case (!$player):
                $this->printJsonError(self::ERROR_PLAYER_DOESNT_EXISTS);
                break;

            case (!$matchId):
                $this->printJsonError(self::ERROR_PLAYER_NOT_PLAYING);
                break;

            case (!$match):
                $this->printJsonError(self::ERROR_MATCH_DOESNT_EXISTS);
                break;

            case (!$match->started()):
                $this->printJsonError(self::ERROR_MATCH_NOT_STARTED);
                break;

        }
        $score = $this->getScore($player, $match);
        $this->sendPushNotification($opponent->cloud_id, self::NOTIF_OPPONENT_QUITS, self::OPPONENT_QUITS, array('score'=>$score));
        $match->finished_at = date("Y-m-d h:i:s");
        $match->save();
        $player->match_id = 0;
        $player->save();
        $this->printJsonResponse(
            array(
                'match' => $match->asArray(),
                'score' => $score,
            )
        );
    }

    /**
     * @Route('/api/v1/cancel-match')
     * @Name('api.v1.cancel-match')
     * @Method('DELETE')
     */
    public function playerCancelMatchAction()
    {
        $this->checkApiKey();

        $player = $this->getUserAndCheckPassword();
        if(!$player){
            $this->printJsonError(self::ERROR_PLAYER_DOESNT_EXISTS);
        }

        $matchId = $player->match_id;
        if(!$matchId){
            $this->printJsonError(self::ERROR_PLAYER_NOT_PLAYING);
        }
        /** @var \Entity\Match $match */
        $match = \Entity\Match::factory()->find_one($matchId);
        if(!$match){
            $this->printJsonError(self::ERROR_MATCH_DOESNT_EXISTS);
        }
        if($match->started()){
            $this->printJsonError(self::ERROR_MATCH_STARTED);
        }

        $match->delete();
        $player->match_id = 0;
        $player->save();
        $this->printJsonResponse();
    }

    /**
     * @Route('/api/v1/match-info')
     * @Name('api.v1.match-info')
     * @Method('GET')
     */
    public function matchInfoAction()
    {
        $this->checkApiKey();

        $player = $this->getUserAndCheckPassword();
        if(!$player->match_id){
            $this->printJsonError(self::ERROR_PLAYER_NOT_PLAYING);
        }

        /** @var \Entity\Match $match */
        $match = \Entity\Match::factory()->find_one($player->match_id);

        if(!$match){
            $this->printJsonError(self::ERROR_MATCH_DOESNT_EXISTS);
        }elseif(!$match->started()){
            $this->printJsonError(self::ERROR_MATCH_NOT_STARTED);
        }

        $score = array();
        /** @var \Entity\Player $opponent */
        if($player->id == $match->player1){
            $opponent = \Entity\Player::factory()->find_one($match->player2);
            $score['you'] = $match->score1 ?: 0;
            $score['other'] = $match->score2 ?: 0;
        }else{
            $opponent = \Entity\Player::factory()->find_one($match->player1);
            $score['you'] = $match->score2 ?: 0;
            $score['other'] = $match->score2 ?: 0;
        }
        $this->printJsonResponse(array(
                'match'    => $match->asArray(),
                'opponent' => $opponent->asArray(),
                'score'    => $score,
            )
        );
    }



    /**
     * @Route('/api/v1/claim-point')
     * @Name('api.v1.claim-point')
     * @Method('PUT')
     */
    public function playerClaimPointAction()
    {
        $this->checkApiKey();

        $player = $this->getUserAndCheckPassword();
        $matchId = $player->match_id;
        if(!$matchId){
            $this->printJsonError(self::ERROR_PLAYER_NOT_PLAYING);
        }
        /** @var \Entity\Match $match */
        $match = \Entity\Match::factory()->find_one($matchId);
        /** @var \Entity\Player $opponent */
        if($player->id == $match->player1){
            $opponent = \Entity\Player::factory()->find_one($match->player2);
        }else{
            $opponent = \Entity\Player::factory()->find_one($match->player1);
        }

        switch(true){
            
            case (!$player):
                $this->printJsonError(self::ERROR_PLAYER_DOESNT_EXISTS);
                break;
            
            case (!$matchId):
                $this->printJsonError(self::ERROR_PLAYER_NOT_PLAYING);
                break;

            case (!$match):
                $this->printJsonError(self::ERROR_MATCH_DOESNT_EXISTS);
                break;

            case (!$match->started()):
                $this->printJsonError(self::ERROR_MATCH_NOT_STARTED);
                break;
            
            case ($player->id == $match->player1):
                $match->score1++;
                $score = $this->getScore($player, $match);
                if($match->to_points > $match->score1){
                    $this->sendPushNotification($opponent->cloud_id, self::NOTIF_SCORE_UPDATE, self::OPPONENT_SCORES, array('score'=>$score));
                    $youWin = false;
                }else{                    
                    $this->sendPushNotification($opponent->cloud_id, self::NOTIF_OTHER_WINS, self::OPPONENT_WINS, array('score'=>$score));
                    $match->finished_at = date("Y-m-d h:i:s");
                    $player->match_id = 0;
                    $player->save();
                    $youWin = true;
                }
                $match->save();
                $this->printJsonResponse(
                    array(
                        'match'  => $match->asArray(),
                        'score'  => $score,
                        'youWin' => $youWin,
                    )
                );
                break;
            
            case ($player->id == $match->player2):
                $match->score2++;
                $score = $this->getScore($player, $match);
                if($match->to_points > $match->score2){
                    $this->sendPushNotification($opponent->cloud_id, self::NOTIF_SCORE_UPDATE, self::OPPONENT_SCORES, array('score'=>$score));
                    $youWin = false;
                }else{
                    $this->sendPushNotification($opponent->cloud_id, self::NOTIF_OTHER_WINS, self::OPPONENT_WINS, array('score'=>$score));
                    $match->finished_at = date("Y-m-d h:i:s");
                    $player->match_id = 0;
                    $player->save();
                    $youWin = true;
                }
                $match->save();
                $this->printJsonResponse(
                    array(
                        'match'  => $match->asArray(),
                        'score'  => $score,
                        'youWin' => $youWin,
                    )
                );
                break;
            
            default:
                $this->printJsonError(self::ERROR_PLAYER_DOESNT_EXISTS);
                break;
                
        }
    }


}



