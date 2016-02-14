<?php
require_once(dirname(dirname(__FILE__))."/Common/function.php");
require_once(dirname(dirname(__FILE__))."/Conf/config.php");
require_once(dirname(dirname(__FILE__))."/Model/mysql2.php");
class CommonAction {
  private $_db_config;
  public $db;
  
  public function __construct() {
    $this->_db_config = $GLOBALS['db_config'];
    $this->connectDb();
  }
  
  public function connectDb() {
    $this->db = new MysqlDB(
      $this->_db_config['DB_HOST'],
      $this->_db_config['DB_USER'],
      $this->_db_config['DB_PWD'],
      $this->_db_config['DB_NAME'],
      $this->_db_config['DB_CHARSET'],
      $this->_db_config['DB_PORT']
    );
  }
}
