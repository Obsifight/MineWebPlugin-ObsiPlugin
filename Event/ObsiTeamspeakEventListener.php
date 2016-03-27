<?php
App::uses('CakeEventListener', 'Event');

class ObsiTeamspeakEventListener implements CakeEventListener {

  private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
      return array(
          'onLoadPage' => 'checkTSChannel',
      );
  }

  public function checkTSChannel($event) {

    if($this->controller->params['controller'] == "user" && $this->controller->params['action'] == "profile" && $this->controller->isConnected) { // On vérifie être sur le profil & être connecté

      // On chage les models & components
      $ServerComponent = $this->controller->Server;
      $server_id = Configure::read('ObsiPlugin.server.pvp.id');

      /*
        On gère l'autorisation pour créer un channel
      */

        $getPlayerFaction = $ServerComponent->call(array('getPlayerFaction' => $this->controller->User->getKey('pseudo')), true, $server_id); // On récupère la faction du joueur
        if(isset($getPlayerFaction['getPlayerFaction']) && !empty($getPlayerFaction['getPlayerFaction']) && $getPlayerFaction['getPlayerFaction'] != "PLAYER_NOT_CONNECTED") { // La requête a réussi & on a trouvé une faction & qu'il est connecté

          $checkFactionLeader = $ServerComponent->call(array('getFactionLeader' => $getPlayerFaction['getPlayerFaction']), true, $server_id); // On récupère le leader de la faction
          if(isset($checkFactionLeader['getFactionLeader']) && $checkFactionLeader['getFactionLeader'] == $this->controller->User->getKey('pseudo')) { // Si la requête a réussi & c'est le leader

            $TeamspeakChannelModel = ClassRegistry::init('Obsi.TeamspeakChannel'); // on charge le model

            $findUserChannel = $TeamspeakChannelModel->find('first', array('conditions' => array('user_id' => $this->controller->User->getKey('id')))); // On cherche les channels de cet utilisateur
            if(empty($findUserChannel)) { // Il n'a pas de channel faction

              ModuleComponent::$vars['canCreateFactionChannel'] = true;
              ModuleComponent::$vars['userFaction'] = $getPlayerFaction['getPlayerFaction'];

            }

          } else {
            $userIsFactionLeader = false;
          }

        } else {
          $userInFaction = false;
        }

      /*
        On gère la suppression automatique si le joueur n'a plus de faction
      */


        if(
          ((isset($userInFaction) && !$userInFaction) || (isset($userIsFactionLeader) && !$userIsFactionLeader))
          && (isset($getPlayerFaction['getPlayerFaction']) && $getPlayerFaction['getPlayerFaction'] != "PLAYER_NOT_CONNECTED")
        ) { // Si il n'a pas de faction OU n'en n'est pas le chef & qu'il est connecté

          $TeamspeakChannelModel = ClassRegistry::init('Obsi.TeamspeakChannel'); // on charge le model

          $findUserChannel = $TeamspeakChannelModel->find('first', array('conditions' => array('user_id' => $this->controller->User->getKey('id')))); // On cherche les channels de cet utilisateur
          if(!empty($findUserChannel)) { // Si il a channel faction

            require_once(ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'TeamSpeak3'.DS.'TeamSpeak3.php');

            // Suppression du channel principal
            try {
              $ts3_VirtualServer = TeamSpeak3::factory("serverquery://".Configure::read('ObsiPlugin.ts.user').":".Configure::read('ObsiPlugin.ts.password')."@".Configure::read('ObsiPlugin.ts.ip')."/?server_port=".Configure::read('ObsiPlugin.ts.port'));
              $ts3_VirtualServer->channelDelete($findUserChannel['TeamspeakChannel']['cid'], true);

              // Suppression bdd
              $TeamspeakChannelModel->delete($findUserChannel['TeamspeakChannel']['id']);

            } catch(Exception $e) {
              $this->controller->log('TS delete channel error : '.$e->getMessage());
            }

          }

        }

      }
  }
}
