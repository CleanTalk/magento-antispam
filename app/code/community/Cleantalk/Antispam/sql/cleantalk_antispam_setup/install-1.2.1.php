<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS `{$this->getTable('cleantalk_server')}`;
CREATE TABLE `{$this->getTable('cleantalk_server')}` (
  `server_id` int(11) NOT NULL default 1,
  `work_url` varchar(255),
  `server_url` varchar(255),
  `server_ttl` int(11),
  `server_changed` int(11),
  PRIMARY KEY (`server_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
");

$installer->run("
DROP TABLE IF EXISTS `{$this->getTable('cleantalk_timelabels')}`;
CREATE TABLE `{$this->getTable('cleantalk_timelabels')}` (
  `ct_key` varchar(255) NOT NULL default 'mail_error',
  `ct_value` int(11),
  PRIMARY KEY (`ct_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
");

$installer->endSetup();
