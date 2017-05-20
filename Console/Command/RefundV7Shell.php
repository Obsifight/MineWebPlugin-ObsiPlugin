<?php
/*
  -> On parcours tous les utilisateurs
  -> On regarde dans la DB de l'auth si le joueur s'est connecté lors de la V5  (joueurs.has_connected_v5)
  -> On regarde si le joueur a été remboursé à la V6 (V6RefundHistory)
    -> Si oui, on le rembourse de ce qu'on l'avait précédemment remboursé
  -> On regarde si il a acheté des articles présent dans $items
    -> Si oui, on le rembourse du montant en PB
*/
class RefundV7Shell extends AppShell {

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
        47, // Annonce légendaire à votre connexion
        69, // Bois de rêne
        73, // Poulet malicieux
        77, // Cristal
        81, // Poulpy
        85, // Chapeau de fête
        89, // Lourde palourde
        93, // Encouragement
        97, // Cristal rouge
        101, // Oreille de chat
        105, // Chapeau de Noël
        109, // Cochon
        113, // Slimy
        117, // Lunette folle
        121, // Pierre tombale
        125, // Like
        129, // Chapeau humble
        133, // Sac d\'assassin
        137, // Epée sanglante
        141, // Sac craftman
        145, // Cornes du destin
        149, // AFK
        153, // Chapeau western
        157, // Barbe
        161, // Penguin
        165, // Couronne
        169, // Chapeau en carton
        173, // Chapeau Fou
        177, // Cristal de glace
        181, // Sac à dos Warrior
        185, // Cristal du nether
        189, // Chapeau panda
        193, // Masque à gaz
        197, // Sac de guerrier
        201 // Sac à dos Archer
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

    $this->out('On se connecte à la base de données V6');
      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      try
      {
        ConnectionManager::create('V6', array(
            'datasource' => 'Database/Mysql',
            'persistent' => false,
            'host' => 'localhost',
            'login' => 'web',
            //'login' => 'website',
            //'password' => 'Xa59M7jb5bLaq2FZ',
            'password' => 'mpV59xL3',
            'database' => 'web_v6',
            'encoding' => 'utf8'
        ));
      }
      catch (Exception $e)
      {
        $this->error('Impossible de se connecter à la base de données V6 !');
        return;
      }
      $bdd_V6 = $con->getDataSource('V6');

    $this->out('On se connecte à la base de données de l\'auth');
      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      try
      {
        ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
      }
      catch (Exception $e)
      {
        $this->error('Impossible de se connecter à la base de données auth !');
        return;
      }
      $bdd_auth = $con->getDataSource('Auth');

    // Find items price
    $this->out('On trouve les prix des articles a rembourser');
    foreach ($items as $item_id) {
      $findItemPrice = $bdd_V6->fetchAll('SELECT price FROM shop__items WHERE id = ?', array($item_id));
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
        === Check si le joueur s'est connecté en V6 ===
      */
      $findUserOnAuth = $bdd_auth->fetchAll('SELECT user_id FROM joueurs WHERE user_pseudo = ? AND `has_connected_v6` = 1', array($user_pseudo));
      if(empty($findUserOnAuth)) {
        $this->out('    Non connecté lors de la V6.');
        continue;
      }

      /*
        === Historique de remboursement lors V6 ===
      */
  /*    $findAddedPointsOnV6Refunds = $bdd_V6->fetchAll('SELECT added_money FROM obsi__refund_histories WHERE user_id = ?', array($user_id));
      if (!empty($findAddedPointsOnV6Refunds)) {
        $added_money = floatval($findAddedPointsOnV6Refunds[0]['obsi__refund_histories']['added_money']);
        $user_added_money += $added_money;
        $refund_others_versions += $added_money;
        $users_refund_others_versions++;
        $this->out('    Remboursement trouvé lors de la V6 : '.$added_money);
      } else {
        $this->out('    Aucun remboursement trouvé lors de la V6');
      }
  */
      /*
        === Historique d'achats parmis $items lors de la V6 ===
      */
      $findBuysOnV6 = $bdd_V6->fetchAll('SELECT item_id FROM shop__items_buy_histories WHERE user_id = ? AND (item_id = '.implode(' OR item_id = ', $items).')', array($user_id));
      if (!empty($findBuysOnV6)) {
        $added_money = 0;
        foreach ($findBuysOnV6 as $buy) {
          $item_id = $buy['shop__items_buy_histories']['item_id'];
          if (isset($itemsPrice[$item_id])) {
            $added_money += floatval($itemsPrice[$item_id]);
            $user_added_money += floatval($itemsPrice[$item_id]);
          } else {
            $this->out('    Erreur : Aucun article trouvé lors de la V6 (ID: '.$item_id.')');
          }
        }
        $this->out('    Articles achetés lors de la V6 : '.$added_money.' PB');
      } else {
        $this->out('    Aucun achats trouvés lors de la V6');
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
    //$this->out('Total remboursé des précédentes versions (V2, V3, V4, V5) : '.$refund_others_versions.' PB');
    //$this->out('Total utilisateurs remboursés des précédentes versions : '.$users_refund_others_versions);
    //$this->out('Total remboursé de cette version (V6) : '.($global_refund - $refund_others_versions).' PB');
    //$this->out('Total utilisateurs remboursés de cette version (V6) : '.($users_refunded - $users_refund_others_versions));
    $this->out('Total utilisateurs remboursés : '.$users_refunded);
		$this->out('Total remboursé : '.$global_refund.' PB');

  }

}
