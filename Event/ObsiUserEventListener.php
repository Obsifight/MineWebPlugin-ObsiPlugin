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
          'onBuy' => 'checkIfPseudo',
          'onLoadAdminPanel' => 'setVarsOnUserEdit',
          'afterLogin' => 'logConnection',
          'beforeSendResetPassMail' => 'checkIfEmailIsConfirmed'
      );
  }

  public function checkIfEmailIsConfirmed($event) {
    $user_id = $event->data['user_id'];

    if($this->controller->Configuration->getKey('confirm_mail_signup')) {

      $userModel = ClassRegistry::init('User');
      $find = $userModel->find('first', array('conditions' => array('id' => $user_id)));

      if(!empty($find['User']['confirmed']) && date('Y-m-d H:i:s', strtotime($find['User']['confirmed'])) != $find['User']['confirmed']) {

        echo json_encode(array('statut' => false, 'msg' => 'Vous ne pouvez pas rénitialiser votre mot de passe si vous n\'avez pas confirmé votre email !'));

        $event->stopPropagation();
        return false;
      }
    }
  }

  public function updateHash($event) {

    $password = $event->data['password'];
    $pseudo = $event->data['username'];

    $salt = 'PApVSuS8hDUEsOEP0fWZESmODaHkXVst27CTnYMM';

    $event->result = sha1($pseudo.$salt.$password);
    $event->stopPropagation();

  }

  public function setVarsOnUserEdit($event) {
    if($this->controller->params['controller'] == "user" && $this->controller->params['action'] == "admin_edit") {

      /*
          ObsiGuard
      */

        $user_id = $this->controller->request->params['pass'][0];
        $findUser = $this->controller->User->find('first', array('conditions' => array('id' => $user_id)));
        if(empty($findUser)) {
          $findUser = $this->controller->User->find('first', array('conditions' => array('pseudo' => $user_id)));
          $user_id = $findUser['User']['id'];
        }
        $user = $findUser['User'];

        $obsiguardStatus = ($user['obsi-obsiguard_enabled']);
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
                ModuleComponent::$vars['obsiguardIPs'] = $authorised_ip; // On les ses
              }
              ModuleComponent::$vars['obsiguardDynamicIPStatus'] = $find[0]['joueurs']['dynamic_ip'];
            }


        }
        ModuleComponent::$vars['obsiguardStatus'] = $obsiguardStatus;

      /*
          Logs de connexion launcher
      */

        App::uses('ConnectionManager', 'Model');
        $con = new ConnectionManager;
        ConnectionManager::create('Util', Configure::read('Obsi.db.Util'));
        $dbUtil = $con->getDataSource('Util');
        $launcherConnectionLogs = $dbUtil->query('SELECT * FROM loginlogs WHERE username=\''.$user['pseudo'].'\' ORDER BY id DESC');
        ModuleComponent::$vars['launcherConnectionLogs'] = $launcherConnectionLogs;


      /*
          Logs de connexion site
      */

        $ConnectionLogModel = ClassRegistry::init('Obsi.ConnectionLog');
        $webConnectionLogs = $ConnectionLogModel->find('all', array('order' => 'id desc', 'conditions' => array('user_id' => $user_id)));
        ModuleComponent::$vars['webConnectionLogs'] = $webConnectionLogs;

      /*
        On groupe les IPs & on les comptes
      */

        $groupedIP = array();

        foreach ($launcherConnectionLogs as $key => $value) {

          $data = $value['loginlogs'];
          $ip = $data['ip'];

          if(!isset($groupedIP[$ip])) {
            $groupedIP[$ip] = 1;
          } else {
            $groupedIP[$ip]++;
          }

          unset($ip);
          unset($data);

        }

        arsort($groupedIP);

        ModuleComponent::$vars['groupedIP'] = $groupedIP;

      /*
        On recherche les éventuels doubles comptes (launcher)
      */

        $IPList = array_keys($groupedIP); // On récupére que les IPs

        // On prépare la liste de OR
        $queryOR = "ip='";
        $queryOR .= implode("' OR ip='", $IPList);
        $queryOR .= "'";

        // On recherche les comptes avec les IPs trouvées
        $findDoubleAccountLogs = $dbUtil->query("SELECT *,COUNT(*) AS count,GROUP_CONCAT(DISTINCT ip SEPARATOR ',') AS ipList FROM loginlogs WHERE $queryOR AND username != '{$user['pseudo']}' GROUP BY username ORDER BY count DESC");

        $doubleAccountLogs = array();
        foreach ($findDoubleAccountLogs as $key => $value) {
          if($value['loginlogs']['username'] != $user['pseudo']) {

            $ipList = explode(',', $value[0]['ipList']);
            $count = 0;
            foreach ($ipList as $k => $ip) {
              $count += $groupedIP[$ip];
            }

            $doubleAccountLogs[] = array(
              'username' => $value['loginlogs']['username'],
              'count' => $count,
            );
            unset($count);

          }
        }

        usort($doubleAccountLogs, function($a, $b) {
          return $a['count'] < $b['count'];
        });

        ModuleComponent::$vars['doubleAccountLogs'] = $doubleAccountLogs;

    }
  }

  public function logConnection($event) {
    $ip = $this->controller->Util->getIP();

    $ConnectionLogModel = ClassRegistry::init('Obsi.ConnectionLog');
    $ConnectionLogModel->create();
    $ConnectionLogModel->set(array(
      'ip' => $ip,
      'user_id' => $event->data['user']['id']
    ));
    $ConnectionLogModel->save();
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

      $cache = Cache::read('connection_'.$user['id'], 'launcherlogs');
      if($cache === false) {
        App::uses('ConnectionManager', 'Model');
        $con = new ConnectionManager;
        ConnectionManager::create('Util', Configure::read('Obsi.db.Util'));
        $db = $con->getDataSource('Util');
        $launcherConnectionLogs = $db->query('SELECT * FROM loginlogs WHERE username=\''.$user['pseudo'].'\' ORDER BY id DESC');

        Cache::write('connection_'.$user['id'], $launcherConnectionLogs, 'launcherlogs');
      } else {
        $launcherConnectionLogs = $cache;
      }

      ModuleComponent::$vars['launcherConnectionLogs'] = $launcherConnectionLogs;

      $ConnectionLogModel = ClassRegistry::init('Obsi.ConnectionLog');
      $webConnectionLogs = $ConnectionLogModel->find('all', array('order' => 'id desc', 'conditions' => array('user_id' => $user['id'])));
      ModuleComponent::$vars['webConnectionLogs'] = $webConnectionLogs;

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

      /*
        Switch du serveur
      */

      /*
        Switch
      */

        $cache = Cache::read('stats-ranks-premium', 'data-short');
        if (!$cache) {
          $query = @json_decode(@file_get_contents('http://api.obsifight.net/users/staff/premium'), true);
          $ranks = $query['data'];
          $ranks = array_reverse($ranks);
          Cache::write('stats-ranks-premium', $ranks, 'data-short');
        } else {
          $ranks = Cache::read('stats-ranks-premium', 'data-short');
        }
        $isInStaff = false;
        foreach ($ranks as $rankname => $users) {
          if (in_array($this->controller->User->getKey('pseudo'), $users))
            $isInStaff = true;
        }

        $havePrize = false;
        $this->Prize = ClassRegistry::init('Obsi.Prizes');
        $findPrizes = $this->Prize->find('first', array('conditions' => array('user_id' => $user['id'])));
        if(!empty($findPrizes)) {
          $prize = $findPrizes['Prizes'];
          $havePrize = true;
        }

        if($isInStaff || $havePrize) {

          $isNotConnected = true;

          $ServerComponent = $this->controller->Server;
          $server_id = Configure::read('ObsiPlugin.server.pvp.id');
          $callConnected = $ServerComponent->call(array('isConnected' => $user['pseudo']), true, $server_id);
          if(isset($callConnected['isConnected']) && $callConnected['isConnected'] == "true") {
            $isNotConnected = false;
          }

        }

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
        'ip',
        'isInStaff',
        'isNotConnected',
        'havePrize',
        'prize'
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

      /*
        Notifications de remboursement
      */
      $RefundsNotificationModel = ClassRegistry::init('Obsi.RefundsNotification');
      $findRefundNotif = $RefundsNotificationModel->find('first', array('conditions' => array('user_id' =>  $user['id'])));
      if(!empty($findRefundNotif)) {

        $RefundsModel = ClassRegistry::init('Obsi.RefundHistory');
        $findRefund = $RefundsModel->find('first', array('conditions' => array('id' => $findRefundNotif['RefundsNotification']['refund_id'])));
        if(!empty($findRefund)) {

          $RefundsNotificationModel->delete($findRefundNotif['RefundsNotification']['id']);

          $msg = 'Vous avez été remboursé de '.$findRefund['RefundHistory']['added_money'].' PB pour vos achats de kits au cours des dernières versions !';
          ModuleComponent::$vars['RefundNotification'] = $msg;

        }
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
      $db->fetchAll('INSERT INTO `joueurs`( `profileid`, `user_pseudo`, `user_mdp`, `is_register_v6`) VALUES (:profileid,:user_pseudo,:user_mdp, 1)', array(
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
    ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
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
    /*
      Le mot de passe edit sur l'auth
    */
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
    /*
      Ptite vérif pour éviter de niquer les quotas de PSC, hein Wave <3
    */
    $configuredUserCanBypassUserEditMoney = Configure::read('Obsi.users.bypass.user.edit.money');
    if(isset($event->data['data']['money']) && !in_array($this->controller->User->getKey('id'), $configuredUserCanBypassUserEditMoney)) {

      // On récupère la money actuelle du joueur edit
      $findUser = $this->controller->User->find('first', array('fields' => array('money', 'pseudo'), 'conditions' => array('id' => $event->data['user_id'])));
      $user_money = $findUser['User']['money'];

      if($user_money < $event->data['data']['money']) {
        file_put_contents(ROOT.DS.'app'.DS.'tmp'.DS.'logs'.DS.'obsi.log', file_get_contents(ROOT.DS.'app'.DS.'tmp'.DS.'logs'.DS.'obsi.log')."\n[".date('Y-m-d H:i:s')."] ".$this->controller->User->getKey('pseudo')." a tenté de modifié les crédits de ".$findUser['User']['pseudo']);
        echo json_encode(array('statut' => false, 'msg' => 'Vous ne pouvez pas ajouter des crédits boutique de cet utilisateur !'));
        $event->stopPropagation();
        return false;
      }

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
