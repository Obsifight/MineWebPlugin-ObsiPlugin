<?php
class RefundV5Shell extends AppShell {

	public $uses = array('User', 'Obsi.RefundHistory', 'Obsi.RefundsNotification'); //Models

  public function main() {

    // Articles remboursables
      $itemsV2 = array('170', '171', '172', '173', '174', '175', '205');
      $itemsV3 = array('170', '171', '172', '173', '174', '175', '205');
      $itemsV4 = array('Kit destruction', 'Kit construction', 'Kit alchimiste', 'Kit enchantement', 'Kit druide', 'Kit explorateur', 'Kit minerai', 'Kit golden');
      $items = array_merge($itemsV2, $itemsV3, $itemsV4);

			$global_refund = 0;
			$users_refunded = 0;
			$time = microtime(true);

			$usersData = array();
			$refundHistoriesData = array();
			$refundNotificationsData = array();

    $this->out('Démarrage du script...');

    // On tente de se connecter aux base de données
      $this->out('On se connecte à la base de données V2');
        try
        {
          $bdd_V2 = new PDO("mysql:host=localhost;dbname=web_v2", "website", "Xa59M7jb5bLaq2FZ");
          $bdd_V2->exec("SET NAMES utf8");
        }
        catch (Exception $e)
        {
          $this->error('Impossible de se connecter à la base de données V2 !');
          return;
        }
      $this->out('On se connecte à la base de données V3');
        try
        {
          $bdd_V3 = new PDO("mysql:host=localhost;dbname=web_v3", "website", "Xa59M7jb5bLaq2FZ");
          $bdd_V3->exec("SET NAMES utf8");
        }
        catch (Exception $e)
        {
          $this->error('Impossible de se connecter à la base de données V4 !');
          return;
        }
      $this->out('On se connecte à la base de données V4');
        try
        {
          $bdd_V4 = new PDO("mysql:host=localhost;dbname=web_v4", "website", "Xa59M7jb5bLaq2FZ");
          $bdd_V4->exec("SET NAMES utf8");
        }
        catch (Exception $e)
        {
          $this->error('Impossible de se connecter à la base de données V4 !');
          return;
        }


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
        === V4 ===
      */

      // On rembourse les achats de la V4 dans tous les cas
        $findPlayerHistoriesInV4 = $bdd_V4->prepare("SELECT * FROM histories WHERE action='BUY_ITEM' AND author=:author");
        $findPlayerHistoriesInV4->execute(array('author' => $user_pseudo));
        $findPlayerHistoriesInV4 = $findPlayerHistoriesInV4->fetchAll();

        foreach ($findPlayerHistoriesInV4 as $key => $value) { // On parcours ses achats

          if(in_array($value['other'], $itemsV4)) { // Si l'article est parmis ceux remboursables

            $findItem = $bdd_V4->prepare('SELECT id,name,price FROM items WHERE name=:name LIMIT 1');
            $findItem->execute(array('name' => $value['other']));
            $findItem = $findItem->fetch();
            if(!empty($findItem)) { // Si on trouve l'article acheté

              $this->out('    Remboursé à la V4 ('.$findItem['price'].' PB - ID: '.$findItem['id'].' - "'.$findItem['name'].'")');

              $refunded = true;
              $user_added_money += $findItem['price']; // On ajoute son prix aux pbs à add à l'user

            } else {
              $this->out('    Erreur article non trouvé (V4) ! (ID: '.$value['other'].')');
            }
            unset($findItem);

          }

        }
        unset($key);
        unset($value);


      /*
        === V3 ===
      */

      // On regarde si le joueur s'est remboursé lors de la V4
        $refundInV4 = $bdd_V4->prepare("SELECT refunded FROM users WHERE pseudo=:pseudo LIMIT 1");
        $refundInV4->execute(array('pseudo' => $user_pseudo));
        $refundInV4 = $refundInV4->fetch();
        $refundInV4 = ($refundInV4['refunded']) ? true : false;
        if($refundInV4) {
          // On récupère l'historique d'achats lors de la V3 parmis les achats récupérables
            $findPlayerHistoriesInV3 = $bdd_V3->prepare("SELECT * FROM historique WHERE joueur=:user_pseudo");
            $findPlayerHistoriesInV3->execute(array('user_pseudo' => $user_pseudo));
            $findPlayerHistoriesInV3 = $findPlayerHistoriesInV3->fetchAll();

            foreach ($findPlayerHistoriesInV3 as $key => $value) { // On parcours ses achats

              if(in_array($value['nom_offre'], $itemsV3)) { // Si l'article est parmis ceux remboursables

                $findItem = $bdd_V2->prepare('SELECT id,nom,prix FROM boutique WHERE id=:id LIMIT 1');
                $findItem->execute(array('id' => $value['nom_offre']));
                $findItem = $findItem->fetch();
                if(!empty($findItem)) { // Si on trouve l'article acheté

                  $this->out('    Remboursé à la V3 ('.$findItem['prix'].' PB - ID: '.$findItem['id'].' - "'.$findItem['nom'].'")');

                  $refunded = true;
                  $user_added_money += $findItem['prix']; // On ajoute son prix aux pbs à add à l'user

                } else {
                  $this->out('    Erreur article non trouvé (V3) ! (ID: '.$value['nom_offre'].')');
                }
                unset($findItem);

              }

            }
            unset($key);
            unset($value);
        } else {
          $this->out('    Non remboursé à la V3');
        }


      /*
        === V2 ===
      */

      // On regarde si le joueur s'est remboursé lors de la V3 & de la V4
        $refundInV3 = $bdd_V3->prepare('SELECT remboursement FROM joueurs WHERE user_pseudo =:user_pseudo AND remboursement=0 LIMIT 1');
        $refundInV3->execute(array('user_pseudo' => $user_pseudo));
        $refundInV3 = $refundInV3->fetchAll();
        $refundInV3 = (!empty($refundInV3));
        if($refundInV4 && $refundInV3) {
          // On récupère l'historique d'achats lors de la V2 parmis les achats récupérables
            $findPlayerHistoriesInV2 = $bdd_V2->prepare("SELECT * FROM historique WHERE joueur=:user_pseudo");
            $findPlayerHistoriesInV2->execute(array('user_pseudo' => $user_pseudo));
            $findPlayerHistoriesInV2 = $findPlayerHistoriesInV2->fetchAll();

            foreach ($findPlayerHistoriesInV2 as $key => $value) { // On parcours ses achats

              if(in_array($value['nom_offre'], $itemsV2)) { // Si l'article est parmis ceux remboursables

                $findItem = $bdd_V2->prepare('SELECT id,nom,prix FROM boutique WHERE id=:id LIMIT 1');
                $findItem->execute(array('id' => $value['nom_offre']));
                $findItem = $findItem->fetch();
                if(!empty($findItem)) { // Si on trouve l'article acheté

                  $this->out('    Remboursé à la V2 ('.$findItem['prix'].' PB - ID: '.$findItem['id'].' - "'.$findItem['nom'].'")');

                  $refunded = true;
                  $user_added_money += $findItem['prix']; // On ajoute son prix aux pbs à add à l'user

                } else {
                  $this->out('    Erreur article non trouvé (V2) ! (ID: '.$value['nom_offre'].')');
                }
                unset($findItem);

              }

            }
            unset($key);
            unset($value);

        } else {
          $this->out('    Non remboursé à la V2');
        }

      /*
        === Historique & ajouts de points & notifications ===
      */

        if(isset($refunded) && $refunded && $user_added_money > 0) {

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
		$this->out('Total utilisateurs remboursés : '.$users_refunded);
		$this->out('Total remboursé : '.$global_refund.' PB');

  }

}
