<?php
class RefreshFactionsRankingShell extends AppShell {

	public $uses = array('Obsi.FactionsRanking'); //Models
/*
  private $columnsToSave = array(
    'position',
    'name',
    'leader',
    'kills',
    'deaths',
    'ratio',
    'golds_pieces',
    'end_events',
    'kingzombie_events'
  );
*/
  public function main() {

    $this->out('<info>Init.</info>');

    /*
      On initialise les components et variables à utiliser
    */
      App::uses('ComponentCollection', 'Controller');
      App::uses('ServerComponent', 'Controller/Component');
      $Collection = new ComponentCollection();
      $Server = new ServerComponent($Collection);

      $server_id = Configure::read('ObsiPlugin.server.pvp.id');
      $grossData = array(); // Données non brutes non traitées
      $savedData = array(); // Données traitées à enregistrer

    /*
      On récupère les données (globales)
    */

    $this->out('<info>Get data...</info>');

      $factionsList = $Server->call(array('getAllFactions' => 'server'), true, $server_id);
      if(!isset($factionsList['getAllFactions'])) {
        $this->error('Récupération des données', 'L\'index "getAllFactions" n\'existe pas !');
      }
      $factionsList = $factionsList['getAllFactions'];
      $factionsList = explode(', ', $factionsList);
      if(!is_array($factionsList)) {
        $this->error('Récupération des données', 'L\'index "getAllFactions" n\'est pas un tableau !');
      }

      $totalFactions = count($factionsList);
    $this->out('<warning>'.$totalFactions.'</warning> founded!');

    /*
      On structure les données et on en récupère des nouvelles
    */

    $this->out('<info>Edit data...</info>');

      $i=0;
      foreach ($factionsList as $factionName) {
        $i++;

        $this->out($i.'/'.$totalFactions. ' - '.$factionName);

        /*
          On récupère le chef et les joueurs présents
        */
          $getLeaderAndPlayers = $Server->call(array('getFactionPlayers' => $factionName, 'getFactionLeader' => $factionName), true, $server_id);

          if(!isset($getLeaderAndPlayers['getFactionLeader'])) {
            continue;
          }
          $leaderName = $getLeaderAndPlayers['getFactionLeader'];

          if(!isset($getLeaderAndPlayers['getFactionPlayers'])) {
            continue;
          }
          $players = $getLeaderAndPlayers['getFactionPlayers'];
          $players = explode(', ', $players);

        /*
          On récupère les kills/deaths/ratio
        */

          $getKillsDeathsRatio = $this->__getKillsAndDeathsOf($players);
          $factionKills = $getKillsDeathsRatio['kills'];
          $factionDeaths = $getKillsDeathsRatio['deaths'];
          $factionRatio = $getKillsDeathsRatio['ratio'];

        /*
          On récupère les évents et les golds de la faction
        */

          $factionGoldsPieces = $this->__getGoldspiecesOf($players);
          $factionEndEvents = $this->__getEndEventsOf($players);
          $factionKingzombieEvents = $this->__getKingzombieEventsOf($players);

        /*
          On calcule les points
        */

          list($factionPoints, $factionPointsDetails) = $this->__calculPoints($factionKills, $factionDeaths, $factionGoldsPieces, $factionEndEvents, $factionKingzombieEvents);

        /*
          On enregistre les données dans la variable
        */
          $savedData[] = array(
            'name' => $factionName,
            'leader' => $leaderName,
            'kills' => $factionKills,
            'deaths' => $factionDeaths,
            'ratio' => $factionRatio,
            'golds_pieces' => $factionGoldsPieces,
            'end_events' => $factionEndEvents,
            'kingzombie_events' => $factionKingzombieEvents,
            'points' => $factionPoints,
						'points_details' => json_encode($factionPointsDetails)
          );


      }

    /*
      On sauvegarde les données
    */

    $this->out('<info>Save data...</info>');


      // On vide la db
        $this->FactionsRanking->deleteAll(array('1' => '1'));

      // On actualise l'auto_increment
        $this->FactionsRanking->query('ALTER TABLE `obsi__factions_rankings` AUTO_INCREMENT = 1;');

      // On save
  		  $this->FactionsRanking->saveMany($savedData);


  	$this->out('Done.');
  }

  /*
    Calcul les points en fonction des donnés passées
  */

  private function __calculPoints($kills, $deaths, $goldsPieces, $endEvents, $kingZombieEvents) {

    $points = 0;
		$factionPointsDetails = array(
			'kills' => 0,
			'deaths' => 0,
			'goldsPieces' => 0,
			'endEvents' => 0,
			'kingZombieEvents' => 0
		);

    /*
      En fonction des kills
    */
      if($kills < 1000) {

        $points += $factionPointsDetails['kills'] = 10 * $kills;

      } elseif($kills < 2000) {

        $points += $factionPointsDetails['kills'] = 12 * $kills;

      } elseif($kills < 3000) {

        $points += $factionPointsDetails['kills'] = 15 * $kills;

      } elseif($kills < 4000) {

        $points += $factionPointsDetails['kills'] = 17 * $kills;

      } elseif($kills < 5000) {

        $points += $factionPointsDetails['kills'] = 19 * $kills;

      } elseif($kills < 6000) {

        $points += $factionPointsDetails['kills'] = 25 * $kills;

      } else {

        $points += $factionPointsDetails['kills'] = 27 * $kills;

      }

    /*
      En fonction des morts
    */
      if($deaths < 1000) {

        $points += $factionPointsDetails['deaths'] = -12 * $deaths;

      } elseif($deaths < 2000) {

        $points += $factionPointsDetails['deaths'] = -14 * $kills;

      } elseif($deaths < 3000) {

        $points += $factionPointsDetails['deaths'] = -17 * $deaths;

      } elseif($deaths < 4000) {

        $points += $factionPointsDetails['deaths'] = -19 * $deaths;

      } elseif($deaths < 5000) {

        $points += $factionPointsDetails['deaths'] = -21 * $deaths;

      } elseif($deaths < 6000) {

        $points += $factionPointsDetails['deaths'] = -27 * $deaths;

      } else {

        $points += $factionPointsDetails['deaths'] = -29 * $deaths;

      }

    return array($points, $factionPointsDetails);

  }

  /*
    Récupère les kills & deaths d'une liste de joueurs puis calcule leur ratio
  */

  private function __getKillsAndDeathsOf($users) {

    //return array('kills' => 0, 'deaths' => 0, 'ratio' => 0); // TODO

    $killsAndDeathsWithRatio = array('kills' => 0, 'deaths' => 0, 'ratio' => 0);

    App::uses('ConnectionManager', 'Model');
    $con = new ConnectionManager;
    ConnectionManager::create('KillStats', Configure::read('Obsi.db.KillStats'));
    $db = $con->getDataSource('KillStats');

    if(is_array($users)) {

      $usersList = "'".implode("', '", $users)."'";
      $request = $db->fetchAll('SELECT * FROM killstats_data WHERE playerName IN('.$usersList.')', array());

      if(!empty($request)) {

        foreach ($request as $result) {
          $killsAndDeathsWithRatio['kills'] += $result['killstats_data']['kills'];
          $killsAndDeathsWithRatio['deaths'] += $result['killstats_data']['deaths'];
        }

      }

      if($killsAndDeathsWithRatio['deaths'] > 0) {
        $killsAndDeathsWithRatio['ratio'] = $killsAndDeathsWithRatio['kills'] / $killsAndDeathsWithRatio['deaths'];
      } else {
        $killsAndDeathsWithRatio['ratio'] = $killsAndDeathsWithRatio['kills'];
      }
      $killsAndDeathsWithRatio['ratio'] = round($killsAndDeathsWithRatio['ratio'], 2);
    }

    return $killsAndDeathsWithRatio;

  }

  /*
    Récupère les events end d'un joueur ou d'une liste de joueurs
  */

  private function __getGoldspiecesOf($users) {

    return 0; // TODO

    $goldpieces = 0;

    if(is_array($users)) {
      foreach ($users as $username) {
        # code...
      }

      foreach ($request as $result) {
        $goldpieces += 0;
      }
    } else {

      $goldpieces = 0;
    }

    return $goldpieces;

  }


  /*
    Récupère les events end d'un joueur ou d'une liste de joueurs
  */

  private function __getEndEventsOf($users) {

    return 0; // TODO

    $endEvents = 0;

    if(is_array($users)) {
      foreach ($users as $username) {
        # code...
      }

      foreach ($request as $result) {
        $endEvents += 0;
      }
    } else {

      $endEvents = 0;
    }

    return $endEvents;

  }

  /*
    Récupère les events KingZombie d'un joueur ou d'une liste de joueurs
  */

  private function __getKingzombieEventsOf($users) {

    return 0; // TODO

    $kingzombieEvents = 0;

    if(is_array($users)) {
      foreach ($users as $username) {
        # code...
      }

      foreach ($request as $result) {
        $kingzombieEvents += 0;
      }
    } else {

      $kingzombieEvents = 0;
    }

    return $kingzombieEvents;

  }

}