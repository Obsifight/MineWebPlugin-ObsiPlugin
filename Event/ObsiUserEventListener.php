<?php
App::uses('CakeEventListener', 'Event');

class ObsiUserEventListener implements CakeEventListener {

  private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
      return array(
          'beforeEncodePassword' => 'updateHash',
          'onLoadPage' => 'setProfileVars',
          'beforeRegister' => 'registerOnAuth',
          'beforeResetPassword' => 'updateAfterResetPasswordOnAuth',
          'beforeUpdatePassword' => 'updatePasswordOnAuth',
          'beforeEditUser' => 'editUserOnAuth',
          'onBuy' => 'checkIfPseudo'
      );
  }

  public function updateHash($event) {

    $password = $event->data['password'];
    $pseudo = $event->data['username'];

    $salt = 'PApVSuS8hDUEsOEP0fWZESmODaHkXVst27CTnYMM';

    $event->result = sha1($pseudo.$salt.$password);
    $event->stopPropagation();

  }

  public function setProfileVars($event) {

    if($this->controller->params['controller'] == "user" && $this->controller->params['action'] == "profile") {

      $profileCompletedPercentage = 0;

      /*
        On va donc calculer le pourcentage en fonction :
          - Compte confirmé ou non (email) - 20%
          - Skin configuré - 20%
          - Cape configurée - 20%
          - ObsiGuard configuré - 20%
          - Numéro de téléphone - 20%

      */

      $user = $this->controller->User->getAllFromCurrentUser();
      if(!empty($user)) {
        $user['isAdmin'] = $this->controller->User->isAdmin();
      }

      // Compte confirmé
        $confirmed = (empty($user['confirmed']) || date('Y-m-d H:i:s', strtotime($user['confirmed'])) == $user['confirmed']);
        $profileCompletedPercentage += ($confirmed) ? 20 : 0;

      // On vérifie qu'il ai upload un skin
        $uploadedSkin = ($user['obsi-skin_uploaded']);
        $profileCompletedPercentage += ($uploadedSkin) ? 20 : 0;

      // On vérifie qu'il ai upload une cape & qu'il en ai buy une
        $uploadedCape = ($user['obsi-cape_uploaded'] && $user['cape']);
        $profileCompletedPercentage += ($uploadedCape) ? 20 : 0;

      // On vérifie que le numéro est pas NULL
        $number_phone = (!empty($user['obsi-number_phone']));
        $profileCompletedPercentage += ($number_phone) ? 20 : 0;

      // On vérifie que ObsiGuard est actif
        $obsiguardStatus = ($user['obsi-obsiguard_enabled']);
        $profileCompletedPercentage += ($obsiguardStatus) ? 20 : 0;


      /*
        Skins & capes
      */

        $canSkin = ($user['vote'] >= 3 || $user['skin']);
        $canCape = ($user['cape']);

        $skinHeightMax = Configure::read('ObsiPlugin.skins.height-max');
        $skinWidthMax = Configure::read('ObsiPlugin.skins.width-max');

        $capeHeightMax = Configure::read('ObsiPlugin.capes.height-max');
        $capeWidthMax = Configure::read('ObsiPlugin.capes.width-max');

      /*
        Logs de connexion
      */

      $cache = Cache::read('connection', 'launcherlogs');
      if($cache === false || !isset($cache[$user['id']])) {
        App::uses('ConnectionManager', 'Model');
        $con = new ConnectionManager;
        ConnectionManager::create('Util', Configure::read('Obsi.db.Util'));
        $db = $con->getDataSource('Util');
        $launcherConnectionLogs = $db->query('SELECT * FROM loginlogs WHERE username=\''.$user['pseudo'].'\' ORDER BY id DESC');

        if($cache === false) {
          $cacheData = array($user['id'] => $launcherConnectionLogs);
        } else {
          $cache[$user['id']] = $launcherConnectionLogs;
          $cacheData = $cache;
        }
        Cache::write('connection', $cacheData, 'launcherlogs');
      } else {
        $launcherConnectionLogs = $cache[$user['id']];
      }

      ModuleComponent::$vars['launcherConnectionLogs'] = $launcherConnectionLogs;

      /*
        ObsiGuard
      */

        if($obsiguardStatus) { // Si il est activé

          // On se connecte à la db
            App::uses('ConnectionManager', 'Model');
            $con = new ConnectionManager;
            ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
            $db = $con->getDataSource('Auth');

          // On va récupérer les IPs actuelles
            $find = $db->fetchAll('SELECT authorised_ip,dynamic_ip FROM joueurs WHERE user_pseudo=?', array($user['pseudo']));
            if(!empty($find) && isset($find[0]['joueurs']['authorised_ip']) && isset($find[0]['joueurs']['dynamic_ip'])) {
              $authorised_ip = @unserialize($find[0]['joueurs']['authorised_ip']);
              if(is_array($authorised_ip)) {
                $this->controller->set('obsiguardIPs', $authorised_ip); // On les sets
              }
              $this->controller->set('obsiguardDynamicIPStatus', $find[0]['joueurs']['dynamic_ip']);
            }


        }

      $ip = $this->controller->Util->getIP();

      $this->controller->set(compact(
        'confirmed',
        'profileCompletedPercentage',
        'canSkin',
        'canCape',
        'skinHeightMax',
        'skinWidthMax',
        'capeHeightMax',
        'capeWidthMax',
        'obsiguardStatus',
        'ip'
      ));

      /*
        Notifications de demande de changement d'email
      */
      $EmailUpdateRequestResponseModel = ClassRegistry::init('Obsi.EmailUpdateRequestResponse');
      $findEmailUpdateResponse = $EmailUpdateRequestResponseModel->find('first', array('conditions' => array('user_id' =>  $user['id'])));
      if(!empty($findEmailUpdateResponse)) {
        $EmailUpdateRequestResponseModel->delete($findEmailUpdateResponse['EmailUpdateRequestResponse']['id']);

        if($findEmailUpdateResponse['EmailUpdateRequestResponse']['status']) {
          $msg = 'Votre demande de modification d\'email a été validé ! Votre email est désormais <b>'.$user['email'].'</b> !';
          $type = 'success';
        } else {
          $msg = 'Votre demande de modification d\'email a été refusé !';
          $type = 'danger';
        }

        ModuleComponent::$vars['EmailUpdateRequestResponse'] = array('msg' => $msg, 'type' => $type);
      }

    }

  }

  function registerOnAuth($event) {

    $pseudo = $event->data['data']['pseudo'];
    if(preg_match('/^MHF\_/', $pseudo)) {

      echo json_encode(array('statut' => false, 'msg' => 'Votre pseudo ne peut pas commencer par "<b>MHF_</b>".'));

      $event->stopPropagation();
      return false;
    }

    App::uses('ConnectionManager', 'Model');
    $con = new ConnectionManager;
    ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
    $db = $con->getDataSource('Auth');

    require ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'UUID'.DS.'uuid.class.php';

    // On set les données
      $profileid = UUID::v4();
      $password = $event->data['data']['password'];

    // On va l'insérer
      $db->fetchAll('INSERT INTO `joueurs`( `profileid`, `user_pseudo`, `user_mdp`, `is_register_v5`) VALUES (:profileid,:user_pseudo,:user_mdp, 1)', array(
        'profileid' => $profileid,
        'user_pseudo' => $pseudo,
        'user_mdp' => $password
      ));

  }

  function updateAfterResetPasswordOnAuth($event) {
    $user_id = $event->data['user_id'];
    $password = $event->data['new_password'];

    $pseudo = $this->controller->User->getFromUser('pseudo', $user_id);

    App::uses('ConnectionManager', 'Model');
    $con = new ConnectionManager;
    $db = $con->getDataSource('Auth');
    // On va l'update
      $db->fetchAll('UPDATE joueurs SET user_mdp=? WHERE user_pseudo=?', array($password, $pseudo));

  }

  function updatePasswordOnAuth($event) {
    $pseudo = $event->data['user']['pseudo'];
    $password = $event->data['new_password'];

    App::uses('ConnectionManager', 'Model');
    $con = new ConnectionManager;
    ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
    $db = $con->getDataSource('Auth');
    // On va l'update
      $db->fetchAll('UPDATE joueurs SET user_mdp=? WHERE user_pseudo=?', array($password, $pseudo));

  }

  function editUserOnAuth($event) {
    if($event->data['password_updated']) {

      $user_id = $event->data['user_id'];
      $password = $event->data['data']['password'];

      $pseudo = $this->controller->User->getFromUser('pseudo', $user_id);

      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
      $db = $con->getDataSource('Auth');
      // On va l'update
        $db->fetchAll('UPDATE joueurs SET user_mdp=? WHERE user_pseudo=?', array($password, $pseudo));

    }
  }

  function checkIfPseudo($event) {
    $items = $event->data['items'];
    $user = $event->data['user'];
    $user_id = $user['id'];

    $this->PseudoUpdateHistory = ClassRegistry::init('Obsi.PseudoUpdateHistory');

    foreach ($items as $key => $value) { // On les parcours

      if($value['id'] == Configure::read('ObsiPlugin.update-pseudo.item.id')) { // Si c'est l'ID du changement de pseudo

        // On vérifie qu'il l'a pas déjà acheté
          if($user['obsi-can_update_pseudo']) {

            echo json_encode(array('statut' => false, 'msg' => 'Vous avez déjà acheté un changement de pseudo !'));

            $event->stopPropagation();
            return false;
          }

        // On vérifie qu'il a pas déjà changé de pseudo 2 fois
          $count = $this->PseudoUpdateHistory->find('count', array('conditions' => array('user_id' => $user_id)));
          if($count >= 2) {
            echo json_encode(array('statut' => false, 'msg' => 'Vous avez déjà acheté un changement de pseudo 2 fois !'));

            $event->stopPropagation();
            return false;
          }

        // On vérifie qu'il a pas déjà changé de pseudo il y a moins de 15j
        $find = $this->PseudoUpdateHistory->find('first', array('conditions' => array('user_id' => $user_id), 'order' => 'id DESC'));
        if(!empty($find) && strtotime('+15 days', strtotime($find['PseudoUpdateHistory']['created'])) > time()) {
          echo json_encode(array('statut' => false, 'msg' => 'Vous avez déjà acheté un changement de pseudo il y a moins de 15 jours !'));

          $event->stopPropagation();
          return false;
        }

        // Aucune erreur on lui ajoute
          $this->controller->User->id = $user_id;
          $this->controller->User->saveField('obsi-can_update_pseudo', 1);

      }

    }
  }

}
