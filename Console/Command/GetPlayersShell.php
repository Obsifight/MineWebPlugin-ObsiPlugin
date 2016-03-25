<?php
class GetPlayersShell extends AppShell {

	public $uses = array('Obsi.CountPlayersLog'); //Models

    public function main() {

      App::uses('ComponentCollection', 'Controller');
      App::uses('ServerComponent', 'Controller/Component');
      $Collection = new ComponentCollection();
      $Server = new ServerComponent($Collection);

      $server_id = Configure::read('ObsiPlugin.server.proxy.id');

      if($Server->online($server_id)) {

        $connected = $Server->call('getPlayerCount', false, $server_id);
        $connected = (is_int($connected)) ? $connected : 0;

      } else {
        $connected = 0;
      }

			$this->CountPlayersLog->create();
			$this->CountPlayersLog->set(array(
				'players_online' => $connected,
				'time' => time()
			));
			$this->CountPlayersLog->save();


	  	$this->out($connected);
    }
}
