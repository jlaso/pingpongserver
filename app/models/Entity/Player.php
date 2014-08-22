<?php

namespace Entity;

use app\models\core\BaseModel;

/**
 * Class that stores players of this app
 */
class Player extends BaseModel
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
  `nick` varchar(100) DEFAULT NULL,
  `password` char(40) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `hash` char(40) DEFAULT NULL,
  `cloud_id` char(50) DEFAULT NULL,
  `match_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE={$options['engine']} AUTO_INCREMENT=1 DEFAULT CHARSET={$options['charset']};

EOD;

    }

    public function asArray()
    {
        return array(
            'id'    => $this->id,
            'nick'  => $this->nick,
            'email' => $this->email,
        );
    }

}
