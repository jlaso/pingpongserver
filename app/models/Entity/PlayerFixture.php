<?php


namespace Entity;

use \app\models\core\FixturableInterface;
use Entity\Staticpage;
use \app\models\core\Registry;
use Validate;


class PlayerFixture implements FixturableInterface
{
    /**
     * Creates a new item from $assocArray and inserts into DB
     *
     * @param array $assocArray
     *
     * @return $this
     */
    public function addNewItem($assocArray)
    {
        $item = \Entity\Player::factory()->create();
        foreach ($assocArray as $field=>$value) {
            $item->set($field,$value);
        }
        $item->save();

        return $this;
    }

    /**
     * Generate fixtures
     *
     * @param \app\models\core\Registry $fixturesRegistry
     *
     * @return void
     */
    public function generateFixtures(Registry $fixturesRegistry)
    {
        $this->addNewItem(
                array(
                    'nick'      => 'player1',
                    'email'     => 'player1@pingpongserver.ahiroo.com',
                    'password'  =>  sha1('1234'),
                )
            )->addNewItem(
                array(
                    'nick'      => 'player2',
                    'email'     => 'player2@pingpongserver.ahiroo.com',
                    'password'  =>  sha1('5678'),
                )
            )->addNewItem(
                array(
                    'nick'      => 'player3',
                    'email'     => 'player3@pingpongserver.ahiroo.com',
                    'password'  =>  sha1('9012'),
                )
            )
        ;
    }

    /**
     * Get the order of fixture generation
     *
     * @return int
     */
    public static function getOrder()
    {
        return 1;
    }

}