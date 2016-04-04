<?php
class SanctionsComponent extends Object {

  private $db;
  private $UUIDList;

  function shutdown(&$controller) {}
  function beforeRender(&$controller) {}
  function beforeRedirect() {}
  function initialize(&$controller) {}
  function startup(&$controller) {}

  private function __getDB() {
    if(empty($this->db)) {
      // On se connecte à la DB
      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      ConnectionManager::create('Bans', Configure::read('Obsi.db.Bans'));
      $this->db = $con->getDataSource('Bans');
    }
    return $this->db;
  }

  /*
    Récupèrer l'UUID pour faire toutes les autres actions
  */

    public function getUUID($pseudo = null) {

      if(isset($this->UUIDList[$pseudo])) {
        return $this->UUIDList[$pseudo];
      }

      $find = $this->__getDB()->fetchAll('SELECT UUID FROM BAT_players WHERE BAT_player=? LIMIT 0,1', array($pseudo));

      if(!empty($find) && isset($find[0]['BAT_players']['UUID'])) {
        $this->UUIDList[$pseudo] = $find[0]['BAT_players']['UUID'];
        return $find[0]['BAT_players']['UUID'];
      }

    }

  /*
    Savoir si le joueur est banni actuellement
  */

  public function isBanned($UUID = null) {
    $find = $this->__getDB()->fetchAll('SELECT ban_id FROM BAT_ban WHERE UUID=? AND ban_state=1 LIMIT 0,1', array($UUID));
    return (!empty($find));
  }

  /*
    Savoir si le joueur est mute actuellement
  */

  public function isMuted($UUID) {
    $find = $this->__getDB()->fetchAll('SELECT mute_id FROM BAT_mute WHERE UUID=? AND mute_state=1 LIMIT 0,1', array($UUID));
    return (!empty($find));
  }

  /*
    Récupérer toutes les fois ou il a été ban/kick/mute
  */

  public function getAllSanctions($UUID) {}

  /*
    Savoir si il a déjà était banni
  */

  public function wasBanned($UUID) {}

  /*
    Savoir si il a déjà était mute
  */

  public function wasMuted($UUID) {}

}
