<?php

class GoogleAnalyticsComponent extends Object {

  private $service = NULL;
  private $profile = NULL;

  function shutdown(&$controller) {}
  function beforeRender(&$controller) {}
  function beforeRedirect() {}
  function initialize(&$controller) {}
  function startup(&$controller) {}

  private function getService() {
    if(empty($this->service)) {

      // Creates and returns the Analytics service object.

      // Load the Google API PHP Client Library.
      require_once ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'google-api-php-client'.DS.'src'.DS.'Google'.DS.'autoload.php';

      // Use the developers console and replace the values with your
      // service account email, and relative location of your key file.
      $service_account_email = '83207578688-o6abklf5hocf6geee9sluihk2md0q4tb@developer.gserviceaccount.com';
      $key_file_location = ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'client_secrets.p12';

      // Create and configure a new client object.
      $client = new Google_Client();
      $client->setApplicationName("ObsiStats");
      $analytics = new Google_Service_Analytics($client);

      // Read the generated client_secrets.p12 key.
      $key = file_get_contents($key_file_location);
      $cred = new Google_Auth_AssertionCredentials(
          $service_account_email,
          array(Google_Service_Analytics::ANALYTICS_READONLY),
          $key
      );
      $client->setAssertionCredentials($cred);
      if($client->getAuth()->isAccessTokenExpired()) {
        $client->getAuth()->refreshTokenWithAssertion($cred);
      }

    } else {
      $analytics = $this->service;
    }
    return $analytics;
  }

  private function getFirstprofileId(&$analytics) {
    if(empty($this->profile)) {
      // Get the user's first view (profile) ID.

      // Get the list of accounts for the authorized user.
      $accounts = $analytics->management_accounts->listManagementAccounts();

      if (count($accounts->getItems()) > 0) {
        $items = $accounts->getItems();
        $firstAccountId = $items[0]->getId();

        // Get the list of properties for the authorized user.
        $properties = $analytics->management_webproperties
            ->listManagementWebproperties($firstAccountId);

        if (count($properties->getItems()) > 0) {
          $items = $properties->getItems();
          $firstPropertyId = $items[0]->getId();

          // Get the list of views (profiles) for the authorized user.
          $profiles = $analytics->management_profiles
              ->listManagementProfiles($firstAccountId, $firstPropertyId);

          if (count($profiles->getItems()) > 0) {
            $items = $profiles->getItems();

            // Return the first view (profile) ID.
            return $items[0]->getId();

          } else {
            //throw new Exception('No views (profiles) found for this user.');
            return false;
          }
        } else {
          //throw new Exception('No properties found for this user.');
          return false;
        }
      } else {
        //throw new Exception('No accounts found for this user.');
        return false;
      }
    } else {
      return $this->profile;
    }
  }

  public function getVisitsFromTo($from, $to) {

    $analytics = $this->getService();
    $profileId = $this->getFirstProfileId($analytics);

    $result = $analytics->data_ga->get(
          'ga:' . $profileId,
          $from,
          $to,
          'ga:sessions');
    return $this->getResult($result);
  }

  public function getVisitsOf($day) {
    return $this->getVisitsFromTo($day, $day);
  }

  private function getResult(&$results) {
    if (count($results->getRows()) > 0) {
      $profileName = $results->getProfileInfo()->getProfileName();
      $rows = $results->getRows();
      $sessions = $rows[0][0];
      return $sessions;
    } else {
      return 0;
    }
  }

}
