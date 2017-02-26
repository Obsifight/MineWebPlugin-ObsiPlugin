<?php
class ShopPurchasesController extends ObsiAppController {

  public function admin_incomesBook() {
    if (!$this->isConnected || !$this->Permissions->can('GET_INCOMES_BOOK'))
      throw new ForbiddenException();
    $this->set('title_for_layout', 'Livre des recettes');
    $this->layout = 'admin';

    if ($this->request->is('post') && isset($this->request->data['daterange']) && !empty($this->request->data['daterange'])) {
      $range = explode(' - ', $this->request->data['daterange']);
      // setup dates
      $startDate = $range[0];
      $endDate = $range[1];
      // get history
      $payments = $this->__generateIncomesBook($startDate.' 00:00:00', $endDate.' 00:00:00');
      // csv data
      $csvData = array(
        array('Date', 'Type de paiement', 'Utilisateur', 'Points crédités', 'Montant brut', 'Taxes', 'Montant net')
      );
      $totalNet = 0;
      foreach ($payments as $payment) {
        $totalNet += $payment['amount_net'];
        $csvData[] = array_values($payment);
      }
      // total
      $csvData[] = array('', '', '', '', '', '', $totalNet.' €');
      // generate and download it
      $this->autoRender = false;
      $this->__arrayToCsvDownload($csvData, 'incomes-book-' . str_replace('-', '', $startDate) . '-' . str_replace('-', '', $endDate) . '.csv');
    }
  }

  private function __arrayToCsvDownload($array, $filename = 'incomes-book.csv', $delimiter = ',') {
    // open raw memory as file so no temp files needed, you might run out of memory though
    $f = fopen('php://memory', 'w');
    // loop over the input array
    foreach ($array as $line) {
      // generate csv lines from the inner arrays
      fputcsv($f, $line, $delimiter);
    }
    // reset the file pointer to the start of the file
    fseek($f, 0);
    // tell the browser it's going to be a csv file
    header('Content-Type: application/csv');
    // tell the browser we want to save it instead of displaying it
    header('Content-Disposition: attachment; filename="'.$filename.'";');
    // make php send the generated csv lines to the browser
    fpassthru($f);
  }

  private function __generateIncomesBook($rangeStart, $rangeEnd) {
// Date
// Type de paiement
//  - Paypal
//  - Stripe
//  - Paymill
//  - HipayWallet
//  - Paysafecard
// Utilisateur (Pseudo/ID)
// Points crédités
// Montant brut (payé par l'utilisateur)
// Montant des taxes
// Montant net (reçu par le vendeur)
    $payments = array();

    /* =====
      PAYPAL
    ======== */
    $this->loadModel('Shop.PaypalHistory');
    $this->loadModel('Shop.Paypal');
    $findPaypalPayments = $this->PaypalHistory->find('all', array('recursive' => 1, 'conditions' => array('PaypalHistory.created >=' => $rangeStart, 'PaypalHistory.created <=' => $rangeEnd)));
    foreach ($findPaypalPayments as $payment) { // for each payment
      // calcul fees
      $amount_gross = $payment['PaypalHistory']['payment_amount'];
      $fees = (3.4 / 100) * ($amount_gross + 0.25); // 3.4% + 0.25 ctes
      $fees += 0.25;
      $fees = round($fees, 2);
      // push into result array
      $payments[] = array(
        'date' => $payment['PaypalHistory']['created'],
        'type' => 'Paypal',
        'user' => $payment['User']['pseudo'] . ' (ID: ' . $payment['User']['id'] . ')',
        'credits' => $payment['PaypalHistory']['credits_gived'],
        'amount_gross' => $amount_gross,
        'fees' => $fees,
        'amount_net' => $amount_gross - $fees
      );
    }

    /* =====
      STRIPE
    ======== */
    $this->loadModel('ShopPlus.StripeHistory');
    $findStripePayments = $this->StripeHistory->find('all', array('recursive' => 1, 'conditions' => array('StripeHistory.created >=' => $rangeStart, 'StripeHistory.created <=' => $rangeEnd)));
    foreach ($findStripePayments as $payment) { // for each payment
      // calcul fees
      $amount_gross = $payment['StripeHistory']['amount'];
      $fees = (1.4 / 100) * $amount_gross; // 1.4%
      $fees += 0.25; // + 0.25 ctes
      $fees = round($fees, 2);
      // push into result array
      $payments[] = array(
        'date' => $payment['StripeHistory']['created'],
        'type' => 'Stripe',
        'user' => $payment['User']['pseudo'] . ' (ID: ' . $payment['User']['id'] . ')',
        'credits' => $payment['StripeHistory']['credits'],
        'amount_gross' => $amount_gross,
        'fees' => $fees,
        'amount_net' => $amount_gross - $fees
      );
    }

    /* =====
      PAYMILL
    ======== */
    $this->loadModel('ShopPlus.PaymillHistory');
    $findPaymillPayments = $this->PaymillHistory->find('all', array('recursive' => 1, 'conditions' => array('PaymillHistory.created >=' => $rangeStart, 'PaymillHistory.created <=' => $rangeEnd)));
    foreach ($findPaymillPayments as $payment) { // for each payment
      // calcul fees
      $amount_gross = $payment['PaymillHistory']['amount'];
      $fees = (2.95 / 100) * $amount_gross; // 2.95%
      $fees += 0.28; // + 0.25 ctes
      $fees = round($fees, 2);
      // push into result array
      $payments[] = array(
        'date' => $payment['PaymillHistory']['created'],
        'type' => 'Paymill',
        'user' => $payment['User']['pseudo'] . ' (ID: ' . $payment['User']['id'] . ')',
        'credits' => $payment['PaymillHistory']['credits'],
        'amount_gross' => $amount_gross,
        'fees' => $fees,
        'amount_net' => $amount_gross - $fees
      );
    }

    /* =====
      HIPAY WALLET
    ======== */
    $this->loadModel('ShopPlus.HipayWalletHistory');
    $findPaymillPayments = $this->HipayWalletHistory->find('all', array('recursive' => 1, 'conditions' => array('HipayWalletHistory.created >=' => $rangeStart, 'HipayWalletHistory.created <=' => $rangeEnd)));
    foreach ($findPaymillPayments as $payment) { // for each payment
      // calcul fees
      $amount_gross = $payment['HipayWalletHistory']['amount'];
      $fees = (2 / 100) * $amount_gross; // 2%
      $fees += 0.25; // + 0.25 ctes
      $fees = round($fees, 2);
      // push into result array
      $payments[] = array(
        'date' => $payment['HipayWalletHistory']['created'],
        'type' => 'HipayWallet',
        'user' => $payment['User']['pseudo'] . ' (ID: ' . $payment['User']['id'] . ')',
        'credits' => $payment['HipayWalletHistory']['credits'],
        'amount_gross' => $amount_gross,
        'fees' => $fees,
        'amount_net' => $amount_gross - $fees
      );
    }

    /* =====
      PAYSAFECARD
    ======== */
    $this->loadModel('Paysafecard.PaymentHistory');
    $findPaysafecardPayments = $this->PaymentHistory->find('all', array('recursive' => 1, 'conditions' => array('PaymentHistory.created >=' => $rangeStart, 'PaymentHistory.created <=' => $rangeEnd)));
    foreach ($findPaysafecardPayments as $payment) { // for each payment
      // calcul fees
      $amount_gross = $payment['PaymentHistory']['amount'];
      $fees = (15 / 100) * $amount_gross; // 15%
      $fees = round($fees, 2);
      // push into result array
      $payments[] = array(
        'date' => $payment['PaymentHistory']['created'],
        'type' => 'Paysafecard',
        'user' => $payment['User']['pseudo'] . ' (ID: ' . $payment['User']['id'] . ')',
        'credits' => $payment['PaymentHistory']['credits_gived'],
        'amount_gross' => $amount_gross,
        'fees' => $fees,
        'amount_net' => $amount_gross - $fees
      );
    }

    /* =====
      ORDER
    ======== */
    usort($payments, function($a, $b) {
      $ad = new DateTime($a['date']);
      $bd = new DateTime($b['date']);

      if ($ad == $bd) return 0;
      return $ad < $bd ? -1 : 1;
    });
    return $payments;
  }

}
