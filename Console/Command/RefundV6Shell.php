<?php
/*
  -> On parcours tous les utilisateurs
  -> On regarde dans la DB de l'auth si le joueur s'est connecté lors de la V5  (joueurs.has_connected_v5)
  -> On regarde si le joueur a été remboursé à la V5 (V5RefundHistory)
    -> Si oui, on le rembourse de ce qu'on l'avait précédemment remboursé
  -> On regarde si il a acheté des articles présent dans $items
    -> Si oui, on le rembourse du montant en PB
*/
class RefundV6Shell extends AppShell {

	public $uses = array('User', 'Obsi.RefundHistory', 'Obsi.RefundsNotification'); //Models

  public function main() {

    // Articles remboursables
      $items = array(
        21, // Kit minerai
        22, // Kit alchimiste
        23, // Kit construction
        24, // Kit destruction
        25, // Kit druide
        26, // Kit explorateur
        27, // Kit golden
        28, // Kit enchantement
        47 // Annonce légendaire à votre connexion
      );
      $itemsPrice = array();

			$global_refund = 0;
      $refund_others_versions = 0;
			$users_refunded = 0;
      $users_refund_others_versions = 0;
			$time = microtime(true);

			$usersData = array();
			$refundHistoriesData = array();
			$refundNotificationsData = array();

    $this->out('Démarrage du script...');

    $this->out('On se connecte à la base de données V5');
      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      try
      {
        ConnectionManager::create('V5', array(
            'datasource' => 'Database/Mysql',
            'persistent' => false,
            'host' => 'localhost',
            'login' => 'web',
            //'login' => 'website',
            //'password' => 'Xa59M7jb5bLaq2FZ',
            'password' => 'mpV59xL3',
            'database' => 'web_v5',
            'encoding' => 'utf8'
        ));
      }
      catch (Exception $e)
      {
        $this->error('Impossible de se connecter à la base de données V5 !');
        return;
      }
      $bdd_V5 = $con->getDataSource('V5');

    $this->out('On se connecte à la base de données de l\'auth');
      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      try
      {
        ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
      }
      catch (Exception $e)
      {
        $this->error('Impossible de se connecter à la base de données V5 !');
        return;
      }
      $bdd_auth = $con->getDataSource('Auth');

    // Find items price
    $this->out('On trouve les prix des articles a rembourser');
    foreach ($items as $item_id) {
      $findItemPrice = $bdd_V5->fetchAll('SELECT price FROM shop__items WHERE id = ?', array($item_id));
      if (!empty($findItemPrice))
        $itemsPrice[$item_id] = $findItemPrice[0]['shop__items']['price'];
    }

    // All users
    $this->out('On parcours tous les utilisateurs');
    $users = $this->User->find('all'); // On prends tous les utilisateurs
		$count = $this->User->find('count');

		$i = 0;
    foreach ($users as $k => $v) { // On les parcours
			$i++;

      $user_pseudo = $v['User']['pseudo'];
      $user_money = $v['User']['money'];
      $user_added_money = 0;
      $user_id = $v['User']['id'];

      $this->out('- ['.$i.'/'.$count.'] Player : '.$user_pseudo);

      /*
        === Check si le joueur s'est connecté en V5 ===
      */
      $findUserOnAuth = $bdd_auth->fetchAll('SELECT user_id FROM joueurs WHERE user_pseudo = ? AND `has_connected_v5` = 1', array($user_pseudo));
      if(empty($findUserOnAuth)) {
        $this->out('    Non connecté lors de la V5.');
        continue;
      }

      /*
        === Historique de remboursement lors V5 ===
      */
      $findAddedPointsOnV5Refunds = $bdd_V5->fetchAll('SELECT added_money FROM obsi__refund_histories WHERE user_id = ?', array($user_id));
      if (!empty($findAddedPointsOnV5Refunds)) {
        $added_money = floatval($findAddedPointsOnV5Refunds[0]['obsi__refund_histories']['added_money']);
        $user_added_money += $added_money;
        $refund_others_versions += $added_money;
        $users_refund_others_versions++;
        $this->out('    Remboursement trouvé lors de la V5 : '.$added_money);
      } else {
        $this->out('    Aucun remboursement trouvé lors de la V5');
      }

      /*
        === Historique d'achats parmis $items lors de la V5 ===
      */
      $findBuysOnV5 = $bdd_V5->fetchAll('SELECT item_id FROM shop__items_buy_histories WHERE user_id = ? AND (item_id = '.implode(' OR item_id = ', $items).')', array($user_id));
      if (!empty($findBuysOnV5)) {
        $added_money = 0;
        foreach ($findBuysOnV5 as $buy) {
          $item_id = $buy['shop__items_buy_histories']['item_id'];
          if (isset($itemsPrice[$item_id])) {
            $added_money += floatval($itemsPrice[$item_id]);
            $user_added_money += floatval($itemsPrice[$item_id]);
          } else {
            $this->out('    Erreur : Aucun article trouvé lors de la V5 (ID: '.$item_id.')');
          }
        }
        $this->out('    Articles achetés lors de la V5 : '.$added_money.' PB');
      } else {
        $this->out('    Aucun achats trouvés lors de la V5');
      }
      /*
        === Historique & ajouts de points & notifications ===
      */

        if($user_added_money > 0) {

					$users_refunded++;

          $this->out('    Remboursé au total de : '.$user_added_money);

          $user_new_sold = $user_money + $user_added_money;

					$global_refund += $user_added_money;

          $this->User->read(null, $user_id);
          $this->User->set(array('money' => $user_new_sold));
        //  $this->User->save();


          $this->RefundHistory->create();
          $this->RefundHistory->set(array(
            'user_id' => $user_id,
            'added_money' => $user_added_money
          ));
          //$this->RefundHistory->save();


          $this->RefundsNotification->create();
          $this->RefundsNotification->set(array(
            'user_id' => $user_id,
            'refund_id' => $this->RefundHistory->getLastInsertId()
          ));
          //$this->RefundsNotification->save();


        } else {
          $this->out('    Non remboursé.');
        }

      unset($user_pseudo);
      unset($user_money);
      unset($user_id);
    }

		$this->out("\n\n");
		$this->out('Time: '.(microtime(true)-$time).' sec.');
    $this->out('Total remboursé des précédentes versions (V2, V3, V4) : '.$refund_others_versions.' PB');
    $this->out('Total utilisateurs remboursés des précédentes versions : '.$users_refund_others_versions);
    $this->out('Total remboursé de cette version (V5) : '.($global_refund - $refund_others_versions).' PB');
    $this->out('Total utilisateurs remboursés de cette version (V5) : '.($users_refunded - $users_refund_others_versions));
    $this->out('Total utilisateurs remboursés : '.$users_refunded);
		$this->out('Total remboursé : '.$global_refund.' PB');

  }

}
