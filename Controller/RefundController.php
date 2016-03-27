<?php
class RefundController extends ObsiAppController {

  public function admin_index($user_pseudo = null) {
    if($this->isConnected && $this->User->isAdmin()) {

      $this->layout = 'admin';

      $searchUser = $this->User->find('first', array('conditions' => array('pseudo' => $user_pseudo)));

      if(!empty($searchUser)) {

        $this->set('title_for_layout', 'Achats au cours des versions de '.$user_pseudo);

        /*
          Infos remboursement
        */

          $refunded = false;
          $refundedPB = 0;

          $this->loadModel('Obsi.RefundHistory');
          $findRefund = $this->RefundHistory->find('all', array('conditions' => array('user_id' => $searchUser['User']['id'])));
          if(!empty($findRefund)) {
            $refunded = true;
            foreach ($findRefund as $key => $value) {
              $refundedPB += $value['RefundHistory']['added_money'];
            }
          }

        /*
            On se connecte aux base de données
        */
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

        /*
          On cherche les achats V4
        */

          $playerItemsV4 = array();

          $findPlayerHistoriesInV4 = $bdd_V4->prepare("SELECT * FROM histories WHERE action='BUY_ITEM' AND author=:author");
          $findPlayerHistoriesInV4->execute(array('author' => $user_pseudo));
          $findPlayerHistoriesInV4 = $findPlayerHistoriesInV4->fetchAll();

          foreach ($findPlayerHistoriesInV4 as $key => $value) { // On parcours ses achats


            $findItem = $bdd_V4->prepare('SELECT id,name,price FROM items WHERE name=:name LIMIT 1');
            $findItem->execute(array('name' => $value['other']));
            $findItem = $findItem->fetch();
            if(!empty($findItem)) { // Si on trouve l'article acheté

              $playerItemsV4[] = $findItem;

            }
            unset($findItem);


          }
          unset($key);
          unset($value);

        /*
          On cherche les achats à la V3
        */

          $playerItemsV3 = array();

          $refundInV4 = $bdd_V4->prepare("SELECT refunded FROM users WHERE pseudo=:pseudo LIMIT 1");
          $refundInV4->execute(array('pseudo' => $user_pseudo));
          $refundInV4 = $refundInV4->fetch();
          $refundInV4 = ($refundInV4['refunded']) ? true : false;

          // On récupère l'historique d'achats lors de la V3 parmis les achats récupérables
            $findPlayerHistoriesInV3 = $bdd_V3->prepare("SELECT * FROM historique WHERE joueur=:user_pseudo");
            $findPlayerHistoriesInV3->execute(array('user_pseudo' => $user_pseudo));
            $findPlayerHistoriesInV3 = $findPlayerHistoriesInV3->fetchAll();

            foreach ($findPlayerHistoriesInV3 as $key => $value) { // On parcours ses achats


              $findItem = $bdd_V2->prepare('SELECT id,nom,prix FROM boutique WHERE id=:id LIMIT 1');
              $findItem->execute(array('id' => $value['nom_offre']));
              $findItem = $findItem->fetch();
              if(!empty($findItem)) { // Si on trouve l'article acheté

                $playerItemsV3[] = $findItem;

              }
              unset($findItem);


            }
            unset($key);
            unset($value);

        /*
            On regarde les achats de la V2
        */

          $playerItemsV2 = array();

          $refundInV3 = $bdd_V3->prepare('SELECT remboursement FROM joueurs WHERE user_pseudo =:user_pseudo AND remboursement=0 LIMIT 1');
          $refundInV3->execute(array('user_pseudo' => $user_pseudo));
          $refundInV3 = $refundInV3->fetchAll();
          $refundInV3 = (!empty($refundInV3));

          // On récupère l'historique d'achats lors de la V2 parmis les achats récupérables
            $findPlayerHistoriesInV2 = $bdd_V2->prepare("SELECT * FROM historique WHERE joueur=:user_pseudo");
            $findPlayerHistoriesInV2->execute(array('user_pseudo' => $user_pseudo));
            $findPlayerHistoriesInV2 = $findPlayerHistoriesInV2->fetchAll();

            foreach ($findPlayerHistoriesInV2 as $key => $value) { // On parcours ses achats


              $findItem = $bdd_V2->prepare('SELECT id,nom,prix FROM boutique WHERE id=:id LIMIT 1');
              $findItem->execute(array('id' => $value['nom_offre']));
              $findItem = $findItem->fetch();
              if(!empty($findItem)) { // Si on trouve l'article acheté

                $playerItemsV2[] = $findItem;

              }
              unset($findItem);


            }
            unset($key);
            unset($value);



        $this->set(compact(
          'playerItemsV4',
          'refundInV4',
          'playerItemsV3',
          'refundInV3',
          'playerItemsV2',
          'user_pseudo',
          'refunded',
          'refundedPB'
        ));


      } else {
        throw new NotFoundException();
      }

    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_refund($user_pseudo = null, $credits = 0) {
    if($this->isConnected && $this->User->isAdmin()) {

      $this->autoRender = false;

      $findUser = $this->User->find('first', array('conditions' => array('pseudo' => $user_pseudo)));
      if(!empty($findUser) && !empty($credits)) {

        $user_money = $findUser['User']['money'];
        $user_id = $findUser['User']['id'];

        $user_new_sold = $user_money + $credits;

        $this->User->read(null, $user_id);
        $this->User->set(array('money' => $user_new_sold));
        $this->User->save();

        $this->loadModel('Obsi.RefundHistory');
        $this->RefundHistory->create();
        $this->RefundHistory->set(array(
          'user_id' => $user_id,
          'added_money' => $credits
        ));
        $this->RefundHistory->save();

        $this->loadModel('Obsi.RefundsNotification');
        $this->RefundsNotification->create();
        $this->RefundsNotification->set(array(
          'user_id' => $user_id,
          'refund_id' => $this->RefundHistory->getLastInsertId()
        ));
        $this->RefundsNotification->save();

        $this->Session->setFlash('Vous avez ajouté '.$credits.' PB à '.$user_pseudo.' !', 'default.success');
        $this->redirect(array('controller' => 'refund', 'action' => 'index', 'plugin' => 'obsi', 'admin' => true, $user_pseudo));

      } else {
        throw new NotFoundException();
      }

    } else {
      throw new ForbiddenException();
    }
  }

}
