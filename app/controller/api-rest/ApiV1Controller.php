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

        $toPoints = $this->slimInstance->request()->get('toPoints') ?: 21;

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
        $match->save();

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

        $criteria = $this->slimInstance->request()->get('criteria');
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
            ->find_many();

        $playersFound = array();
        if(count($matches)){
            foreach($matches as $match){
                $result[] = $match->asArray();
                //$players[$match->player1] = $match->player1;
                $playersFound[$match->player1] = $players[$match->player1];
            }
        }

        $this->printJsonResponse(
            array(
                'matches' => $result,
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

        if(!$match){
            $this->printJsonError(self::ERROR_MATCH_DOESNT_EXISTS);
        }elseif($match->started()){
            $this->printJsonError(self::ERROR_MATCH_STARTED);
        }else{

            $match->player2 = $player->id;
            $match->save();

            $this->sendPushNotification($match->player1, self::PLAYER_JOINED);

            $this->printJsonResponse(array('match'=>$match->id));
        }
    }

    /**
     * @Route('/api/v1/claim-point/:matchId')
     * @Name('api.v1.claim-point')
     * @Method('PUT')
     */
    public function playerClaimPointAction($matchId)
    {
        $this->checkApiKey();

        $match = \Entity\Match::factory()->find_one($matchId);
        $player = $this->getUserAndCheckPassword();

        switch(true){
            
            case (!$player):
                $this->printJsonError(self::ERROR_PLAYER_DOESNT_EXISTS);
                break;
            
            case (!$match):
                $this->printJsonError(self::ERROR_MATCH_DOESNT_EXISTS);
                break;

            case (!$match->started()):
                $this->printJsonError(self::ERROR_MATCH_NOT_STARTED);
                break;
            
            case ($player->id == $match->player1):

                $match->score1++;
                if($match->to_points > $match->score1){
                    $this->sendPushNotification($match->player1, self::OPPONENT_SCORES, json_encode($match->score()));
                }else{                    
                    $this->sendPushNotification($match->player1, self::OPPONENT_WINS, json_encode($match->score()));
                    $match->finished_at = date("Y-m-d h:i:s");
                }
                $this->printJsonResponse(array('match'=>$match->asArray()));
                $match->save();
                break;
            
            case ($player->id == $match->player2):

                $match->score2++;
                if($match->to_points > $match->score2){
                    $this->sendPushNotification($match->player2, self::OPPONENT_SCORES, json_encode($match->score()));
                }else{
                    $this->sendPushNotification($match->player2, self::OPPONENT_WINS, json_encode($match->score()));
                    $match->finished_at = date("Y-m-d h:i:s");
                }
                $this->printJsonResponse(array('match'=>$match->asArray()));
                $match->save();
                break;
            
            default:
                $this->printJsonError(self::ERROR_PLAYER_DOESNT_EXISTS);
                break;
                
        }
    }


}



