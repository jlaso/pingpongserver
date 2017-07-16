<?php

namespace Entity;

use app\models\core\BaseModel;

/**
 * Class that stores players of this app
 */
class Match extends BaseModel
{
    /**
     * Get the SQL creation sentece of this table
     *
     * @param array $options
     * @return string
     */
    public static function _creationSchema(Array $options = array())
    {
        $class = self::_tableNameForClass(get_called_class());

        // default options
        $options = array_merge(self::_defaultCreateOptions(),$options);

        return

            <<<EOD

CREATE TABLE IF NOT EXISTS `{$class}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player1` int(11) DEFAULT NULL,
  `player2` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `score1` int(11) DEFAULT NULL,
  `score2` int(11) DEFAULT NULL,
  `to_points` int(11) DEFAULT 21,
  `longitude` decimal(10,8) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE={$options['engine']} AUTO_INCREMENT=1 DEFAULT CHARSET={$options['charset']};

EOD;

    }

    public function asArray()
    {
        return array(
            'id'          => $this->id,
            'player1'     => $this->player1 ?: 0,
            'player2'     => $this->player2 ?: 0,
            'created_at'  => $this->created_at ?: "",
            'finished_at' => $this->finished_at ?: "",
            'score1'      => $this->score1 ?: 0,
            'score2'      => $this->score2 ?: 0,
            'to_points'   => $this->to_points ? intval($this->to_points) : 0,
            'longitude'   => $this->longitude,
            'latitude'    => $this->latitude,
        );
    }

    public function score()
    {
        return array(
            $this->player1 => $this->score1,
            $this->player2 => $this->score2,
        );
    }

    public function started()
    {
        return ($this->player1 && $this->player2);
    }

}
