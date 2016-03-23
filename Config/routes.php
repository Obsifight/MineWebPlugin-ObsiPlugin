<?php
Router::connect('/signup', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/signup/*', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/register', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/register/*', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/inscription', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/inscription/*', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));

Router::connect('/join_us', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/join_us/*', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/nous-rejoindre', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/nous-rejoindre/*', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/p/nous-rejoindre', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/p/nous-rejoindre/*', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));

Router::connect('/getHeadSkin/*', array('controller' => 'ObsiAPI', 'action' => 'getHeadSkin', 'plugin' => 'obsi'));

Router::connect('/admin/Obsi/*', array('controller' => 'user', 'action' => 'viewEmailUpdateRequests', 'plugin' => 'obsi', 'prefix' => 'admin'));

Router::connect('/obsiapi/ipn/obsiguard/*', array('controller' => 'obsiguard', 'action' => 'ipn', 'plugin' => 'obsi'));
Router::connect('/obsiapi/stats/getVisits', array('controller' => 'stats', 'action' => 'getVisits', 'plugin' => 'obsi'));


Router::connect('/stats', array('controller' => 'stats', 'action' => 'index', 'plugin' => 'obsi'));
Router::connect('/stats/search/user/*', array('controller' => 'stats', 'action' => 'search_user', 'plugin' => 'obsi'));
Router::connect('/stats/*', array('controller' => 'stats', 'action' => 'user', 'plugin' => 'obsi'));
Router::connect('/stats/f/*', array('controller' => 'stats', 'action' => 'faction', 'plugin' => 'obsi'));
