<?php

class UserController extends ObsiAppController {

  /*
    Obsiguard Admin
  */

    function admin_deleteIPObsiguard($user_id, $ip_id) {
      $this->autoRender = false;
      if($this->isConnected && $this->User->isAdmin()) {
        $findUser = $this->User->find('first', array('conditions' => array('id' => $user_id)));
        if(!empty($findUser)) {

          $user_pseudo = $findUser['User']['pseudo'];

          // On se connecte à la db
            App::uses('ConnectionManager', 'Model');
            $con = new ConnectionManager;
            ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
            $db = $con->getDataSource('Auth');

          // On va récupérer les IPs actuelles
            $find = $db->fetchAll('SELECT authorised_ip FROM joueurs WHERE user_pseudo=?', array($user_pseudo));
            if(!empty($find)) {
              // On ajoute l'ip envoyé à notre liste
              $ipList = $find[0]['joueurs']['authorised_ip'];
              $ipList = @unserialize($find[0]['joueurs']['authorised_ip']);
              if(is_array($ipList) && isset($ipList[$ip_id])) { // Si la clé existe
                unset($ipList[$ip_id]); // on la supprime
              }
            } else {
              echo json_encode(array('statut' => false, 'msg' => 'Player not found'));
              return;
            }

          $ipList = serialize($ipList);

          // On va set
            $db->fetchAll('UPDATE joueurs SET authorised_ip=? WHERE user_pseudo=?', array($ipList, $user_pseudo));


          $this->Session->setFlash('L\'ObsiGuard du joueur '.$user_pseudo.' a bien été modifié !', 'default.success');
          $this->redirect(array('controller' => 'user', 'action' => 'edit', 'admin' => true, 'plugin' => false, $user_id));

        } else {
          throw new ForbiddenException();
        }
      }
    }

    function admin_switchObsiguardDynamic($user_id) {
      $this->autoRender = false;
      if($this->isConnected && $this->User->isAdmin()) {
        $findUser = $this->User->find('first', array('conditions' => array('id' => $user_id)));
        if(!empty($findUser)) {

          $user_pseudo = $findUser['User']['pseudo'];

          // On se connecte à la db
            App::uses('ConnectionManager', 'Model');
            $con = new ConnectionManager;
            ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
            $db = $con->getDataSource('Auth');

          // On va récupérer l'état actuel du mode
            $find = $db->fetchAll('SELECT dynamic_ip FROM joueurs WHERE user_pseudo=?', array($user_pseudo));
            if(!empty($find)) {
              $status = ($find[0]['joueurs']['dynamic_ip']) ? 0 : 1;
            } else {
              echo json_encode(array('statut' => false, 'msg' => 'Player not found'));
            }

          // On va set
            $db->fetchAll('UPDATE joueurs SET dynamic_ip=? WHERE user_pseudo=?', array($status, $user_pseudo));

          $this->Session->setFlash('L\'ObsiGuard du joueur '.$user_pseudo.' a bien été modifié !', 'default.success');
          $this->redirect(array('controller' => 'user', 'action' => 'edit', 'admin' => true, 'plugin' => false, $user_id));

        } else {
          throw new ForbiddenException();
        }
      }
    }

    function admin_disableObsiguard($user_id) {
      $this->autoRender = false;
      if($this->isConnected && $this->User->isAdmin()) {
        $findUser = $this->User->find('first', array('conditions' => array('id' => $user_id)));
        if(!empty($findUser)) {

          $user_pseudo = $findUser['User']['pseudo'];

          // On se connecte à la db
            App::uses('ConnectionManager', 'Model');
            $con = new ConnectionManager;
            ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
            $db = $con->getDataSource('Auth');

          // On va mettre NULL comme IP autorisées
            $db->fetchAll('UPDATE joueurs SET authorised_ip=NULL WHERE user_pseudo=?', array($user_pseudo));

          // On le met dans la bdd site
            $this->User->setToUser('obsi-obsiguard_enabled', 0, $user_id);

          $this->Session->setFlash('L\'ObsiGuard du joueur '.$user_pseudo.' a bien été modifié !', 'default.success');
          $this->redirect(array('controller' => 'user', 'action' => 'edit', 'admin' => true, 'plugin' => false, $user_id));

        } else {
          throw new ForbiddenException();
        }
      }
    }

  /*
    Inscription
  */

    function signup() {
  		if(!$this->isConnected) {
  			$this->set('title_for_layout', 'Inscription');
  		} else {
  			$this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
  		}
  	}

    function check_pseudo($pseudo = null) {
  		$this->autoRender = false;
      $this->response->type('json');

      $statut = false;
  		if(!empty($pseudo)) {
  			$count = $this->User->find('count', array('conditions' => array('pseudo' => $pseudo)));
        $statut = (!$count) ? true : false;
  		}
      echo json_encode(array('statut' => $statut));
  	}

    function check_email($email) {
  		$this->autoRender = false;
      $this->response->type('json');

      $statut = true;
      $error = '';

  		$jetable = array('0815.ru0clickemail.com','0-mail.com','0wnd.net','0wnd.org','10minutemail.com','20minutemail.com','2prong.com','3d-painting.com','4warding.com', '4warding.net', '4warding.org', '9ox.net', 'a-bc.net', 'ag.us.to', 'amilegit.com', 'anonbox.net', 'anonymbox.com', 'antichef.com', 'antichef.net', 'antispam.de', 'baxomale.ht.cx', 'beefmilk.com', 'binkmail.com', 'bio-muesli.net', 'bobmail.info', 'bodhi.lawlita.com', 'bofthew.com', 'brefmail.com', 'bsnow.net', 'bugmenot.com', 'bumpymail.com', 'casualdx.com', 'chogmail.com', 'cool.fr.nf', 'correo.blogos.net', 'cosmorph.com', 'courriel.fr.nf', 'courrieltemporaire.com', 'curryworld.de', 'cust.in', 'dacoolest.com', 'dandikmail.com', 'deadaddress.com', 'despam.it', 'despam.it', 'devnullmail.com', 'dfgh.net', 'digitalsanctuary.com', 'discardmail.com', 'discardmail.de', 'disposableaddress.com', 'disposeamail.com', 'disposemail.com', 'dispostable.com', 'dm.w3internet.co.ukexample.com', 'dodgeit.com', 'dodgit.com', 'dodgit.org', 'dontreg.com', 'dontsendmespam.de', 'dump-email.info', 'dumpyemail.com', 'e4ward.com', 'email60.com', 'emailias.com', 'emailias.com', 'emailinfive.com', 'emailmiser.com', 'emailtemporario.com.br', 'emailwarden.com', 'enterto.com', 'ephemail.net', 'explodemail.com', 'fakeinbox.com', 'fakeinformation.com', 'fansworldwide.de',  'fastacura.com','filzmail.com','fixmail.tk','fizmail.com','frapmail.com','garliclife.com','gelitik.in','get1mail.com','getonemail.com','getonemail.net','girlsundertheinfluence.com','gishpuppy.com','goemailgo.com','great-host.in','greensloth.com','greensloth.com','gsrv.co.uk','guerillamail.biz', 'guerillamail.com', 'guerillamail.net', 'guerillamail.org', 'guerrillamail.biz', 'guerrillamail.com','guerrillamail.de','guerrillamail.net','guerrillamail.org','guerrillamailblock.com','haltospam.com','hidzz.com','hotpop.com','ieatspam.eu','ieatspam.info','ihateyoualot.info','imails.info','inboxclean.com','inboxclean.org','incognitomail.com','incognitomail.net','ipoo.org','irish2me.com','jetable.com','jetable.fr.nf','jetable.net','jetable.org','jnxjn.com','junk1e.com','kasmail.com','kaspop.com','klzlk.com','kulturbetrieb.info','kurzepost.de','kurzepost.de','lifebyfood.com','link2mail.net','litedrop.com','lookugly.com','lopl.co.cc','lr78.com','maboard.com','mail.by','mail.mezimages.net','mail4trash.com','mailbidon.com','mailcatch.com','maileater.com','mailexpire.com','mailin8r.com','mailinator.com','mailinator.net','mailinator2.com','mailincubator.com','mailme.lv','mailmetrash.com','mailmoat.com','mailnator.com','mailnull.com','mailzilla.org','mbx.cc','mega.zik.dj','meltmail.com','mierdamail.com','mintemail.com','mjukglass.nu','mobi.web.id','moburl.com', 'moncourrier.fr.nf', 'monemail.fr.nf', 'monmail.fr.nf', 'mt2009.com', 'mx0.wwwnew.eu', 'mycleaninbox.net', 'myspamless.com', 'mytempemail.com', 'mytrashmail.com', 'netmails.net', 'neverbox.com', 'no-spam.ws', 'nobulk.com', 'noclickemail.com', 'nogmailspam.info', 'nomail.xl.cx', 'nomail2me.com', 'nospam.ze.tc', 'nospam4.us', 'nospamfor.us', 'nowmymail.com', 'objectmail.com', 'obobbo.com', 'odaymail.com', 'onewaymail.com', 'ordinaryamerican.net', 'owlpic.com', 'pookmail.com', 'privymail.de', 'proxymail.eu', 'punkass.com', 'putthisinyourspamdatabase.com', 'quickinbox.com', 'rcpt.at', 'recode.me', 'recursor.net', 'regbypass.comsafe-mail.net', 'safetymail.info', 'sandelf.de', 'saynotospams.com', 'selfdestructingmail.com', 'sendspamhere.com', 'sharklasers.com', 'shieldedmail.com', 'shiftmail.com', 'skeefmail.com', 'slopsbox.com', 'slushmail.com', 'smaakt.naar.gravel', 'smellfear.com', 'snakemail.com', 'sneakemail.com', 'sofort-mail.de', 'sogetthis.com', 'soodonims.com', 'spam.la', 'spamavert.com', 'spambob.net', 'spambob.org', 'spambog.com', 'spambog.de', 'spambog.ru', 'spambox.info', 'spambox.us', 'spamcannon.com', 'spamcannon.net', 'spamcero.com', 'spamcorptastic.com', 'spamcowboy.com', 'spamcowboy.net', 'spamcowboy.org', 'spamday.com', 'spamex.com', 'spamfree.eu', 'spamfree24.com', 'spamfree24.de', 'spamfree24.eu', 'spamfree24.info', 'spamfree24.net', 'spamfree24.org', 'spamgourmet.com', 'spamgourmet.net', 'spamgourmet.org', 'spamherelots.com', 'spamhereplease.com', 'spamhole.com', 'spamify.com', 'spaminator.de', 'spamkill.info', 'spaml.com', 'spaml.de', 'spammotel.com', 'spamobox.com', 'spamspot.com', 'spamthis.co.uk', 'spamthisplease.com', 'speed.1s.fr', 'suremail.info', 'tempalias.com', 'tempe-mail.com', 'tempemail.biz', 'tempemail.com', 'tempemail.net', 'tempinbox.co.uk', 'tempinbox.com', 'tempomail.fr', 'temporaryemail.net', 'temporaryinbox.com', 'tempymail.com', 'thankyou2010.com', 'thisisnotmyrealemail.com', 'throwawayemailaddress.com', 'tilien.com', 'tmailinator.com', 'tradermail.info', 'trash-amil.com', 'trash-mail.at', 'trash-mail.com', 'trash-mail.de', 'trash2009.com', 'trashmail.at', 'trashmail.com', 'trashmail.me', 'trashmail.net', 'trashmailer.com', 'trashymail.com', 'trashymail.net', 'trillianpro.com', 'tyldd.com', 'tyldd.com', 'uggsrock.com', 'wegwerfmail.de', 'wegwerfmail.net', 'wegwerfmail.org', 'wh4f.org', 'whyspam.me', 'willselfdestruct.com', 'winemaven.info', 'wronghead.com', 'wuzupmail.net', 'xoxy.net', 'yogamaven.com', 'yopmail.com', 'yopmail.fr', 'yopmail.net', 'yuurok.com', 'zippymail.info', 'zoemail.com');
      if(in_array(explode('@', $email)[1], $jetable)) {
        $statut = false;
        $error = 'Votre email n\'est pas valide !';
      } else {
        $count = $this->User->find('count', array('conditions' => array('email' => $email)));
        if($count > 0) {
          $statut = false;
          $error = 'Votre email est déjà utilisé !';
        }
      }

      echo json_encode(array('statut' => $statut, 'error' => $error));
  	}

  /*
    Fonction pour valider le numéro & le parse depuis le JS
  */

    private function isValidNumberPhoneAndParse($number_phone) {

      $number_phone = str_replace('plus', '+', $number_phone);

      if(preg_match('/^(0|\+33|0033)[1-9][0-9]{8}$/', $number_phone)) { // Si il fais partie de 0033 ou +33 ou rien

        if(preg_match('/^\+33/', $number_phone)) { // si c'est +33
          $number_phone_parsed = $number_phone; // On change pas
        } elseif(preg_match('/^0033/', $number_phone)) { // si c'est 0033
          $number_phone_parsed = '+33'.substr($number_phone, 4);
        } else {
          $number_phone_parsed = '+33'.substr($number_phone, 1); // c'est un zéro
        }

        return $number_phone_parsed;
      }
      return false;

    }

  /*
    Envoie du code de confirmation pour la validation du SMS
  */

    function sendSMSConfirmCode($number_phone = null) {
      $this->response->type('json');
      $this->autoRender = false;

      if($this->request->is('ajax') && $this->isConnected) {

        if(!empty($number_phone)) {

          $number_phone = $this->isValidNumberPhoneAndParse($number_phone);

          if($number_phone !== false) {

            $find = $this->User->find('first', array('conditions' => array('obsi-number_phone' => $number_phone)));
            if(empty($find)) {

              // On génère le code
                $key = substr(sha1(rand().date('hYmsdi')), 10, 5);

              // On envoie le SMS
                App::uses('SMSComponent', 'Obsi.Controller/Component');
                $sms = "Voici le code pour confirmer ton numéro de téléphone sur notre serveur ObsiFight ! \n".$key."\nSTOP au XXXXX";
                $send = SMSComponent::send($sms, $number_phone, 'FR');
                //$send = array('statut' => 'error', 'message' => 'Wait dev !', 'code' => '100');
                if($send['statut'] == "error") {
                  $this->log('SMS Send Error ('.$send['code'].') : '.$send['message']);
                  echo json_encode(array('statut' => false, 'msg' => 'Une erreur est survenue lors de l\'envoie du SMS.'));
                  return;
                }

              // On enregistre dans une table le SMS + son code et l'utilisateur
                $this->loadModel('Obsi.SmsConfirmationCode');
                // On supprime les anciens
                $this->SmsConfirmationCode->deleteAll(array('user_id' => $this->User->getKey('id')));
                // On créé le nouveau
                $this->SmsConfirmationCode->create();
                $this->SmsConfirmationCode->set(array(
                  'user_id' => $this->User->getKey('id'),
                  'code' => $key,
                  'number_phone' => $number_phone
                ));
                $this->SmsConfirmationCode->save();

              // On dis au JS que c'est correct
                echo json_encode(array('statut' => true));

            } else {
              echo json_encode(array('statut' => false, 'msg' => 'Ce numéro est déjà enregistré.'));
            }

          } else {
            echo json_encode(array('statut' => false, 'msg' => 'Le numéro est invalide.'));
          }

        } else {
          echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
        }

      } else {
        throw new ForbiddenException();
      }
    }

  /*
    Enregistrement du numéro sur le profil
  */

    function saveProfile() {

      $this->autoRender = false;

      if($this->request->is('ajax') && $this->isConnected) {

        if(!empty($this->request->data['number_phone'])) {

          if(!empty($this->request->data['confirm_code'])) {

            $this->request->data['number_phone'] = $this->isValidNumberPhoneAndParse($this->request->data['number_phone']);

            if($this->request->data['number_phone'] !== false) {

              $this->loadModel('Obsi.SmsConfirmationCode');
              $findCode = $this->SmsConfirmationCode->find('first', array('conditions' => array('user_id' => $this->User->getKey('id'), 'number_phone' => $this->request->data['number_phone'], 'code' => $this->request->data['confirm_code'])));
              if(!empty($findCode)) {

                $this->User->setKey('obsi-number_phone', $this->request->data['number_phone']);
                $this->SmsConfirmationCode->deleteAll(array('user_id' => $this->User->getKey('id')));

                echo json_encode(array('statut' => true, 'msg' => 'Votre numéro a bien été enregistré !'));

              } else {
                echo json_encode(array('statut' => false, 'msg' => 'Le code de validation est invalide ou ne correspond pas à ce numéro ou cet utilisateur.'));
              }

            } else {
              echo json_encode(array('statut' => false, 'msg' => 'Le numéro est invalide.'));
            }

          } else {
            echo json_encode(array('statut' => false, 'msg' => 'Vous devez d\'abord envoyer un code de confirmation a votre numéro et remplir le champ !'));
          }

        } else {
          echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
        }

      } else {
        throw new ForbiddenException();
      }

    }

  /*
    Suppression du numéro de téléphone
  */

    function deletePhoneNumber() {
      $this->autoRender = false;

      if($this->isConnected) {

        $this->User->setKey('obsi-number_phone', null);

        $this->Session->setFlash('Votre numéro de téléphone a bien été supprimé de nos base de données !', 'default.success');
        $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));

      } else {
        throw new ForbiddenException();
      }
    }

  /*
    Envoie de points custom (demande de mot de passe)
  */

    function sendPoints() {
      $this->autoRender = false;
  		if($this->isConnected) {
  			if($this->request->is('ajax')) {

  				if(!empty($this->request->data['to']) && !empty($this->request->data['howMany']) && !empty($this->request->data['password'])) {

            if(strtolower($this->request->data['to']) != strtolower($this->User->getKey('pseudo')) && intval($this->request->data['to']) != $this->User->getKey('id')) {

              $password = $this->Util->password($this->request->data['password'], $this->User->getKey('pseudo'));
              if($password == $this->User->getKey('password')) {

      					if($this->User->exist($this->request->data['to'])) {

      						$how = intval($this->request->data['howMany']);

      						if($how > 0) {

      							$money_user = $this->User->getKey('money') - $how;

      							if($money_user >= 0) {

                      /*
                        On vérifie qu'il est pas ban
                      */
                      $this->Sanctions = $this->Components->load('Obsi.Sanctions');
                      if(!$this->Sanctions->isBanned($this->Sanctions->getUUID($this->User->getKey('pseudo')))) {

                        /*
                          On vérifie qu'il est son email confirmé
                        */
                        $confirmed = $this->User->getKey('confirmed');
                        if(empty($confirmed) || date('Y-m-d H:i:s', strtotime($confirmed)) == $confirmed) {

          								$this->User->setKey('money', $money_user);
          								$to_money = $this->User->getFromUser('money', $this->request->data['to']) + $how;
          								$this->User->setToUser('money', $to_money, $this->request->data['to']);

          								$this->History->set('SEND_MONEY', 'shop', $this->request->data['to'].'|'.$how);
          								echo json_encode(array('statut' => true, 'msg' => $this->Lang->get('SHOP__USER_POINTS_TRANSFER_SUCCESS'), 'newSold' => $money_user));

                        } else {
                          echo json_encode(array('statut' => false, 'msg' => 'Vous n\'avez pas confirmé votre email ! Vous ne pouvez pas transférez vos points !'));
                        }

                      } else {
                        echo json_encode(array('statut' => false, 'msg' => 'Vous êtes banni ! Vous ne pouvez pas transférez vos points !'));
                      }

      							} else {
      								echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('SHOP__BUY_ERROR_NO_ENOUGH_MONEY')));
      							}

      						} else {
      							echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('SHOP__USER_POINTS_TRANSFER_ERROR_EMPTY')));
      						}

      					} else {
      						echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('USER__ERROR_NOT_FOUND')));
      					}

              } else {
                echo json_encode(array('statut' => false, 'msg' => 'Votre mot de passe est incorrect.'));
              }

            } else {
              echo json_encode(array('statut' => false, 'msg' => 'Vous ne pouvez pas envoyer de points boutique à vous même !'));
            }

  				} else {
  					echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
  				}

  			} else {
  				echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')));
  			}
  		} else {
  			echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('USER__ERROR_MUST_BE_LOGGED')));
  		}
    }

  /*

  */

    private function __isValidEmail($email) {
      if(filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $timeout = 10;

        $url = 'http://mailtester.com/';
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1'; // simule Firefox 4.
        $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
        $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        $header[] = "Accept-Charset: utf-8";
        $header[] = "Accept-Language: fr"; // langue fr.
        $header[] = "Pragma: "; // Simule un navigateur

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array(
          'email' => $email
        ));
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        $response = curl_exec($curl);
        $time = curl_getinfo($curl, CURLINFO_CONNECT_TIME);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        $responsesAvailable = array(
          array(
            'msg' => 'The domain is invalid or no mail server was found for it.',
            'valid' => false,
          ),
          array(
            'msg' => 'E-mail address does not exist on this server',
            'valid' => false,
          ),
          array(
            'msg' => 'Server doesn\'t allow e-mail address verification',
            'valid' => true,
          ),
          array(
            'msg' => 'E-mail address is valid',
            'valid' => true,
          )
        );

        if($code == 200) {
          foreach ($responsesAvailable as $data) {

            if(preg_match('#'.$data['msg'].'#i', $response)) {

              return $data['valid'];

              break;
            }

          }
        } else {
          return true;
        }

      }
      return false;
    }

  /*
    Demande pour changement d'email
  */

    public function requestEmailUpdate() {
      $this->autoRender = false;
      if($this->isConnected) {
        if($this->request->is('ajax')) {

          if(!empty($this->request->data['newEmail']) && !empty($this->request->data['reason'])) {

            if($this->__isValidEmail($this->request->data['newEmail'])) {

              $this->loadModel('Obsi.EmailUpdateRequest');
              // On cherche si il n'a pas déjà fais une demande
                $find = $this->EmailUpdateRequest->find('first', array('conditions' => array('user_id' => $this->User->getKey('id'))));
                if(empty($find)) {

                  // On vérifie que l'email n'est pas déjà pris &/ou demandé
                    $findThisEmailInUsers = $this->User->find('first', array('conditions' => array('email' => $this->request->data['newEmail'])));
                    $findThisEmailInRequests = $this->EmailUpdateRequest->find('first', array('conditions' => array('new_email' => $this->request->data['newEmail'])));
                    if(empty($findThisEmailInUsers) && empty($findThisEmailInRequests)) {

                      $this->EmailUpdateRequest->create();
                      $this->EmailUpdateRequest->set(array(
                        'user_id' => $this->User->getKey('id'),
                        'new_email' => $this->request->data['newEmail'],
                        'reason' => $this->request->data['reason']
                      ));
                      $this->EmailUpdateRequest->save();

                      echo json_encode(array('statut' => true, 'msg' => 'La demande a bien été enregistrée.'));

                    } else {
                      echo json_encode(array('statut' => false, 'msg' => 'Cet email est déjà pris ou a déjà été demandé.'));
                    }

                } else {
                  echo json_encode(array('statut' => false, 'msg' => 'Vous avez déjà effectuée une demande de changement, veuillez patientez que celle-ci soit traitée.'));
                }

            } else {
              echo json_encode(array('statut' => false, 'msg' => 'L\'email n\'est pas valide !'));
            }

          } else {
            echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')));
          }

        } else {
          echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')));
        }
      } else {
        echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('USER__ERROR_MUST_BE_LOGGED')));
      }
    }

    public function admin_viewEmailUpdateRequests() {
      if($this->isConnected AND $this->User->isAdmin()) {

        $this->layout = 'admin';

        $this->set('title_for_layout', 'Gérer les demandes de modification d\'email');

        $this->loadModel('Obsi.EmailUpdateRequest');
        $requests = $this->EmailUpdateRequest->find('all');

        $usersToFind = array();
        foreach ($requests as $key => $value) {
          $usersToFind[] = $value['EmailUpdateRequest']['user_id'];
        }

        $usersByID = array();
        $findUsers = $this->User->find('all', array('conditions' => array('id' => $usersToFind)));
        foreach ($findUsers as $key => $value) {
          $usersByID[$value['User']['id']] = $value['User']['pseudo'];
        }

        $this->set(compact('requests', 'usersByID'));

      } else {
        throw new ForbiddenException();
      }
    }

    public function admin_validEmailUpdateRequest($id = null) {
      $this->autoRender = false;
      if($this->isConnected AND $this->User->isAdmin()) {

        if(!empty($id)) {

          $this->loadModel('Obsi.EmailUpdateRequest');
          $find = $this->EmailUpdateRequest->find('first', array('conditions' => array('id' => $id)));

          if(!empty($find)) {

            $old_user_email = $this->User->getFromUser('email', $find['EmailUpdateRequest']['user_id']);

            // On met l'email à l'utilisateur
              $this->User->setToUser('email', $find['EmailUpdateRequest']['new_email'], $find['EmailUpdateRequest']['user_id']);

            // On lui génère un code de confirmation d'email
              $username = $this->User->getFromUser('pseudo', $find['EmailUpdateRequest']['user_id']);
              $email = $this->User->getFromUser('email', $find['EmailUpdateRequest']['user_id']);

              $confirmCode = substr(md5(uniqid()), 0, 12);

              $emailMsg = $this->Lang->get('EMAIL__CONTENT_CONFIRM_MAIL', array(
                '{LINK}' => Router::url('/user/confirm/', true).$confirmCode,
                '{IP}' => $this->Util->getIP(),
                '{USERNAME}' => $username,
                '{DATE}' => $this->Lang->date(date('Y-m-d H:i:s'))
              ));

              $email = $this->Util->prepareMail(
                $email,
                $this->Lang->get('EMAIL__TITLE_CONFIRM_MAIL'),
                $emailMsg
              )->sendMail();

              $this->User->setToUser('confirmed', $confirmCode, $find['EmailUpdateRequest']['user_id']);

            // On le notifie
              $this->loadModel('Obsi.EmailUpdateRequestResponse');
              $this->EmailUpdateRequestResponse->create();
              $this->EmailUpdateRequestResponse->set(array(
                'user_id' => $find['EmailUpdateRequest']['user_id'],
                'status' => 1
              ));
              $this->EmailUpdateRequestResponse->save();

            // On supprime la demande
              $this->EmailUpdateRequest->delete($find['EmailUpdateRequest']['id']);

            // On le met dans l'historique
              $this->loadModel('Obsi.EmailUpdateHistory');
              $this->EmailUpdateHistory->create();
              $this->EmailUpdateHistory->set(array(
                'user_id' => $find['EmailUpdateRequest']['user_id'],
                'old_email' => $old_user_email,
                'new_email' => $find['EmailUpdateRequest']['new_email'],
                'confirmed_by' => $this->User->getKey('id')
              ));
              $this->EmailUpdateHistory->save();

            // On redirige et préviens l'administrateur
              $this->Session->setFlash('La demande a bien été validé !', 'default.success');
              $this->redirect(array('action' => 'viewEmailUpdateRequests'));

          } else {
            throw new NotFoundException('Empty find');
          }

        } else {
          throw new NotFoundException('No ID');
        }

      } else {
        throw new ForbiddenException();
      }
    }

    public function admin_invalidEmailUpdateRequest($id = null) {
      $this->autoRender = false;
      if($this->isConnected AND $this->User->isAdmin()) {

        if(!empty($id)) {

          $this->loadModel('Obsi.EmailUpdateRequest');
          $find = $this->EmailUpdateRequest->find('first', array('conditions' => array('id' => $id)));

          if(!empty($find)) {

            // On notifie l'utilisateur
              $this->loadModel('Obsi.EmailUpdateRequestResponse');
              $this->EmailUpdateRequestResponse->create();
              $this->EmailUpdateRequestResponse->set(array(
                'user_id' => $find['EmailUpdateRequest']['user_id'],
                'status' => 0
              ));
              $this->EmailUpdateRequestResponse->save();

            // On supprime la demande
              $this->EmailUpdateRequest->delete($find['EmailUpdateRequest']['id']);

            // On redirige et préviens l'administrateur
              $this->Session->setFlash('La demande a bien été invalidé !', 'default.success');
              $this->redirect(array('action' => 'viewEmailUpdateRequests'));

          } else {
            throw new NotFoundException('Empty find');
          }

        } else {
          throw new NotFoundException('No ID');
        }

      } else {
        throw new ForbiddenException();
      }
    }

  /*
    Changement de pseudo
  */

    public function updatePseudo() {
      $this->autoRender = false;
      if($this->isConnected && $this->request->is('ajax')) {

        if($this->User->getKey('obsi-can_update_pseudo')) {

          if(!empty($this->request->data['pseudo']) && !empty($this->request->data['password'])) {

            $password = $this->Util->password($this->request->data['password'], $this->User->getKey('pseudo'));
            if($password == $this->User->getKey('password')) {

              // On vérifie qu'il est valide
              if(preg_match('`^([a-zA-Z0-9-_]{2,16})$`', $this->request->data['pseudo'])) {

                // On vérifie si il est pas déjà pris
                  $find = $this->User->find('first', array('conditions' => array('pseudo' => $this->request->data['pseudo'])));
                  if(empty($find)) {

                    // On vérifie si le pseudo a pas déjà été utilisé
                      $this->loadModel('Obsi.PseudoUpdateHistory');
                      $findInHistory = $this->PseudoUpdateHistory->find('first', array('conditions' => array('old_pseudo' => $this->request->data['pseudo'])));
                      if(empty($findInHistory)) {

                        $old_pseudo = $this->User->getKey('pseudo');

                      // On set les nouvelles données
                        $new_pseudo = $this->request->data['pseudo'];
                        $password = $this->Util->password($this->request->data['password'], $new_pseudo);

                      // On effectue le changement sur notre bdd & on supprime son autorisation
                        $this->User->read(null, $this->User->getKey('id'));
                        $this->User->set(array(
                          'pseudo' => $new_pseudo,
                          'password' => $password,
                          'obsi-can_update_pseudo' => 0
                        ));
                        $this->User->save();

                      // On effectue le changement sur la bdd de l'auth
                        App::uses('ConnectionManager', 'Model');
                        $con = new ConnectionManager;
                        ConnectionManager::create('Auth', Configure::read('Obsi.db.Auth'));
                        $db = $con->getDataSource('Auth');
                        $db->fetchAll('UPDATE joueurs SET user_mdp=:user_mdp,user_pseudo=:user_pseudo WHERE user_pseudo=:old_pseudo', array(
                          'user_mdp' => $password,
                          'user_pseudo' => $new_pseudo,
                          'old_pseudo' => $old_pseudo
                        ));

                      // On log le tout
                        $this->PseudoUpdateHistory->create();
                        $this->PseudoUpdateHistory->set(array(
                          'user_id' => $this->User->getKey('id'),
                          'old_pseudo' => $old_pseudo,
                          'new_pseudo' => $new_pseudo
                        ));
                        $this->PseudoUpdateHistory->save();

                      // On rename son skin et sa cape si besoin
                        if($this->User->getKey('obsi-skin_uploaded') || $this->User->getKey('obsi-cape_uploaded')) {

                          $conn_id = @ftp_connect(Configure::read('ObsiPlugin.skins.upload.server'));
                          if(!@ftp_login($conn_id, Configure::read('ObsiPlugin.skins.upload.user'), Configure::read('ObsiPlugin.skins.upload.password'))) {
                            echo json_encode(array('statut' => false, 'msg' => 'Une erreur est intervenue lors du changement de votre skin et/ou cape.'));
                            return;
                          }

                          if($this->User->getKey('obsi-skin_uploaded')) { // si le gars a déjà upload un skin

                            $old_skin_name = Configure::read('ObsiPlugin.skins.upload.filename');
                            $old_skin_name = str_replace('{PLAYER}', $old_pseudo, $old_skin_name);
                            $new_skin_name = Configure::read('ObsiPlugin.skins.upload.filename');
                            $new_skin_name = str_replace('{PLAYER}', $new_pseudo, $new_skin_name);

                            @ftp_rename($conn_id, $old_skin_name, $new_skin_name);

                          }

                          if($this->User->getKey('obsi-cape_uploaded')) { // si le gars a déjà upload une cape

                            $old_cape_name = Configure::read('ObsiPlugin.capes.upload.filename');
                            $old_cape_name = str_replace('{PLAYER}', $old_pseudo, $old_cape_name);
                            $new_cape_name = Configure::read('ObsiPlugin.capes.upload.filename');
                            $new_cape_name = str_replace('{PLAYER}', $new_pseudo, $new_cape_name);

                            @ftp_rename($conn_id, $old_cape_name, $new_cape_name);

                          }

                        }

                      // On dis au JS que c'est fait
                        echo json_encode(array('statut' => true, 'msg' => 'Le changement a bien été effecuté !'));

                    } else {
                      echo json_encode(array('statut' => false, 'msg' => 'Ce pseudo a déjà été utilisé !'));
                    }

                  } else {
                    echo json_encode(array('statut' => false, 'msg' => 'Ce pseudo est déjà pris !'));
                  }

              } else {
                echo json_encode(array('statut' => false, 'msg' => 'Le pseudo est invalide (caractères spéciaux ou supérieur à 16 caractères) !'));
              }

            } else {
              echo json_encode(array('statut' => false, 'msg' => 'Votre mot de passe est incorrect !'));
            }

          } else {
            echo json_encode(array('statut' => false, 'msg' => 'Vous n\'avez choisi pseudo ou vous n\'avez pas entré de mot de passe !'));
          }

        } else {
          echo json_encode(array('statut' => false, 'msg' => 'Vous n\'êtes pas autorisé à changer de pseudo !'));
        }

      } else {
        throw new ForbiddenException();
      }

    }

  /*
    Se faire switch de serveur
  */

    public function switchServer() {
      if($this->isConnected) {

        $user_pseudo = $this->User->getKey('pseudo');
        $staff = Configure::read('ObsiPlugin.staff');

        $isInStaff = false;
        foreach ($staff as $rank => $users) {
          if(in_array($user_pseudo, $users)) {
            $isInStaff = true;
            break;
          }
        }

        if($isInStaff) {

          $server_id = Configure::read('ObsiPlugin.server.pvp.id');
          $server_bungee_id = Configure::read('ObsiPlugin.server.bungee.id');

          $callConnected = $this->Server->call(array('isConnected' => $user_pseudo), true, $server_id);
          if(isset($callConnected['isConnected']) && $callConnected['isConnected'] == "true") {

            $this->Session->setFlash('Vous être déjà connecté !', 'default.error');
            $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));

          } else {
            $this->Server->call(array('performCommand' => 'send '.$user_pseudo.' srv_pvp'), true, $server_bungee_id);

            $this->Session->setFlash('Vous avez été switch sur le serveur pvp !', 'default.success');
            $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
          }

        } else {
          $this->Session->setFlash('Vous ne faites pas partie du staff !', 'default.error');
          $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
        }

      } else {
        throw new ForbiddenException();
      }
    }

}
