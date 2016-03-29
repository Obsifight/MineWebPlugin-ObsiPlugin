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
      $serverpvp = $this->Server->call('getPlayerList', false, $server_id);
      if(isset($serverpvp['getPlayerList'])) {
        $usersOnlines = explode(', ', $serverpvp['getPlayerList']);
      } else {
        $usersOnlines = array();
      }

  /*
    Stats globals
  */
    $usersRegistered = $this->User->find('count');

  /*
    Connectés
  */

    $this->loadModel('Obsi.CountPlayersLog');
    $findOnlinePlayers = $this->CountPlayersLog->find('all', array('conditions' => array('time >= ' => strtotime('-7 days'))));
    $onlinePlayers = array();
    if(!empty($findOnlinePlayers)) {
      foreach ($findOnlinePlayers as $key => $value) {

        $onlinePlayers[] = array(
          (intval($value['CountPlayersLog']['time'])*1000),
          intval($value['CountPlayersLog']['players_online'])
        );

      }
    }

    $onlinePlayers = json_encode($onlinePlayers);

    $peakTimes = array();

    // On récupère les heures les plus fréquentées (max 5)
      $findPeakTimes['hours'] = $this->CountPlayersLog->query(
        'SELECT HOUR(created) AS `hour`, AVG(players_online) AS `average_players_online`
        FROM obsi__count_players_logs
        GROUP BY hour(created)
        ORDER BY AVG(players_online) DESC
        LIMIT 5');

    // On les parcours, pour récupérer la moyenne de joueurs
      foreach ($findPeakTimes['hours'] as $key => $value) {

        $value = $value[0];
        $peakTimes['hours'][$value['hour']] = round($value['average_players_online'], 0, PHP_ROUND_HALF_UP); // La moyenne de joueur pour cette heure là

      }

    // On récupère les heures les plus fréquentées (max 5)
      $findPeakTimes['days'] = $this->CountPlayersLog->query(
        'SELECT created, AVG(players_online) AS `average_players_online`
        FROM obsi__count_players_logs
        GROUP BY DAY(created)
        ORDER BY AVG(players_online) DESC
        LIMIT 3');

    // On les parcours, pour récupérer la moyenne de joueurs
      foreach ($findPeakTimes['days'] as $key => $value) {

        $day = date('w', strtotime($value['obsi__count_players_logs']['created']));
        switch ($day) { // On converti en français
          case 0:
            $day = 'Dimanche';
            break;
          case 1:
            $day = 'Lundi';
            break;
          case 2:
            $day = 'Mardi';
            break;
          case 3:
            $day = 'Mercredi';
            break;
          case 4:
            $day = 'Jeudi';
            break;
          case 5:
            $day = 'Vendredi';
            break;
          case 6:
            $day = 'Samedi';
            break;

          default:
            break;
        }

        $value = $value[0];
        $peakTimes['days'][$day] = round($value['average_players_online'], 0, PHP_ROUND_HALF_UP); // La moyenne de joueur pour cette heure là

      }

  /*
    Users
  */

    // Graph

      $registersUsers['today'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d'))));
      $registersUsers['yesterday'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-1 days')))));
      $registersUsers['before_yesterday'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-2 days')))));
      $registersUsers['three_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-3 days')))));
      $registersUsers['four_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-4 days')))));
      $registersUsers['five_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-5 days')))));
      $registersUsers['six_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-6 days')))));
      $registersUsers['seven_days_before'] = $this->User->find('count', array('conditions' => array('DATE(created)' => date('Y-m-d', strtotime('-7 days')))));


    // Pourcentages
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
      $totalUsers = $this->User->find('count');

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
        'userRatio'
      ));

    } else {
      throw new NotFoundException();
    }

  }

  public function faction($faction) {}

}
