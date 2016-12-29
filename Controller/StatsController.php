<?php

class StatsController extends ObsiAppController {

  public $components = array('Obsi.GoogleAnalytics');

  private function timeAgo($ptime) {
    $etime = time() - $ptime;

    if ($etime < 1) {
      return 'quelques secondes';
    }

    $a = array( 365 * 24 * 60 * 60  =>  'an',
                 30 * 24 * 60 * 60  =>  'mois',
                      24 * 60 * 60  =>  'jour',
                           60 * 60  =>  'heure',
                                60  =>  'minute',
                                 1  =>  'seconde'
                );
    $a_plural = array( 'an'   => 'ans',
                       'mois'  => 'mois',
                       'jour'    => 'jours',
                       'heure'   => 'heures',
                       'minute' => 'minutes',
                       'seconde' => 'secondes'
                );

    foreach ($a as $secs => $str)
    {
        $d = $etime / $secs;
        if ($d >= 1)
        {
            $r = round($d);
            return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str);
        }
    }
  }

  public function index() {

    $this->set('title_for_layout', 'Statistiques');

    /*
      Staff
    */
    $staff = Configure::read('ObsiPlugin.staff');
    $server_id = Configure::read('ObsiPlugin.server.pvp.id');

    $usersOnlines = array();
    $cache = Cache::read('usersOnlines', 'short');
    if (!$cache) {
      $serverpvp = $this->Server->call('getPlayerList', false, $server_id);
      if(isset($serverpvp['getPlayerList'])) {
        $usersOnlines = explode(', ', $serverpvp['getPlayerList']);
        Cache::write('usersOnlines', $usersOnlines, 'short');
      }
    } else {
      $usersOnlines = Cache::read('usersOnlines', 'short');
    }

  /*
    Stats globals
  */

    $cache = Cache::read('registered_count', 'data-short');
    if (!$cache) {
      $usersRegistered = $this->User->find('count');
      Cache::write('registered_count', $usersRegistered, 'data-short');
    } else {
      $usersRegistered = $cache;
    }

  /*
    Max players
  */
    $cache = Cache::read('maxPlayers', 'data-short');
    if (!$cache) {
      $query = @json_decode(@file_get_contents('http://players.api.obsifight.net/max'));
      if ($query) {
        $maxPlayers = $query->max;
        Cache::write('maxPlayers', $maxPlayers, 'data-short');
      } else {
        $maxPlayers = 0;
      }
    } else {
      $maxPlayers = $cache;
    }
    $this->set('maxPlayers', $maxPlayers);

  /*
    Connectés
  */

  $cache = Cache::read('stats-onlinePlayers', 'data-short');
  if (!$cache) {
    $url = 'http://players.api.obsifight.net/data?superiorDate='.date('Y-m-d%20H:i:s', strtotime('-8 days'));
    $findOnlinePlayers = @json_decode(@file_get_contents($url), true);
    $onlinePlayers = array();
    if ($findOnlinePlayers && !empty($findOnlinePlayers)) {
      foreach ($findOnlinePlayers as $key => $value) {

        $onlinePlayers[] = array(
          (intval($value['time'])),
          intval($value['count'])
        );

      }
    }
    $onlinePlayers = json_encode($onlinePlayers);
    Cache::write('stats-onlinePlayers', $onlinePlayers, 'data-short');
  } else {
    $onlinePlayers = Cache::read('stats-onlinePlayers', 'data-short');
  }

  $this->loadModel('Obsi.CountPlayersLog');
  $cache = Cache::read('stats-peakTimes', 'data-short');
  if (!$cache) {
    $peakTimes = array(
      'hours' => @json_decode(@file_get_contents('http://players.api.obsifight.net/stats/peak-times/hours'), true),
      'days' => @json_decode(@file_get_contents('http://players.api.obsifight.net/stats/peak-times/days'), true)
    );
    Cache::write('stats-peakTimes', $peakTimes, 'data-short');
  } else {
    $peakTimes = Cache::read('stats-peakTimes', 'data-short');
  }

  /*
    Users
  */

  $cache = Cache::read('stats-users', 'data-short');
  if (!$cache) {
    $registersUsers['today'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d'))));
    $registersUsers['yesterday'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-1 days')))));
    $registersUsers['before_yesterday'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-2 days')))));
    $registersUsers['three_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-3 days')))));
    $registersUsers['four_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-4 days')))));
    $registersUsers['five_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-5 days')))));
    $registersUsers['six_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-6 days')))));
    $registersUsers['seven_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-7 days')))));

    App::uses('ConnectionManager', 'Model');
    $con = new ConnectionManager;
    ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
    $db = $con->getDataSource('Auth');

    $registeredUsersOnV5 = $db->fetchAll('SELECT COUNT(user_id) FROM joueurs WHERE is_register_v5=1')[0][0]['COUNT(user_id)'];
    $connectedUsersOnV5 = $db->fetchAll('SELECT COUNT(user_id) FROM joueurs WHERE has_connected_v5=1')[0][0]['COUNT(user_id)'];
    $registeredUsersThisWeek = $this->User->find('count', array(
      'conditions' => array(
        'AND' => array(
          'created >=' => date('Y-m-d 00:00:00', strtotime('monday this week')),
          'created <=' => date('Y-m-d 23:59:59', strtotime('sunday this week')),
        )
      )
    ));

    Cache::write('stats-users', array($registersUsers, $registeredUsersOnV5, $connectedUsersOnV5, $registeredUsersThisWeek), 'data-short');
  } else {
    list($registersUsers, $registeredUsersOnV5, $connectedUsersOnV5, $registeredUsersThisWeek) = Cache::read('stats-users', 'data-short');
  }

  $totalUsers = $usersRegistered;

  $percentageRegisteredUsersOnV5 = round(( $registeredUsersOnV5 * 100 / $totalUsers ), 2, PHP_ROUND_HALF_UP);
  $percentageConnectedUsersOnV5 = round(( $connectedUsersOnV5 * 100 / $totalUsers ), 2, PHP_ROUND_HALF_UP);
  $percentageRegisteredUsersThisWeek = round(( $registeredUsersThisWeek * 100 / $totalUsers ), 2, PHP_ROUND_HALF_UP);


  /*
    Set vars to view
  */

    $this->set('staff', $staff);
    $this->set(compact(
      'usersOnlines',
      'usersRegistered',
      'playersMaxAmount',
      'percentageRegisteredUsersOnV5',
      'percentageConnectedUsersOnV5',
      'percentageRegisteredUsersThisWeek',
      'registersUsers',
      'onlinePlayers',
      'peakTimes'
    ));
  }

  public function getVisits() {
    $this->autoRender = false;
    $this->response->type('json');

    $cache = Cache::read('visits', 'stats');
    if($cache === false) {

      $data = array(
        'visits_all' => 0,
        'visits_today' => 0,
        'visits_yesterday' => 0,
        'visits_before_yesterday' => 0,
        'visits_three_days_before' => 0,
        'visits_four_days_before' => 0,
        'visits_five_days_before' => 0,
        'visits_six_days_before' => 0,
        'visits_seven_days_before' => 0
      );

      $data['visits_all'] = $this->GoogleAnalytics->getVisitsFromTo('2015-10-05', 'today');
      $data['visits_today'] = $this->GoogleAnalytics->getVisitsOf('today');
      $data['visits_yesterday'] = $this->GoogleAnalytics->getVisitsOf('yesterday');
      $data['visits_before_yesterday'] = $this->GoogleAnalytics->getVisitsOf(date('Y-m-d', strtotime('-2 days')));
      $data['visits_three_days_before'] = $this->GoogleAnalytics->getVisitsOf(date('Y-m-d', strtotime('-3 days')));
      $data['visits_four_days_before'] = $this->GoogleAnalytics->getVisitsOf(date('Y-m-d', strtotime('-4 days')));
      $data['visits_five_days_before'] = $this->GoogleAnalytics->getVisitsOf(date('Y-m-d', strtotime('-5 days')));
      $data['visits_six_days_before'] = $this->GoogleAnalytics->getVisitsOf(date('Y-m-d', strtotime('-6 days')));
      $data['visits_seven_days_before'] = $this->GoogleAnalytics->getVisitsOf(date('Y-m-d', strtotime('-7 days')));

      Cache::write('visits', $data, 'stats');

    } else {
      $data = $cache;
    }

    echo json_encode($data);
  }

  public function search_user($username = null) {
    $this->autoRender = false;
    $this->response->type('json');

    $result = array();

    $find = $this->User->find('all', array('conditions' => array('pseudo LIKE' => $username.'%'), 'limit' => 5));
    if(!empty($find)) {
      foreach ($find as $key => $value) {
        $result[] = $value['User']['pseudo'];
      }
    }


    echo json_encode($result);
  }

  public function user($user) {

    $findUser = $this->User->find('first', array('conditions' => array('pseudo' => $user)));
    if(!empty($findUser)) { //On cherche l'utilisateur

      $this->set('title_for_layout', $findUser['User']['pseudo']);
      $this->set('header', false);


      /*
          On se connecte à LogBlock et on cherche le joueur
      */

      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      ConnectionManager::create('LogBlock', Configure::read('Obsi.db.LogBlock'));
      $db = $con->getDataSource('LogBlock');

      $findUserLogs = $db->fetchAll('SELECT * FROM `lb-players` WHERE playername=:user', array('user' => $user));
      if(!empty($findUserLogs)) {

        $findUserLogs = $findUserLogs[0]['lb-players'];

        /*
            Stats LogBlock
        */


          // Temps de connexion & last connexion
            $lastLogin = 'Il y a '.$this->timeAgo(strtotime($findUserLogs['lastlogin']));

            $onlineTimeConverted = $this->Util->secondsToTime($findUserLogs['onlinetime']);
            $onlineTime = array();
            if($onlineTimeConverted['d'] > 0) {
              $onlineTime[] = $onlineTimeConverted['d'].' '.$this->Lang->get('GLOBAL__DATE_R_DAYS');
            }
            if($onlineTimeConverted['h'] > 0) {
              $onlineTime[] = $onlineTimeConverted['h'].' '.$this->Lang->get('GLOBAL__DATE_R_HOURS');
            }
            if($onlineTimeConverted['m'] > 0) {
              $onlineTime[] = $onlineTimeConverted['m'].' '.$this->Lang->get('GLOBAL__DATE_R_MINUTES');
            }
            $onlineTime = implode(', ', $onlineTime);

          // Stats sur les blocs
            $logblock_player_id = $findUserLogs['playerid'];

            $findUserLogsBlocksDestroyedWorld = $db->fetchAll('SELECT COUNT(*) FROM `lb-FACTION` WHERE playerid=:user AND type=0', array('user' => $logblock_player_id));
            if(!empty($findUserLogsBlocksDestroyedWorld)) {
              $userBlocksDestroyed = $findUserLogsBlocksDestroyedWorld[0][0]['COUNT(*)'];
            }

            $findUserLogsBlocksPlacedWorld = $db->fetchAll('SELECT COUNT(*) FROM `lb-FACTION` WHERE playerid=:user AND replaced=0', array('user' => $logblock_player_id));
            if(!empty($findUserLogsBlocksDestroyedWorld)) {
              $userBlocksPlaced = $findUserLogsBlocksPlacedWorld[0][0]['COUNT(*)'];
            }

      } else {
        $lastLogin = 'Ne s\'est jamais connecté';
        $onlineTime = '0 heures, 0 minutes';
      }

      /*
        On récupère ses données sur la base de données de l'auth
      */

      ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
      $db = $con->getDataSource('Auth');

      $authUser = $db->fetchAll('SELECT * FROM joueurs WHERE user_pseudo=:pseudo', array('pseudo' => $findUser['User']['pseudo']))[0]['joueurs'];
        $hasConnectedV5 = ($authUser['has_connected_v5']) ? true : false;
        $isRegisterV5 = ($authUser['is_register_v5']) ? true : false;


      /*
          Stats de kills ...
      */

        ConnectionManager::create('KillStats', Configure::read('Obsi.db.KillStats'));
        $db = $con->getDataSource('KillStats');

        $findUserKillsDeathsRatio = $db->fetchAll('SELECT * FROM killstats_data WHERE playerName=:pseudo', array('pseudo' => $findUser['User']['pseudo']));
        if(!empty($findUserKillsDeathsRatio)) {
          $findUserKillsDeathsRatio = $findUserKillsDeathsRatio[0]['killstats_data'];

          $userKills = $findUserKillsDeathsRatio['kills'];
          $userDeaths = $findUserKillsDeathsRatio['deaths'];
          $userRatio = $findUserKillsDeathsRatio['ratio'];
        }


      // Autres données
        $server_id = Configure::read('ObsiPlugin.server.pvp.id');
        $votes = $findUser['User']['vote'];
        $registerDate = date('d/m/Y', strtotime($findUser['User']['created']));

        $getPlayerFaction = $this->Server->call(array('getPlayerFaction' => $findUser['User']['pseudo']), true, $server_id); // On récupère la faction du joueur
        if(isset($getPlayerFaction['getPlayerFaction']) && !empty($getPlayerFaction['getPlayerFaction']) && $getPlayerFaction['getPlayerFaction'] != "PLAYER_NOT_CONNECTED") { // La requête a réussi & on a trouvé une faction & qu'il est connecté
          $playerFaction = $getPlayerFaction['getPlayerFaction'];
        } else {
          $playerFaction = 'Wilderness';
        }

        $staff = Configure::read('ObsiPlugin.staff');
        $staffPrefix = Configure::read('ObsiPlugin.staff-prefix');
        foreach ($staff as $rank => $users) {
          if(in_array($findUser['User']['pseudo'], $users)) { //si membre du staff
            $rankUser = $staffPrefix[$rank];
          }
        }
        if(!isset($rankUser)) {
          $rankUser = '<span class="label label-primary">Joueur</span>';
        }

        $searchIsOnline = $this->Server->call(array('isConnected' => $this->User->getKey('pseudo')), true, $server_id);
        if(isset($searchIsOnline['isConnected']) && $searchIsOnline['isConnected'] == "true") {
          $isOnline = true;
        } else {
          $isOnline = false;
        }




      $this->set(compact(
        'onlineTime',
        'lastLogin',
        'hasConnectedV5',
        'isRegisterV5',
        'registerDate',
        'votes',
        'findUser',
        'playerFaction',
        'rankUser',
        'userBlocksPlaced',
        'userBlocksDestroyed',
        'userKills',
        'userDeaths',
        'userRatio',
        'isOnline'
      ));

    } else {
      throw new NotFoundException();
    }

  }

  public function faction($faction) {}

}
