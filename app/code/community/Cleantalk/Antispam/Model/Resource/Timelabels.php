<?php

#CREATE TABLE `cleantalk_timelabels` ( `ct_key` varchar(255) not null default 'mail_error', `ct_value` int(11), PRIMARY KEY (`ct_key`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;
class Cleantalk_Antispam_Model_Resource_Timelabels extends Mage_Core_Model_Resource_Db_Abstract{
    protected function _construct()
    {
        $this->_init('antispam/timelabels', 'ct_key');
    }
}
