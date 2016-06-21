<?php
class PaysafecardController extends ObsiAppController {


  public function admin_banUser($user_id) {
    $this->autoRender = false;
    if($this->isConnected && $this->User->isAdmin()) {

      $findUser = $this->User->find('first', array('conditions' => array('id' => $user_id)));
      if(!empty($findUser)) {

        $this->loadModel('Obsi.PscBan');
        $findBan = $this->PscBan->find('first', array('conditions' => array('user_id' => $user_id)));
        if(empty($findBan)) {

          $this->PscBan->create();
          $this->PscBan->set(array(
            'user_id' => $user_id,
            'author_id' => $this->User->getKey('id')
          ));
          $this->PscBan->save();

          $this->loadModel('Shop.Paysafecard');
          // on cherche toutes les psc de l'user pour retirer si elles ont été prise en charge
          $this->loadModel('Obsi.PscTaked');
          $findPSC = $this->Paysafecard->find('all', array('conditions' => array('user_id' => $user_id)));
          foreach ($findPSC as $key => $value) {
            $this->PscTaked->deleteAll(array('psc_id' => $value['Paysafecard']['id']));
          }
          // on supprime toutes les psc de l'user
          $this->Paysafecard->deleteAll(array('user_id' => $user_id));

          $this->Session->setFlash('Vous avez bien banni l\'utilisateur '.$findUser['User']['pseudo'].' du moyen de paiement PaySafeCard !', 'default.success');
        } else {
          $this->Session->setFlash('L\'utilisateur '.$findUser['User']['pseudo'].' est déjà banni du moyen de paiement PaySafeCard !', 'default.error');
        }

        $this->redirect(array('controller' => 'payment', 'action' => 'index', 'plugin' => 'shop', 'admin' => true));

      } else {
        throw new NotFoundException();
      }

    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_take() {
    $this->autoRender = false;
    $this->response->type('json');
    if($this->isConnected && $this->Permissions->can('CAN_TAKE_PSC')) {

      if($this->request->is('post') && !empty($this->request->data['id'])) {

        $id = $this->request->data['id'];

        $this->loadModel('Shop.Paysafecard');
        $findPSC = $this->Paysafecard->find('first', array('conditions' => array('id' => $id)));
        if(!empty($findPSC)) {

          $this->loadModel('Obsi.PscTaked');
          $ifTaked = $this->PscTaked->find('first', array('conditions' => array('psc_id' => $id)));
          if(empty($ifTaked)) {

            $searchIfAlreadyTaked = $this->PscTaked->find('first', array('conditions' => array('user_id' => $this->User->getKey('id'))));
            if(empty($searchIfAlreadyTaked)) {

              $this->loadModel('Shop.PaysafecardHistory');
              $findPSCValidedThisMonth = $this->PaysafecardHistory->find('all', array(
                'fields' => array('SUM(amount)'),
                'conditions' => array(
                  'author_id' => $this->User->getKey('id'),
                  'created >= ' => date('Y-m-01 00:00:00') // supérieur au début du mois courant
                )
              ));

              //$quotas = Configure::read('Obsi.shop.psc.quotas');
              $this->loadModel('Obsi.PscQuotas');
              $quotas = array();
              $findQuotas = $this->PscQuotas->find('all');
              foreach ($findQuotas as $quota) {
                $pseudo = ($quota['PscQuotas']['user_id'] == 0) ? 'all' : $this->User->getFromUser('pseudo', $quota['PscQuotas']['user_id']);
                $quotas[$pseudo] = $quota['PscQuotas']['quota'];
              }

              foreach ($quotas as $user => $quota) {

                if($this->User->getKey('pseudo') == $user) {
                  $userQuota = $quota;
                  break;
                }

              }
              if(!isset($userQuota)) {
                $userQuota = $quotas['all'];
              }

              if(empty($findPSCValidedThisMonth)) {
                $quotaUsed = $findPSC['Paysafecard']['amount'];
              } else {
                $quotaUsed = $findPSCValidedThisMonth[0][0]['SUM(amount)'] + $findPSC['Paysafecard']['amount'];
              }

              if(empty($findPSCValidedThisMonth) || $quotaUsed <= $userQuota) {

                $this->PscTaked->create();
                $this->PscTaked->set(array('user_id' => $this->User->getKey('id'), 'psc_id' => $id));
                $this->PscTaked->save();

                echo json_encode(array('statut' => true, 'code' => $findPSC['Paysafecard']['code']));

              } else {
                echo json_encode(array('statut' => false, 'msg' => 'Vous avez déjà dépassé votre quota du mois !'));
              }

            } else {
              echo json_encode(array('statut' => false, 'msg' => 'Vous ne pouvez prendre qu\'une seule PaySafeCard à la fois !'));
            }

          } else {
            echo json_encode(array('statut' => false, 'msg' => 'La PaySafeCard est déjà prise en charge !'));
          }

        } else {
          echo json_encode(array('statut' => false, 'msg' => 'La PaySafeCard est introuvable !'));
        }

      } else {
        throw new NotFoundException();
      }

    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_valid($id = null, $money = 0) {
    $this->autoRender = false;
    if($this->isConnected AND $this->Permissions->can('CAN_TAKE_PSC')) {
      if(!empty($id) && !empty($money)) {

        $this->loadModel('Shop.Paysafecard');
        $search = $this->Paysafecard->find('first', array('conditions' => array('id' => $id)));

        if(!empty($search)) {

          $this->loadModel('Obsi.PscTaked');
          $ifTaked = $this->PscTaked->find('first', array('conditions' => array('psc_id' => $id)));
          if(!empty($ifTaked)) {

            $findPaysafecard = $search['Paysafecard'];

            $this->History->set('BUY_MONEY', 'shop', null, $findPaysafecard['user_id']);

            $user_money = $this->User->getFromUser('money', $findPaysafecard['user_id']);
            $new_money = intval($user_money) + intval($money);
            $this->User->setToUser('money', $new_money, $findPaysafecard['user_id']);

            $this->loadModel('Shop.PaysafecardMessage');
            $this->PaysafecardMessage->create();
            $this->PaysafecardMessage->set(array(
              'user_id' => $findPaysafecard['user_id'],
              'type' => 1,
              'amount' => $findPaysafecard['amount'],
              'added_points' => intval($money)
            ));
            $this->PaysafecardMessage->save();

            $this->loadModel('Shop.PaysafecardHistory');
            $this->PaysafecardHistory->create();
            $this->PaysafecardHistory->set(array(
              'code' => $findPaysafecard['code'],
              'amount' => $findPaysafecard['amount'],
              'credits_gived' => intval($money),
              'user_id' => $findPaysafecard['user_id'],
              'author_id' => $this->User->getKey('id')
            ));
            $this->PaysafecardHistory->save();

            $this->PscTaked->delete($ifTaked['PscTaked']['id']);

            $this->Paysafecard->delete($id);
            $this->History->set('VALID_PAYSAFECARD', 'shop');

            $this->Session->setFlash($this->Lang->get('SHOP__PAYSAFECARD_VALID_SUCCESS'), 'default.success');
            $this->redirect(array('controller' => 'payment', 'action' => 'index', 'admin' => true, 'plugin' => 'shop'));

          } else {
            throw new ForbiddenException();
          }

        } else {
          throw new NotFoundException('PSC not found');
        }
      } else {
        throw new NotFoundException('Empty ID/Money');
      }
    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_invalid($id = null) {
    $this->autoRender = false;
    if($this->isConnected AND $this->Permissions->can('CAN_TAKE_PSC')) {
      if(!empty($id)) {

        $this->loadModel('Shop.Paysafecard');
        $search = $this->Paysafecard->find('first', array('conditions' => array('id' => $id)));

        if(!empty($search)) {

          $this->loadModel('Obsi.PscTaked');
          $ifTaked = $this->PscTaked->find('first', array('conditions' => array('psc_id' => $id)));
          if(!empty($ifTaked)) {

            $this->loadModel('Shop.PaysafecardMessage');
            $this->PaysafecardMessage->read(null, null);
            $this->PaysafecardMessage->set(array(
              'user_id' => $search['Paysafecard']['user_id'],
              'type' => 0,
              'amount' => $search['Paysafecard']['amount'],
              'added_points' => 0
            ));
            $this->PaysafecardMessage->save();

            $this->PscTaked->delete($ifTaked['PscTaked']['id']);
            $this->Paysafecard->delete($id);

            $this->History->set('INVALID_PAYSAFECARD', 'shop');

            $this->Session->setFlash($this->Lang->get('SHOP__PAYSAFECARD_INVALID_SUCCESS'), 'default.success');
            $this->redirect(array('controller' => 'payment', 'action' => 'index', 'admin' => true, 'plugin' => 'shop'));

          } else {
            throw new ForbiddenException();
          }

        } else {
          throw new NotFoundException('PSC not found');
        }
      } else {
        throw new NotFoundException('Empty ID/Money');
      }
    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_viewQuota() {
    if($this->isConnected && $this->User->isAdmin()) {

      $this->layout = 'admin';
      $this->set('title_for_layout', 'Voir les quotas de PaySafeCard depuis '.date('Y-m-01 00:00:00'));

      //$quotas = Configure::read('Obsi.shop.psc.quotas');
      $this->loadModel('Obsi.PscQuotas');
      $quotas = array();
      $findQuotas = $this->PscQuotas->find('all');
      foreach ($findQuotas as $quota) {
        $pseudo = ($quota['PscQuotas']['user_id'] == 0) ? 'all' : $this->User->getFromUser('pseudo', $quota['PscQuotas']['user_id']);
        $quotas[$pseudo] = $quota['PscQuotas']['quota'];
      }

      $usersToFind = array();
      $usersByID = array();
      $usersQuotas = array();

      $this->loadModel('Shop.PaysafecardHistory');
      $findPaySafeCardHistory = $this->PaysafecardHistory->find('all', array('conditions' => array('created >= ' => date('Y-m-01 00:00:00'))));
      foreach ($findPaySafeCardHistory as $key => $value) {
        $usersToFind[] = $value['PaysafecardHistory']['author_id'];

        if(!isset($usersQuotas[$value['PaysafecardHistory']['author_id']])) {
          $usersQuotas[$value['PaysafecardHistory']['author_id']] = $value['PaysafecardHistory']['amount'];
        } else {
          $usersQuotas[$value['PaysafecardHistory']['author_id']] += $value['PaysafecardHistory']['amount'];
        }
      }

      $findUsersByID = $this->User->find('all', array('conditions' => array('id' => $usersToFind)));
      foreach ($findUsersByID as $key => $value) {
        $usersByID[$value['User']['id']] = $value['User']['pseudo'];
      }

      $this->set(compact(
        'usersQuotas',
        'quotas',
        'usersByID'
      ));

    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_edit_quota() {
    if($this->isConnected && $this->User->isAdmin()) {
      $authorized_users = explode("\n", file_get_contents(ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Config'.DS.'authorized_users_quota.txt'));
      if(in_array($this->User->getKey('pseudo'), $authorized_users)) {

        $this->layout = 'admin';
        $this->set('title_for_layout', 'Modifier les quotas de PaySafeCard');

        $this->loadModel('Obsi.PscQuotas');

        if($this->request->is('post')) {

          if(
            !isset($this->request->data['users'][0]) ||
            empty($this->request->data['users'][0]) ||
            !isset($this->request->data['users'][0]['pseudo']) ||
            empty($this->request->data['users'][0]['pseudo']) ||
            $this->request->data['users'][0]['pseudo'] != "all" ||
            !isset($this->request->data['users'][0]['quota'])
          ) {

            $this->Session->setFlash('Vous ne pouvez pas ne pas configurer de quota global !', 'default.error');
            return;

          }

          foreach ($this->request->data['users'] as $user) {

            if(!empty($user['pseudo'])) {

              if($user['pseudo'] == "all") {
                $user_id = 0;
              } else {
                $user_id = $this->User->getFromUser('id', $user['pseudo']);
              }

              $find = $this->PscQuotas->find('first', array('conditions' => array('user_id' => $user_id)));
              if(!empty($find)) {
                $this->PscQuotas->read(null, $find['PscQuotas']['id']);
              } else {
                $this->PscQuotas->create();
              }
              $this->PscQuotas->set(array(
                'user_id' => $user_id,
                'updated_by' => $this->User->getKey('id'),
                'quota' => $user['quota'],
                'modified' => date('Y-m-d H:i:s')
              ));
              $this->PscQuotas->save();

            }

          }

          $this->Session->setFlash('Les quotas ont bien été modifiés !', 'default.success');

        }

        $quotas = array();
        $findQuotas = $this->PscQuotas->find('all');
        foreach ($findQuotas as $quota) {
          $pseudo = ($quota['PscQuotas']['user_id'] == 0) ? 'all' : $this->User->getFromUser('pseudo', $quota['PscQuotas']['user_id']);
          $quotas[] = array(
            'pseudo' => $pseudo,
            'quota' => $quota['PscQuotas']['quota']
          );
        }

        $this->set('quotas', $quotas);

      } else {
        throw new ForbiddenException();
      }
    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_remove_quota($username) {
    if($this->isConnected && $this->User->isAdmin()) {
      $authorized_users = explode("\n", file_get_contents(ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Config'.DS.'authorized_users_quota.txt'));
      if(in_array($this->User->getKey('pseudo'), $authorized_users)) {
        $this->autoRender = false;
        $this->loadModel('Obsi.PscQuotas');
        $this->PscQuotas->deleteAll(array('user_id' => $this->User->getFromUser('id', $username)));
      }
    }
  }

}
