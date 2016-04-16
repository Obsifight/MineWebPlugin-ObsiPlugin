<?php

class ObsiguardController extends ObsiAppController {

  /*
  Historique des changements d'ObsiGuard (bdd)

  type =
    1: enable
    2: disable
    3: addIP (champ obsiguard_ip)
    4: removeIP (champ obsiguard_ip)
    5: switchDynamicIP (activation),
    6: switchDynamicIP (désactivation),
    7: generateConfirmCode
  */


  public function enable() {
    $this->autoRender = false;
    $this->response->type('json');
    // On vérifie que l'utilisateur est connecté
    if($this->isConnected && $this->request->is('ajax')) {

      // On se connecte à la db
        App::uses('ConnectionManager', 'Model');
        $con = new ConnectionManager;
        ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
        $db = $con->getDataSource('Auth');

      // On va mettre un array vide comme IP autorisées
        $db->fetchAll('UPDATE joueurs SET authorised_ip=? WHERE user_pseudo=?', array(serialize(array()), $this->User->getKey('pseudo')));

      // On le met dans la bdd site
        $this->User->setKey('obsi-obsiguard_enabled', 1);

      // On met dans l'historique
        $this->loadModel('Obsi.ObsiguardHistory');
        $this->ObsiguardHistory->create();
        $this->ObsiguardHistory->set(array(
          'user_id' => $this->User->getKey('id'),
          'ip' => $this->Util->getIP(),
          'type' => 1
        ));
        $this->ObsiguardHistory->save();

      // On dis au JS que c'est bon
        echo json_encode(array('statut' => true));

    } else {
      throw new ForbiddenException();
    }
  }

  public function disable() {
    $this->autoRender = false;
    $this->response->type('json');
    // On vérifie que l'utilisateur est connecté & qu'il est autorisé
    if($this->isConnected && $this->request->is('ajax')) {

      if($this->authorizeManage()) {

        // On se connecte à la db
          App::uses('ConnectionManager', 'Model');
          $con = new ConnectionManager;
          ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
          $db = $con->getDataSource('Auth');

        // On va mettre NULL comme IP autorisées
          $db->fetchAll('UPDATE joueurs SET authorised_ip=NULL WHERE user_pseudo=?', array($this->User->getKey('pseudo')));

        // On le met dans la bdd site
          $this->User->setKey('obsi-obsiguard_enabled', 0);

        // On met dans l'historique
          $this->loadModel('Obsi.ObsiguardHistory');
          $this->ObsiguardHistory->create();
          $this->ObsiguardHistory->set(array(
            'user_id' => $this->User->getKey('id'),
            'ip' => $this->Util->getIP(),
            'type' => 2
          ));
          $this->ObsiguardHistory->save();

        // On dis au JS que c'est bon
          echo json_encode(array('statut' => true));

      } else {
        $this->generateConfirmCode();
        echo json_encode(array('statut' => false, 'msg' => 'Not authorized'));
      }

    } else {
      throw new ForbiddenException();
    }
  }

  public function switchDynamicIP() {
    $this->autoRender = false;
    $this->response->type('json');
    // On vérifie que l'utilisateur est connecté
    if($this->isConnected && $this->request->is('ajax')) {

      // On se connecte à la db
        App::uses('ConnectionManager', 'Model');
        $con = new ConnectionManager;
        ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
        $db = $con->getDataSource('Auth');

      // On va récupérer l'état actuel du mode
        $find = $db->fetchAll('SELECT dynamic_ip FROM joueurs WHERE user_pseudo=?', array($this->User->getKey('pseudo')));
        if(!empty($find)) {
          $status = ($find[0]['joueurs']['dynamic_ip']) ? 0 : 1;
        } else {
          echo json_encode(array('statut' => false, 'msg' => 'Player not found'));
        }

      // On va set
        $db->fetchAll('UPDATE joueurs SET dynamic_ip=? WHERE user_pseudo=?', array($status, $this->User->getKey('pseudo')));

      // On met dans l'historique
        $this->loadModel('Obsi.ObsiguardHistory');
        $this->ObsiguardHistory->create();
        $this->ObsiguardHistory->set(array(
          'user_id' => $this->User->getKey('id'),
          'ip' => $this->Util->getIP(),
          'type' => ($status) ? 5 : 6
        ));
        $this->ObsiguardHistory->save();

      // On dis au JS que c'est bon
        echo json_encode(array('statut' => true));

    } else {
      throw new ForbiddenException();
    }
  }

  public function addIP() {
    $this->autoRender = false;
    $this->response->type('json');
    // On vérifie que l'utilisateur est connecté & qu'il est autorisé
    if($this->isConnected && $this->request->is('ajax')) {

      if($this->authorizeManage()) {

        if(!empty($this->request->data['ip'])) {

          if(filter_var($this->request->data['ip'], FILTER_VALIDATE_IP)) {

            // On se connecte à la db
              App::uses('ConnectionManager', 'Model');
              $con = new ConnectionManager;
              ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
              $db = $con->getDataSource('Auth');

            // On va récupérer les IPs actuelles
              $find = $db->fetchAll('SELECT authorised_ip FROM joueurs WHERE user_pseudo=?', array($this->User->getKey('pseudo')));
              if(!empty($find)) {
                // On ajoute l'ip envoyé à notre liste
                $ipList = @unserialize($find[0]['joueurs']['authorised_ip']);
                if(is_array($ipList)) {
                  $ipList[] = $this->request->data['ip'];
                } else {
                  $ipList = array($this->request->data['ip']);
                }
              } else {
                echo json_encode(array('statut' => false, 'msg' => 'Player not found'));
                return;
              }

            $ipList = serialize($ipList);

            // On va set
              $db->fetchAll('UPDATE joueurs SET authorised_ip=? WHERE user_pseudo=?', array($ipList, $this->User->getKey('pseudo')));

            // On met dans l'historique
              $this->loadModel('Obsi.ObsiguardHistory');
              $this->ObsiguardHistory->create();
              $this->ObsiguardHistory->set(array(
                'user_id' => $this->User->getKey('id'),
                'ip' => $this->Util->getIP(),
                'type' => 3,
                'obsiguard_ip' => $this->request->data['ip']
              ));
              $this->ObsiguardHistory->save();

            // On dis au JS que c'est bon
              echo json_encode(array('statut' => true));

          } else {
            echo json_encode(array('statut' => false, 'msg' => 'L\'adresse IP n\'est pas valide !'));
          }

        } else {
          echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
        }

      } else {
        $this->generateConfirmCode();
        echo json_encode(array('statut' => false, 'msg' => 'Not authorized', 'modal_authorize' => true));
      }

    } else {
      throw new ForbiddenException();
    }
  }

  public function removeIP($id = false) {
    $this->autoRender = false;
    $this->response->type('json');
    // On vérifie que l'utilisateur est connecté
    if($this->isConnected && $this->request->is('ajax')) {

      if($id !== false) {

        // On se connecte à la db
          App::uses('ConnectionManager', 'Model');
          $con = new ConnectionManager;
          ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
          $db = $con->getDataSource('Auth');

        // On va récupérer les IPs actuelles
          $find = $db->fetchAll('SELECT authorised_ip FROM joueurs WHERE user_pseudo=?', array($this->User->getKey('pseudo')));
          if(!empty($find)) {
            // On ajoute l'ip envoyé à notre liste
            $ipList = $find[0]['joueurs']['authorised_ip'];
            $ipList = @unserialize($find[0]['joueurs']['authorised_ip']);
            if(is_array($ipList) && isset($ipList[$id])) { // Si la clé existe
              $obsiguard_ip = $ipList[$id];
              unset($ipList[$id]); // on la supprime
            }
          } else {
            echo json_encode(array('statut' => false, 'msg' => 'Player not found'));
            return;
          }

        $ipList = serialize($ipList);

        // On va set
          $db->fetchAll('UPDATE joueurs SET authorised_ip=? WHERE user_pseudo=?', array($ipList, $this->User->getKey('pseudo')));

        // On met dans l'historique
          $this->loadModel('Obsi.ObsiguardHistory');
          $this->ObsiguardHistory->create();
          $this->ObsiguardHistory->set(array(
            'user_id' => $this->User->getKey('id'),
            'ip' => $this->Util->getIP(),
            'type' => 4,
            'obsiguard_ip' => $obsiguard_ip
          ));
          $this->ObsiguardHistory->save();

        // On dis au JS que c'est bon
          echo json_encode(array('statut' => true));

      } else {
        echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
      }

    } else {
      throw new ForbiddenException();
    }
  }

  private function generateConfirmCode() {
    $this->autoRender = false;

    // On vérifie qu'il a confirmé son email
    if($this->Configuration->getKey('confirm_mail_signup')) {
      $confirmed = $this->User->getKey('confirmed');

      if(!empty($confirmed) && date('Y-m-d H:i:s', strtotime($confirmed)) != $confirmed) {
        return false;
      }
    }

    // On génère la clé
      $key = substr(md5(rand().date('sihYdm')), 0, 10);

    // On prépare le message
      $emailMsg = "<h4>Salut ".$this->User->getKey('pseudo')." (".$this->Util->getIP().") !</h4><br>";
      $emailMsg .= "<p class='lead'>Tu trouveras ci-dessous le code pour te permettre d'ajouter une adresse IP à/de désactiver ObsiGuard sur ton compte.</p>";
      $emailMsg .= "<p style='text-align:center;'>\n<span style='font-weight:800;font-size:25px;'>".$key."</span></p>";

    // On envoie l'email
      $email = $this->Util->prepareMail(
    							$this->User->getKey('email'),
    							'Ajout d\'une adresse IP',
    							$emailMsg
    						)->sendMail();

    if($email) {

      // On l'enregistre
        $this->User->setKey('obsi-obsiguard_code', $key);

      // On met dans l'historique
        $this->loadModel('Obsi.ObsiguardHistory');
        $this->ObsiguardHistory->create();
        $this->ObsiguardHistory->set(array(
          'user_id' => $this->User->getKey('id'),
          'ip' => $this->Util->getIP(),
          'type' => 7
        ));
        $this->ObsiguardHistory->save();

      return true;
    }


    return false;

  }

  private function authorizeManage() {
    if($this->isConnected) {
      $session = $this->Session->read('can_manage_obsiguard');
      return (!empty($session) && $this->User->getKey('obsi-obsiguard_manage_key') == $session) ? true : false;
    } else {
      return false;
    }
  }

  public function checkAuthorizeCode() {
    $this->autoRender = false;
    // On vérifie que l'utilisateur est connecté
    if($this->isConnected && $this->request->is('ajax')) {

      if(!empty($this->request->data['code'])) {

        if($this->request->data['code'] == $this->User->getKey('obsi-obsiguard_code')) {

          $sessionKey = substr(sha1(rand().date('Ydmsih')), 0, 10);
          $this->User->setKey('obsi-obsiguard_manage_key', $sessionKey);
          $this->Session->write('can_manage_obsiguard', $sessionKey);

          // On dis au JS que c'est bon
            echo json_encode(array('statut' => true));

        } else {

          // Le code est faux
            echo json_encode(array('statut' => false, 'msg' => 'Le code que vous avez entré est faux.'));
        }

      } else {
        echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
      }

    } else {
      throw new ForbiddenException();
    }
  }

  public function checkAuthorizeManage() {
    $this->autoRender = false;
    $this->response->type('json');
		echo json_encode(array('statut' => $this->authorizeManage()));
  }

  /*
    Quand il y a une connexion étrangère
  */

    function ipn($username = null, $ip = null) {

      $this->autoRender = false;

      $findUser = $this->User->find('first', array('conditions' => array('pseudo' => $username)));
      if(!empty($findUser) && !empty($findUser['User']['obsi-number_phone'])) {

        // On peut envoyer le SMS
        App::uses('SMSComponent', 'Obsi.Controller/Component');
        $sms = "Une IP non autorisée vient de tenter de se connecter à ton compte ! \n".$ip."\nSTOP au XXXXX";
        $send = SMSComponent::send($sms, $findUser['User']['obsi-number_phone'], 'FR');

        return;

      }

    }


}
