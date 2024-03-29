<?php
/**
 *
 * @package    PACMEC
 * @category   SysSystem
 * @copyright  2020-2021 FelipheGomez
 * @author     FelipheGomez <feliphegomez@pm.me>
 * @license    license.txt
 * @version    1.0.1
 */

namespace PACMEC\System;

class SysSession implements \SessionHandlerInterface {
  const IPS_BANNED = [
    '34.67.120.179',
    '35.161.55.221',
    '35.188.52.86',
    '35.188.80.222',
    '35.194.17.187',
    '35.222.83.208',
    '35.239.126.209',
    '45.41.177.175',
    '51.15.191.81',
    '66.249.64.83',
    '66.249.64.112',
    '66.249.64.114',
    '66.249.64.116',
    '66.249.64.214',
    '66.249.64.218',
    '66.246.224.11',
  ];
  private $link;

  public function open($savePath, $sessionName) {
    if(isset($GLOBALS['PACMEC']['DB'])){
      $this->link = $GLOBALS['PACMEC']['DB'];
      return true;
    } else { return false; }
  }

  public function close() {
    $this->link = NULL;
    return true;
  }

  public function read($id) {
  	try {
      if(in_array($GLOBALS['PACMEC']['ip'], Self::IPS_BANNED)) return "";
  		$result = $this->link->FetchObject("SELECT `session_data` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('sessions')}` WHERE `session_id`=? AND `session_expires`>=? AND `host`=?", [
        $id, date('Y-m-d H:i:s'),
        $GLOBALS['PACMEC']['host']
      ]);
  		if($result !== false && isset($result->session_data)){ return $result->session_data; } else { return ""; }
  	}
  	catch(Exception $e){
  		echo $e->getMessage();
  		return "";
  	}
  }

  public function write($id, $data) {
  	try {
      if(in_array($GLOBALS['PACMEC']['ip'], Self::IPS_BANNED)) return false;
  		$DateTime = date('Y-m-d H:i:s');
  		$NewDateTime = date('Y-m-d H:i:s',strtotime($DateTime.' + 1 hour'));
  		$result = $this->link->FetchObject("REPLACE INTO `{$GLOBALS['PACMEC']['DB']->getTableName('sessions')}` SET `session_id`=?, `session_expires`=?, `session_data`=?, `ip`=?, `host`=?", [
        $id,
        $NewDateTime,
        $data,
        $GLOBALS['PACMEC']['ip'],
        $GLOBALS['PACMEC']['host']
      ]);
  		if($result !== false){ return true; } else { return false; }
  	}
  	catch(Exception $e){
  		echo $e->getMessage();
  		return false;
  	}
  }

  public function destroy($id) {
  	try {
  		$result = $this->link->FetchObject("DELETE FROM `{$GLOBALS['PACMEC']['DB']->getTableName('sessions')}` WHERE `session_id`=?", [$id]);
  		if($result !== false){ return true; } else { return false; }
  	}
  	catch(Exception $e){
  		echo $e->getMessage();
  		return false;
  	}
  }

  public function gc($maxlifetime) {
  	try {
  		$result = $this->link->FetchObject("DELETE FROM `{$GLOBALS['PACMEC']['DB']->getTableName('sessions')}` WHERE ((UNIX_TIMESTAMP(session_id)+?)<?)", [$maxlifetime, $maxlifetime]);
  		if($result !== false){ return true; } else { return false; }
  	}
  	catch(Exception $e){
  		echo $e->getMessage();
  		return false;
  	}
  }
}
