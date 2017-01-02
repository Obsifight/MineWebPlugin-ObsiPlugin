<?php
class PrizeController extends ObsiAppController {

  public function admin_index() {
    if($this->isConnected && $this->Permissions->can('MANAGE_PRIZE')) {
      $this->set('title_for_layout', 'Liste des lots en attente');
      $this->layout = 'admin';

      $prizes = $this->Prize->find('all');

      $usersToFind = array();
      foreach ($prizes as $prize) {
        $usersToFind[] = $prize['Prize']['user_id'];
        $usersToFind[] = $prize['Prize']['staff_id'];
      }

      $findUsers = $this->User->find('all', array('conditions' => array('id' => $usersToFind)));

      $usersByID = array();
      foreach ($findUsers as $user) {
        $usersByID[$user['User']['id']] = $user['User']['pseudo'];
      }

      $this->set(compact('prizes', 'usersByID'));

    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_add() {
    if($this->isConnected && $this->Permissions->can('MANAGE_PRIZE')) {
      $this->set('title_for_layout', 'Ajouter un lot');
      $this->layout = 'admin';
    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_add_ajax($id) {
    if($this->isConnected && $this->Permissions->can('MANAGE_PRIZE')) {

      $this->autoRender = false;
      if($this->request->is('ajax')) {

        if(!empty($this->request->data['commands']) && !empty($this->request->data['name'])) {

          $commands = implode('[{+}]', $this->request->data['commands']);

          $user_id = $this->User->getFromUser('id', $this->request->data['pseudo']);

          if(!empty($user_id)) {

            $this->Prize->create();
            $this->Prize->set(array(
              'user_id' => $user_id,
              'staff_id' => $this->User->getKey('id'),
              'name' => $this->request->data['name'],
              'commands' => $commands
            ));
            $this->Prize->save();

            $this->Session->setFlash('Vous avez bien ajouté un lot !', 'default.success');
            echo json_encode(array('statut' => true, 'msg' => 'Vous avez bien ajouté un lot !'));

          } else {
            echo json_encode(array('statut' => false, 'msg' => 'Le joueur n\'a pas été trouvé !'));
          }

        } else {
          echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
        }

      } else {
        echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')));
      }

    } else {
      throw new ForbiddenException();
    }
  }

  public function admin_remove($id = false) {
    if($this->isConnected && $this->Permissions->can('MANAGE_PRIZE')) {
      $this->autoRender = false;

      if($this->Prize->delete($id)) {
        $this->Session->setFlash('Vous avez bien supprimé un lot !', 'default.success');
      } else {
        $this->Session->setFlash('Une erreur est survenue lors de la suppression du lot !', 'default.error');
      }

      $this->redirect(array('action' => 'index', 'admin' => true));

    } else {
      throw new ForbiddenException();
    }
  }

  public function get($id = false) {
    if($this->isConnected) {

      $findPrize = $this->Prize->find('first', array('conditions' => array('id' => $id)));
      if(!empty($findPrize)) {

        if($findPrize['Prize']['user_id'] == $this->User->getKey('id')) {

          $server_id = Configure::read('ObsiPlugin.server.pvp.id');
          $callConnected = $this->Server->call(array('isConnected' => $user['pseudo']), true, $server_id);
          if(isset($callConnected['isConnected']) && $callConnected['isConnected'] == "true") {

            $this->Server->commands($findPrize['Prize']['commands'], $server_id);

            $this->Prize->delete($id);

            $this->Session->setFlash('Vous avez bien reçu votre lot !', 'default.success');
            $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));

          } else {
            $this->Session->setFlash('Vous devez être connecté pour recevoir ce lot !', 'default.error');
            $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
          }

        } else {
          throw new ForbiddenException();
        }

      } else {
        throw new NotFoundException();
      }

    } else {
      throw new ForbiddenException();
    }
  }


}
