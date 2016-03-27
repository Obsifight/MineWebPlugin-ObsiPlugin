<?php
class TeamspeakController extends ObsiAppController{

	function create() {
    if($this->isConnected && $this->Permissions->can('CREATE_TEAMSPEAK_CHANNEL')) {
		  $this->autoRender = false;

			$server_id = Configure::read('ObsiPlugin.server.pvp.id');

			$getPlayerFaction = $this->Server->call(array('getPlayerFaction' => $this->User->getKey('pseudo')), true, $server_id); // On récupère la faction du joueur
			if(isset($getPlayerFaction['getPlayerFaction']) && !empty($getPlayerFaction['getPlayerFaction']) && $getPlayerFaction['getPlayerFaction'] != "PLAYER_NOT_CONNECTED") { // La requête a réussi & on a trouvé une faction & qu'il est connecté

				$checkFactionLeader = $this->Server->call(array('getFactionLeader' => $getPlayerFaction['getPlayerFaction']), true, $server_id); // On récupère le leader de la faction
				if(isset($checkFactionLeader['getFactionLeader']) && $checkFactionLeader['getFactionLeader'] == $this->User->getKey('pseudo')) { // Si la requête a réussi & c'est le leader

					$this->loadModel('Obsi.TeamspeakChannel'); // on charge le model

					$findUserChannel = $this->TeamspeakChannel->find('first', array('conditions' => array('user_id' => $this->User->getKey('id')))); // On cherche les channels de cet utilisateur
					if(empty($findUserChannel)) { // Il n'a pas de channel faction


			  		require_once(ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'TeamSpeak3'.DS.'TeamSpeak3.php');

						$factionName = $getPlayerFaction['getPlayerFaction'];

			  		// Création du channel principal
						try {
			       	$ts3_VirtualServer = TeamSpeak3::factory("serverquery://".Configure::read('ObsiPlugin.ts.user').":".Configure::read('ObsiPlugin.ts.password')."@".Configure::read('ObsiPlugin.ts.ip')."/?server_port=".Configure::read('ObsiPlugin.ts.port'));
			      	$channel = $ts3_VirtualServer->channelCreate(array(
			        	"channel_name" => '['.$factionName.'] Canal base',
			        	"channel_topic" => "Channel créé depuis le site par : ".$this->User->getKey('pseudo'),
			        	"channel_flag_permanent" => TRUE,
			        	"cpid" => '72'
			      	));
						} catch(Exception $e) {
							$this->log('TS Create channel ['.$factionName.'] error : '.$e->getMessage());
							throw new InternalErrorException('TS Channel Create Error 1');
						}

			    	// Channel secondaire (3)
			      	$i = 0;
			      	while ($i < 2) {
			      		$i++;
								try {
				      		$ts3_VirtualServer->channelCreate(array(
				          	"channel_name" => '['.$factionName.'] Canal libre '.$i,
				          	"channel_topic" => "Channel créé depuis le site par : ".$this->User->getKey('pseudo'),
				          	"channel_flag_permanent" => TRUE,
				          	"cpid" => $channel
				        	));
								} catch(Exception $e) {
									$this->log('TS Create channel ['.$factionName.'] error : '.$e->getMessage());
									throw new InternalErrorException('TS Channel Create Error 2');
								}
			      	}

			    	// Création de la privilegeKey
			     	  $key = $ts3_VirtualServer->privilegeKeyCreate('1', '5', $channel);

			     	// Enregistrement dans la base de donnée
			       	$this->TeamspeakChannel->create();
			       	$this->TeamspeakChannel->set(array(
			       		'cid' => $channel,
			       		'name' => '['.$factionName.'] Canal base',
			       		'user_id' => $this->User->getKey('id')
			       	));
			       	$this->TeamspeakChannel->save();

			     	$this->Session->setFlash('Votre channel à bien été créé ! Voici la privilegeKey pour vous permettre d\'être admin de votre channel : '.$key.' !', 'default.success');
			     	$this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));

					} else {
						$this->Session->setFlash('Vous disposez déjà d\'un channel faction !', 'default.error');
			     	$this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
					}

				} else {
					$this->Session->setFlash('Vous n\'êtes pas le chef de votre faction !', 'default.error');
					$this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
				}

			} else {
				$this->Session->setFlash('Vous n\'êtes pas dans une faction !', 'default.error');
				$this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
			}

    } else {
      throw new ForbiddenException();
    }
	}

}
