<?php

// Callback URL : http://obsifight.fr/sms/newResponse/

class SMSComponent extends Object {

	static private $userLogin = 'street-sylux@live.fr';
	static private $apiKey = '4z4xVyUdQyEO2gr35Jn9Xqig8LrhQSk9';
  //static private $userLogin = 'mineconstruct@gmail.com';
  //static private $apiKey = 'HBk7YbKwHSsgIl4eNx43JROYMj5UE8ZR';
	static private $name = 'ObsiFight';

  function shutdown(&$controller) {}
  function beforeRender(&$controller) {}
  function beforeRedirect() {}
  function initialize(&$controller) {}
  function startup(&$controller) {}

	public static function send($text, $to, $type = 'XXX', $reply = false) {

		$url = 'http://www.octopush-dm.com/api/sms/'.self::makeURL($text, $to, $type, $reply);

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$return = curl_exec($curl);
		curl_close($curl);

		$object = new SimpleXMLElement($return);
		if(class_exists('SimpleXMLElement') && is_object($object)) {
			$statut = self::checkStatut($object);
			return ($statut === true) ? $object : $statut;
		} else {
			return array('statut' => 'error', 'code' => '1', 'message' => 'Class SimpleXMLElement doesn\'t exist or return bad value.', 'data' => '');
		}

	}

	private static function makeURL($text, $to, $type, $reply) {
		$url = '?user_login='.urlencode(self::$userLogin);
		$url .= '&api_key='.self::$apiKey;
		$url .= '&sms_text='.urlencode($text);
		$url .= '&sms_recipients='.urlencode($to);
		$url .= '&sms_type='.$type;
		$url .= '&sms_sender='.self::$name;
		if($reply) {
			$url .= '&with_replies=1';
		}
		return $url;
	}

	private static function checkStatut($object) {
		switch ($object->error_code) {
			case 000:
				return true;
				break;
			case 100:
				return array('statut' => 'error', 'code' => '100', 'message' => 'Not a POST Request.', 'data' => '');
				break;
			case 101:
				return array('statut' => 'error', 'code' => '101', 'message' => 'Bad logins.', 'data' => '');
				break;
			case 102:
				return array('statut' => 'error', 'code' => '102', 'message' => 'Message too long (> 160).', 'data' => '');
				break;
			case 103:
				return array('statut' => 'error', 'code' => '103', 'message' => 'No recipient.', 'data' => '');
				break;
			case 104:
				return array('statut' => 'error', 'code' => '104', 'message' => 'No enought money.', 'data' => '');
				break;
			case 105:
				return array('statut' => 'error', 'code' => '105', 'message' => 'No enought money.', 'data' => '');
				break;
			case 106:
				return array('statut' => 'error', 'code' => '106', 'message' => 'Sender invalid.', 'data' => '');
				break;
			case 107:
				return array('statut' => 'error', 'code' => '107', 'message' => 'Empty message.', 'data' => '');
				break;
			case 108:
				return array('statut' => 'error', 'code' => '108', 'message' => 'Empty login.', 'data' => '');
				break;
			case 109:
				return array('statut' => 'error', 'code' => '109', 'message' => 'Empty password.', 'data' => '');
				break;
			case 110:
				return array('statut' => 'error', 'code' => '110', 'message' => 'Empty recipient.', 'data' => '');
				break;
			case 111:
				return array('statut' => 'error', 'code' => '2', 'message' => 'Empty recipient mode.', 'data' => '');
				break;
			case 112:
				return array('statut' => 'error', 'code' => '112', 'message' => 'Empty quality of message.', 'data' => '');
				break;
			case 113:
				return array('statut' => 'error', 'code' => '113', 'message' => 'Account not valided.', 'data' => '');
				break;
			case 114:
				return array('statut' => 'error', 'code' => '114', 'message' => 'Error with account.', 'data' => '');
				break;
			case 115:
				return array('statut' => 'error', 'code' => '115', 'message' => 'Error with parameters.', 'data' => '');
				break;
			case 116:
				return array('statut' => 'error', 'code' => '116', 'message' => 'The mailing option only works by using a contact list.', 'data' => '');
				break;
			case 117:
				return array('statut' => 'error', 'code' => '117', 'message' => 'Your recipient list contains no correct number.', 'data' => '');
				break;
			case 118:
				return array('statut' => 'error', 'code' => '118', 'message' => 'You must tick one of the two boxes to indicate..', 'data' => '');
				break;
			case 119:
				return array('statut' => 'error', 'code' => '119', 'message' => 'You cannot send SMS with more than 160 characters for this type of SMS.', 'data' => '');
				break;
			case 120:
				return array('statut' => 'error', 'code' => '120', 'message' => 'A SMS with the same request_id has already been sent..', 'data' => '');
				break;
			case 121:
				return array('statut' => 'error', 'code' => '121', 'message' => 'In Premium SMS, the mention "STOP au XXXXX" is mandatory and must belong to your text.', 'data' => '');
				break;
			case 122:
				return array('statut' => 'error', 'code' => '122', 'message' => 'In Standard SMS, the mention "no PUB=STOP" is mandatory and must belong to your text.', 'data' => '');
				break;
			case 123:
				return array('statut' => 'error', 'code' => '123', 'message' => 'The field request_sha1 is missing.', 'data' => '');
				break;
			case 124:
				return array('statut' => 'error', 'code' => '124', 'message' => 'The field request_sha1 does not match.', 'data' => '');
				break;
			case 125:
				return array('statut' => 'error', 'code' => '125', 'message' => 'An undefined error has occurred..', 'data' => '');
				break;
			case 126:
				return array('statut' => 'error', 'code' => '126', 'message' => 'An SMS campaign is already waiting for approval to send.', 'data' => '');
				break;
			case 127:
				return array('statut' => 'error', 'code' => '127', 'message' => 'An SMS campaign is already being processed.', 'data' => '');
				break;
			case 128:
				return array('statut' => 'error', 'code' => '128', 'message' => 'Too many attempts have been made.', 'data' => '');
				break;
			case 129:
				return array('statut' => 'error', 'code' => '129', 'message' => 'Campaign is being built.', 'data' => '');
				break;
			case 130:
				return array('statut' => 'error', 'code' => '130', 'message' => 'Campagne has not been set as finished.', 'data' => '');
				break;
			case 131:
				return array('statut' => 'error', 'code' => '131', 'message' => 'Campaign not found.', 'data' => '');
				break;
			case 132:
				return array('statut' => 'error', 'code' => '132', 'message' => 'Campaign sent.', 'data' => '');
				break;
			case 133:
				return array('statut' => 'error', 'code' => '133', 'message' => 'The user_batch_id has already been used.', 'data' => '');
				break;
			case 150:
				return array('statut' => 'error', 'code' => '150', 'message' => 'No country was found for this prefix.', 'data' => '');
				break;
			case 151:
				return array('statut' => 'error', 'code' => '151', 'message' => 'The recipient country is not part of the countries available.', 'data' => '');
				break;
			case 152:
				return array('statut' => 'error', 'code' => '152', 'message' => 'You cannot send low cost SMS to this country.', 'data' => '');
				break;
			case 153:
				return array('statut' => 'error', 'code' => '153', 'message' => 'The route is congested.', 'data' => '');
				break;
			case 201:
				return array('statut' => 'error', 'code' => '201', 'message' => 'This option is only available on request.', 'data' => '');
				break;
			case 202:
				return array('statut' => 'error', 'code' => '202', 'message' => 'The email account you wish to credit is incorrect.', 'data' => '');
				break;
			case 203:
				return array('statut' => 'error', 'code' => '203', 'message' => 'You already have tokens in use', 'data' => '');
				break;
			case 204:
				return array('statut' => 'error', 'code' => '204', 'message' => 'You specified a wrong token.', 'data' => '');
				break;
			case 205:
				return array('statut' => 'error', 'code' => '205', 'message' => 'The number of text messages you want to transfer is too low.', 'data' => '');
				break;
			case 206:
				return array('statut' => 'error', 'code' => '206', 'message' => 'You may not run campaigns during a credit transfer.', 'data' => '');
				break;
			case 207:
				return array('statut' => 'error', 'code' => '207', 'message' => 'You do not have access to this feature.', 'data' => '');
				break;
			case 208:
				return array('statut' => 'error', 'code' => '208', 'message' => 'Wrong type of SMS.', 'data' => '');
				break;
			case 209:
				return array('statut' => 'error', 'code' => '209', 'message' => 'You are not allowed to send SMS messages to this user.', 'data' => '');
				break;
			case 210:
				return array('statut' => 'error', 'code' => '210', 'message' => 'This email is not specified in any of your sub accounts or affiliate users.', 'data' => '');
				break;
			case 300:
				return array('statut' => 'error', 'code' => '300', 'message' => 'You are not authorized to manage your lists by API.', 'data' => '');
				break;
			case 301:
				return array('statut' => 'error', 'code' => '301', 'message' => 'You have reached the maximum number of lists.', 'data' => '');
				break;
			case 302:
				return array('statut' => 'error', 'code' => '302', 'message' => 'A list with the same name already exists.', 'data' => '');
				break;
		case 303:
				return array('statut' => 'error', 'code' => '303', 'message' => 'The specified list does not exist.', 'data' => '');
				break;
			case 304:
				return array('statut' => 'error', 'code' => '304', 'message' => 'The list is already full.', 'data' => '');
				break;
			case 305:
				return array('statut' => 'error', 'code' => '305', 'message' => 'There are too many contacts in the query.', 'data' => '');
				break;
			case 306:
				return array('statut' => 'error', 'code' => '306', 'message' => 'The requested action is unknown.', 'data' => '');
				break;

			default:
				return array('statut' => 'error', 'code' => '2', 'message' => 'Unknown error.', 'data' => '');
				break;
		}
	}

}
