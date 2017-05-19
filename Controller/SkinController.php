<?php

class SkinController extends ObsiAppController {

  public function upload() {
    $this->autoRender = false;

		if($this->isConnected && ($this->User->getKey('vote') >= 3 || $this->User->getKey('skin'))) {
			if($this->request->is('post')) {

				$skin_max_size = Configure::read('ObsiPlugin.skins.max-size'); // octet
				$width_max = Configure::read('ObsiPlugin.skins.width-max');
        $height_max = Configure::read('ObsiPlugin.skins.height-max');

				$isValidImg = $this->Util->isValidImage($this->request, array('png'), $width_max, $height_max, $skin_max_size);

				if(!$isValidImg['status']) {
					echo json_encode(array('statut' => false, 'msg' => $isValidImg['msg']));
					exit;
				} else {
					$infos = $isValidImg['infos'];
				}

        $conn_id = @ftp_connect(Configure::read('ObsiPlugin.skins.upload.server'));
        if(!@ftp_login($conn_id, Configure::read('ObsiPlugin.skins.upload.user'), Configure::read('ObsiPlugin.skins.upload.password'))) {
          echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')));
          exit;
        }

        $tmp_name = $this->request->params['form']['image']['tmp_name'];
        $filename = Configure::read('ObsiPlugin.skins.upload.filename');
        $filename = str_replace('{PLAYER}', $this->User->getKey('pseudo'), $filename);

        if(!@ftp_put($conn_id, $filename, $tmp_name, FTP_ASCII)) {
          echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')));
          exit;
        }

        ftp_close($conn_id);

        $this->User->setKey('obsi-skin_uploaded', 1);
	      echo json_encode(array('statut' => true, 'msg' => $this->Lang->get('API__UPLOAD_SKIN_SUCCESS')));

			}

		} else {
			new ForbiddenException();
		}
  }

  public function delete() {
    $this->autoRender = false;

		if($this->isConnected) {

      $conn_id = @ftp_connect(Configure::read('ObsiPlugin.skins.upload.server'));
      if(!@ftp_login($conn_id, Configure::read('ObsiPlugin.skins.upload.user'), Configure::read('ObsiPlugin.skins.upload.password'))) {
        $this->Session->setFlash('Erreur interne lors de la suppression.', 'default.error');
        $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
      }

      $filename = Configure::read('ObsiPlugin.skins.upload.filename');
      $filename = str_replace('{PLAYER}', $this->User->getKey('pseudo'), $filename);

      if(!@ftp_delete($conn_id, $filename)) {
        $this->Session->setFlash('Erreur lors de la suppression.', 'default.error');
        $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
      }

      ftp_close($conn_id);

      $this->User->setKey('obsi-skin_uploaded', 0);

      $this->Session->setFlash('Votre skin a bien été supprimé !', 'default.success');
      $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));

    } else {
      throw new ForbiddenException();
    }
  }


}
