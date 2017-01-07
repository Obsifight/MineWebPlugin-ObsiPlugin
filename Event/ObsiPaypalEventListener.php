<?php
App::uses('CakeEventListener', 'Event');

class ObsiPaypalEventListener implements CakeEventListener {

  private $controller;

  public function __construct($request, $response, $controller) {
    $this->controller = $controller;
  }

  public function implementedEvents() {
    return array(
      'requestPage' => 'paypalIPN'
    );
  }

  private function __apiBan($username) {
    $api = $this->controller->Components->load('Obsi.Api');
    $result = $api->get('/sanction/bans', 'POST', array(
      'reason' => 'Accès au compte restreint : litige en cours',
      'server' => '(global)',
      'type' => 'user',
      'user' => array(
        'username' => $username
      )
    ));
    if (!$result->status || !$result->success) // error
      return false;
    return true;
  }

  private function __apiUnban($username) {
    $api = $this->controller->Components->load('Obsi.Api');
    // get ban id
    $result = $api->get("/user/{$username}/sanctions?limit=3");
    if (!$result->status || !$result->success) // error
      return false;
    $banId = $result->body['bans'][0]['id'];
    // unban
    $result = $api->get("/sanction/bans/{$banId}", 'PUT', array(
      'remove_reason' => 'Litige clos'
    ));
    if (!$result->status || !$result->success) // error
      return false;
    return true;
  }

  public function paypalIPN($event) {
    // check request
    if ($this->controller->params['controller'] != 'payment' || $this->controller->params['action'] != 'ipn' || $this->controller->params['plugin'] != 'shop')
      return;
    // stop event propagation
    $event->stopPropagation();
    if (!$this->controller->request->is('post'))
      throw new NotFoundException('Not post');
    // vars
    $data = $this->controller->request->data;
    $txn_id = $data['txn_id'];

    // check user exist
    $user_id = $data['custom'];
    $this->controller->loadModel('User');
    if(!$this->controller->User->exist($user_id))
      throw new InternalErrorException('PayPal : Unknown user');

    // ==================
    // Check authenticity
    // ==================
    // form data
    $IPN = 'cmd=_notify-validate';
    foreach ($this->controller->request->data as $key => $value) {
      $value = urlencode($value);
      $IPN .= "&$key=$value";
    }
    // request to paypal
    $cURL = curl_init();
    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($cURL, CURLOPT_URL, 'https://www.paypal.com/cgi-bin/webscr');
    curl_setopt($cURL, CURLOPT_ENCODING, 'gzip');
    curl_setopt($cURL, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($cURL, CURLOPT_POST, true); // POST back
    curl_setopt($cURL, CURLOPT_POSTFIELDS, $IPN); // the $IPN
    curl_setopt($cURL, CURLOPT_HEADER, false);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURL, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($cURL, CURLOPT_FORBID_REUSE, true);
    curl_setopt($cURL, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($cURL, CURLOPT_TIMEOUT, 60);
    curl_setopt($cURL, CURLINFO_HEADER_OUT, true);
    curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
      'Connection: close',
      'Expect: ',
    ));
    $Response = curl_exec($cURL);
    $Status = (int)curl_getinfo($cURL, CURLINFO_HTTP_CODE);
    curl_close($cURL);
    // handle response
    if(empty($Response) || $Status != 200 || !$Status)
      throw new InternalErrorException('PayPal : Error with PayPal Response');
    if(!preg_match('~^(VERIFIED)$~i', trim($Response)))
      throw new InternalErrorException('PayPal : Paiement not verified');

    // =============
    //  Handle types
    // ============
    // COMPLETED
    if ($data['payment_status'] == 'Completed') {
      // vars
      $amount = $data['mc_gross'];
      // Check currency
      if ($data['mc_currency'] != 'EUR')
        throw new InternalErrorException('PayPal : Bad currency');
      // find offer with this amount
      $this->controller->loadModel('Shop.Paypal');
      $findOffer = $this->controller->Paypal->find('first', array('conditions' => array('price' => $amount)));
      if (empty($findOffer))
        throw new InternalErrorException('PayPal : Unknown offer');
      if ($data['receiver_email'] == $findOffer['Paypal']['email'])
        throw new InternalErrorException('PayPal : Receiver email invalid');
      // check if not already stored in db
      $this->controller->loadModel('Shop.PaypalHistory');
      $findPayment = $this->controller->PaypalHistory->find('first', array('conditions' => array('payment_id' => $txn_id)));
      if (!empty($findPayment))
        throw new InternalErrorException('PayPal : Payment already credited');

      // success, add new sold
      $sold = $this->controller->User->getFromUser('money', $user_id);
      $newSold = floatval($sold + floatval($findOffer['Paypal']['money']));
      $this->controller->User->setToUser('money', $newSold, $user_id);

      // add into history
      $this->controller->HistoryC = $this->controller->Components->load('History');
      $this->controller->HistoryC->set('BUY_MONEY', 'shop', null, $user_id);

      // On l'ajoute dans l'historique des paiements
      $this->controller->PaypalHistory->create();
      $this->controller->PaypalHistory->set(array(
        'payment_id' => $txn_id,
        'user_id' => $user_id,
        'offer_id' => $findOffer['Paypal']['id'],
        'payment_amount' => $amount,
        'credits_gived' => $findOffer['Paypal']['money'],
        'obsi-status' => 'COMPLETED'
      ));
      $this->controller->PaypalHistory->save();
      // notification
      $this->controller->loadModel('Notification');
      $this->controller->Notification->setToUser($this->Lang->get('NOTIFICATION__PAYPAL_IPN_VALIDED'), $user_id);
    }
    // NEW CASE OR REFUND
    else if ($data['payment_status'] == 'Reversed' || $data['payment_status'] == 'Refunded') {
      // find payment
      $this->controller->loadModel('Shop.PaypalHistory');
      $findPayment = $this->controller->PaypalHistory->find('first', array('conditions' => array('payment_id' => $txn_id)));
      if (empty($findPayment))
        throw new NotFoundException('PayPal : Payment not found');
      // find user
      $findUser = $this->User->find('first', array('conditions' => array('id' => $user_id)));
      if (empty($findUser))
        throw new NotFoundException('PayPal : User not found');
      // ban user
      $this->__apiBan($findUser['User']['username']);
      // edit payment_status
      $this->controller->PaypalHistory->read(null, $findPayment['PaypalHistory']['id']);
      $data = array(
        'obsi-status' => strtoupper($data['payment_status'])
      );
      if ($data['payment_status'] == 'Reversed')
        $data['obsi-case_date'] = date('Y-m-d H:i:s');// add date
      $this->controller->PaypalHistory->set($data);
      $this->controller->PaypalHistory->save();
    }
    // CLOSE CASE
    else if ($data['payment_status'] == 'Canceled_Reversal') {
      // find payment
      $this->controller->loadModel('Shop.PaypalHistory');
      $findPayment = $this->controller->PaypalHistory->find('first', array('conditions' => array('payment_id' => $txn_id)));
      if (empty($findPayment))
        throw new NotFoundException('PayPal : Payment not found');
      // find user
      $findUser = $this->User->find('first', array('conditions' => array('id' => $user_id)));
      if (empty($findUser))
        throw new NotFoundException('PayPal : User not found');
      // unban user
      $this->__apiUnban($findUser['User']['username']);
      // edit payment status
      $this->controller->PaypalHistory->read(null, $findPayment['PaypalHistory']['id']);
      $this->controller->PaypalHistory->set(array(
        'obsi-status' => strtoupper($data['payment_status'])
      ));
      $this->controller->PaypalHistory->save();
    }
    return $this->controller->response->statusCode(200);
  }

}
