<?php
class CheckMailsShell extends AppShell {

  public $uses = array('User'); //Models

  public function main() {

    $this->out('<info>Init.</info>');

    $users = $this->User->find('all');
    $count = count($users);

    $this->out('<info>All users finded!</info>');
    $this->out('<comment>We have '.$count.' users!</comment>');

    $i = 0;
    foreach ($users as $user) {
      $i++;
      $this->out("[$i/$count] {$user['User']['pseudo']} - {$user['User']['email']}");

      if($this->__isValidEmail($user['User']['email'])) {
        $this->out('<success>Valid email!</success>');
      } else {
        $this->out('<error>Invalid email!</error>');
      }

    }

    $this->out('Done.');

  }

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
          'msg' => 'Bad address syntax.',
          'valid' => false
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

}
