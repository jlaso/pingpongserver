<?php

namespace Entity;

use app\models\core\BaseModel;

/**
 * Class that handles notifications on this app
 */
class Notification extends BaseModel
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
  `type` int(1) NOT NULL,
  `message` char(100) DEFAULT NULL,
  `read` int(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE={$options['engine']} AUTO_INCREMENT=1 DEFAULT CHARSET={$options['charset']};

EOD;

    }

    public function asArray()
    {
        return array(
            'id'    => $this->id,
            'nick'  => $this->nick,
            'type' => $this->type,
            'message' => $this->message,
            'read' => (bool) $this->read,
        );
    }

}
