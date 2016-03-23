<?php

class StatsController extends ObsiAppController {

  public $components = array('Obsi.GoogleAnalytics');

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
      $db = $con->getDataSource('Auth');

      $registeredUsersOnV5 = $db->fetchAll('SELECT COUNT(user_id) FROM joueurs WHERE is_register_v5=1')[0][0]['COUNT(user_id)'];
      $connectedUsersOnV5 = $db->fetchAll('SELECT COUNT(user_id) FROM joueurs WHERE has_connected_v5=1')[0][0]['COUNT(user_id)'];
      $registeredUsersThisWeek = $this->User->find('count', array(
        'conditions' => array(
          'OR' => array(
            'created >=' => date('Y-m-d 00:00:00', strtotime('-1 week')),
            'created <=' => date('Y-m-d 23:59:59'),
          )
        )
      ));
      $totalUsers = $this->User->find('count');

      $percentageRegisteredUsersOnV5 = round(($totalUsers * ( $registeredUsersOnV5 / 100 )), 2);
      $percentageConnectedUsersOnV5 = round(($totalUsers * ( $connectedUsersOnV5 / 100 )), 2);
      $percentageRegisteredUsersThisWeek = round(($totalUsers * ( $registeredUsersThisWeek / 100 )), 2);


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

  public function user($user) {}

  public function faction($faction) {}

}
