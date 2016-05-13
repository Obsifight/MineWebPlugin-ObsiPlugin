<?php
class SanctionsComponent extends Object {

  private $db;
  private $UUIDList;
  private $PseudoList;

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
        $this->UUIDList[$find[0]['BAT_players']['UUID']] = $pseudo;
        return $find[0]['BAT_players']['UUID'];
      }

    }

  /*
    Récupèrer le pseudo depuis l'UUID
  */

    public function getPseudoFromUUID($UUID = null) {

      if(isset($this->PseudoList[$UUID])) {
        return $this->PseudoList[$UUID];
      }

      $find = $this->__getDB()->fetchAll('SELECT BAT_player FROM BAT_players WHERE UUID=? LIMIT 0,1', array($UUID));

      if(!empty($find) && isset($find[0]['BAT_players']['BAT_player'])) {
        $this->PseudoList[$UUID] = $find[0]['BAT_players']['BAT_player'];
        $this->PseudoList[$find[0]['BAT_players']['BAT_player']] = $UUID;
        return $find[0]['BAT_players']['BAT_player'];
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

  public function getAllSanctions($UUID) {
    // Les valeurs de retour
    $userBans = array('active' => false);
    $userMutes = array('active' => false);
    $userKicks = array();

    // On cherche
    $findBans = $this->__getDB()->fetchAll('SELECT * FROM BAT_ban WHERE UUID=? ORDER BY ban_id DESC', array($UUID));
    $findMutes = $this->__getDB()->fetchAll('SELECT * FROM BAT_mute WHERE UUID=? ORDER BY mute_id DESC', array($UUID));
    $findKick = $this->__getDB()->fetchAll('SELECT * FROM BAT_kick WHERE UUID=? ORDER BY kick_id DESC', array($UUID));

    // On parcours pour trier
    if(is_array($findBans)) {
      foreach ($findBans as $key => $value) {
        $userBans['list'][] = $value['BAT_ban'];
        if($value['BAT_ban']['ban_state']) {
          $userBans['active'] = true;
        }
      }
      $userBans['count'] = count($userBans['list']);
      unset($key);
      unset($value);
    }

    if(is_array($findMutes)) {
      foreach ($findMutes as $key => $value) {
        $userMutes['list'][] = $value['BAT_mute'];
        if($value['BAT_mute']['mute_state']) {
          $userMutes['active'] = true;
        }
      }
      $userMutes['count'] = count($userMutes['list']);
      unset($key);
      unset($value);
    }

    if(is_array($findKick)) {
      foreach ($findKick as $key => $value) {
        $userKicks['list'][] = $value['BAT_kick'];
      }
      $userKicks['count'] = count($userKicks['list']);
      unset($key);
      unset($value);
    }

    // On retourne
    return array('bans' => $userBans, 'mutes' => $userMutes, 'kicks' => $userKicks);
  }

  /*
    Récupérer toutes les sanctions d'un membre du staff par son pseudo
  */

  public function getAllSanctionsBy($pseudo = null, $UUID = null) {

    if(!empty($UUID)) {
      $pseudo = $this->getPseudoFromUUID($UUID);
    }

    // Les valeurs de retour
    $userBans = array();
    $userMutes = array();
    $userKicks = array();

    // On cherche
    $findBans = $this->__getDB()->fetchAll('SELECT * FROM BAT_ban WHERE ban_staff=? ORDER BY ban_id DESC', array($pseudo));
    $findMutes = $this->__getDB()->fetchAll('SELECT * FROM BAT_mute WHERE mute_staff=? ORDER BY mute_id DESC', array($pseudo));
    $findKick = $this->__getDB()->fetchAll('SELECT * FROM BAT_kick WHERE kick_staff=? ORDER BY kick_id DESC', array($pseudo));

    // On parcours pour trier
    if(is_array($findBans)) {
      foreach ($findBans as $key => $value) {
        $userBans[] = $value['BAT_ban'];
      }
      unset($key);
      unset($value);
    }

    if(is_array($findMutes)) {
      foreach ($findMutes as $key => $value) {
        $userMutes[] = $value['BAT_mute'];
      }
      unset($key);
      unset($value);
    }

    if(is_array($userKicks)) {
      foreach ($userKicks as $key => $value) {
        $userKicks[] = $value['BAT_kick'];
      }
      unset($key);
      unset($value);
    }

    // On retourne
    return array('bans' => $userBans, 'mutes' => $userMutes, 'kicks' => $userKicks);
  }


  /*
    Savoir si il a déjà était banni
  */

  public function wasBanned($UUID) {
    $find = $this->__getDB()->fetchAll('SELECT ban_id FROM BAT_ban WHERE UUID=? LIMIT 0,1', array($UUID));
    return (!empty($find));
  }

  /*
    Savoir si il a déjà était mute
  */

  public function wasMuted($UUID) {
    $find = $this->__getDB()->fetchAll('SELECT mute_id FROM BAT_mute WHERE UUID=? LIMIT 0,1', array($UUID));
    return (!empty($find));
  }

}
