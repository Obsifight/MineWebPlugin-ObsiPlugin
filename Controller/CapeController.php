<?php

class CapeController extends ObsiAppController {

  public function upload() {
    $this->autoRender = false;

		if($this->isConnected && $this->User->getKey('cape')) {
			if($this->request->is('post')) {

				$skin_max_size = Configure::read('ObsiPlugin.capes.max-size'); // octet
				$width_max = Configure::read('ObsiPlugin.capes.width-max');
        $height_max = Configure::read('ObsiPlugin.capes.height-max');

				$isValidImg = $this->Util->isValidImage($this->request, array('png'), $width_max, $height_max, $skin_max_size);

				if(!$isValidImg['status']) {
					echo json_encode(array('statut' => false, 'msg' => $isValidImg['msg']));
					exit;
				} else {
					$infos = $isValidImg['infos'];
				}

        $conn_id = @ftp_connect(Configure::read('ObsiPlugin.capes.upload.server'));
        if(!@ftp_login($conn_id, Configure::read('ObsiPlugin.capes.upload.user'), Configure::read('ObsiPlugin.capes.upload.password'))) {
          echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')));
          exit;
        }

        $tmp_name = $this->request->params['form']['image']['tmp_name'];
        $filename = Configure::read('ObsiPlugin.capes.upload.filename');
        $filename = str_replace('{PLAYER}', $this->User->getKey('pseudo'), $filename);

        if(!ftp_put($conn_id, $filename, $tmp_name, FTP_ASCII)) {
          echo json_encode(array('statut' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')));
          exit;
        }

        $this->User->setKey('obsi-cape_uploaded', 1);
	      echo json_encode(array('statut' => true, 'msg' => $this->Lang->get('API__UPLOAD_CAPE_SUCCESS')));

			}

		} else {
			new ForbiddenException();
		}
  }

}
