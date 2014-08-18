<?php

use JLaso\SlimRoutingManager\Controller\Controller;

class FrontendController extends Controller
{

    /**
     * @Route('/')
     * @Name('home.index')
     */
    public function indexAction()
    {
        $bestPlayers = array(
            'gold'   => array('nick' => 'jlaso', 'points' => 1010),
            'silver' => array('nick' => 'patrick', 'points' => 980),
            'bronze' => array('nick' => 'angel', 'points' => 410),
        );
        $this->slimInstance->render('frontend/home/index.html.twig', array(
                'section' => 'home.index',
                'player'  => $bestPlayers,
            )
        );
    }

    /**
     * @Route('/api-doc/:version')
     * @Name('home.api')
     */
    public function apiDocAction($version)
    {
        $this->slimInstance->render('frontend/home/api.html.twig', array(
                'version' => $version,
                'section' => 'home.api',
            ))
        ;
    }

    /**
     * @Route('/hall-of-fame')
     * @Name('home.hall_fame')
     */
    public function hallOfFameAction()
    {
        $players = \Entity\Player::factory()->find_many();

        $this->slimInstance->render('frontend/home/hall_fame.html.twig', array(
                'players' => $players,
                'section' => 'home.hall_fame',
            ))
        ;
    }



}



