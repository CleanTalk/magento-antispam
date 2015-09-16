<?php
#CREATE TABLE cleantalk_server (server_id int(11) not null default 1, work_url varchar(255), server_url varchar(255), server_ttl int(11), server_changed int(11), PRIMARY KEY (server_id))
class Cleantalk_Antispam_Model_Resource_Server extends Mage_Core_Model_Resource_Db_Abstract{
    protected function _construct()
    {
        $this->_init('antispam/server', 'server_id');
    }
}
