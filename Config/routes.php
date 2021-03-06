<?php
Router::connect('/signup', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/signup/*', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/register', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/register/*', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/inscription', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));
Router::connect('/inscription/*', array('controller' => 'user', 'action' => 'signup', 'plugin' => 'obsi'));

Router::connect('/user/send-points/disable', array('controller' => 'user', 'action' => 'disableSendPoints', 'plugin' => 'obsi'));
Router::connect('/user/send-points/enable', array('controller' => 'user', 'action' => 'enableSendPoints', 'plugin' => 'obsi'));

Router::connect('/join_us', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/join_us/*', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/nous-rejoindre', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/nous-rejoindre/*', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/p/nous-rejoindre', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));
Router::connect('/p/nous-rejoindre/*', array('controller' => 'page', 'action' => 'join_us', 'plugin' => 'obsi'));

Router::connect('/admin/obsi', array('controller' => 'obsiadmin', 'action' => 'index', 'plugin' => 'obsi', 'prefix' => 'admin'));

Router::connect('/admin/Obsi/*', array('controller' => 'user', 'action' => 'viewEmailUpdateRequests', 'plugin' => 'obsi', 'prefix' => 'admin'));

Router::connect('/obsiapi/ipn/obsiguard/*', array('controller' => 'obsiguard', 'action' => 'ipn', 'plugin' => 'obsi'));
Router::connect('/obsiapi/stats/getVisits', array('controller' => 'stats', 'action' => 'getVisits', 'plugin' => 'obsi'));

/*App::uses('SubdomainRoute', 'Plugin/Obsi/Routing/Route');

Router::connect('/stats', array('controller' => 'stats', 'action' => 'index', 'plugin' => 'obsi', 'subdomain' => 'stats'));
Router::connect('/stats/search/user/*', array('controller' => 'stats', 'action' => 'search_user', 'plugin' => 'obsi', 'subdomain' => 'stats'));
Router::connect('/stats/*', array('controller' => 'stats', 'action' => 'user', 'plugin' => 'obsi', 'subdomain' => 'stats'));
Router::connect('/stats/f/*', array('controller' => 'stats', 'action' => 'faction', 'plugin' => 'obsi', 'subdomain' => 'stats'));*/

Router::connect('/stats', array('controller' => 'stats', 'action' => 'index', 'plugin' => 'obsi'));
Router::connect('/stats/search/user/*', array('controller' => 'stats', 'action' => 'search_user', 'plugin' => 'obsi'));
Router::connect('/stats/*', array('controller' => 'stats', 'action' => 'user', 'plugin' => 'obsi'));
Router::connect('/stats/f/*', array('controller' => 'stats', 'action' => 'faction', 'plugin' => 'obsi'));

Router::connect('/classement-factions', array('controller' => 'FactionsRanking', 'action' => 'index', 'plugin' => 'obsi'));
Router::connect('/factions', array('controller' => 'FactionsRanking', 'action' => 'index', 'plugin' => 'obsi'));
Router::connect('/classement/factions', array('controller' => 'FactionsRanking', 'action' => 'index', 'plugin' => 'obsi'));
Router::connect('/classement-factions/*', array('controller' => 'FactionsRanking', 'action' => 'index', 'plugin' => 'obsi'));
Router::connect('/factions/*', array('controller' => 'FactionsRanking', 'action' => 'index', 'plugin' => 'obsi'));
Router::connect('/classement/factions/*', array('controller' => 'FactionsRanking', 'action' => 'index', 'plugin' => 'obsi'));

Router::connect('/faction/edit', array('controller' => 'FactionsRanking', 'action' => 'edit', 'plugin' => 'obsi'));
Router::connect('/faction/edit/upload/logo', array('controller' => 'FactionsRanking', 'action' => 'uploadLogo', 'plugin' => 'obsi'));

Router::connect('/admin/shop/income-book', array('controller' => 'ShopPurchases', 'action' => 'incomesBook', 'plugin' => 'obsi', 'prefix' => 'admin'));
Router::connect('/admin/infos/did-you-know', array('controller' => 'DidYouKnow', 'action' => 'index', 'plugin' => 'obsi', 'prefix' => 'admin'));
Router::connect('/admin/infos/did-you-know/add', array('controller' => 'DidYouKnow', 'action' => 'add', 'plugin' => 'obsi', 'prefix' => 'admin'));
Router::connect('/admin/infos/did-you-know/delete/:id', array('controller' => 'DidYouKnow', 'action' => 'delete', 'plugin' => 'obsi', 'prefix' => 'admin'));

Router::connect('/user/google-auth', array('controller' => 'google', 'action' => 'auth', 'plugin' => 'obsi'));
Router::connect('/user/youtube/videos', array('controller' => 'google', 'action' => 'manageVideos', 'plugin' => 'obsi'));
Router::connect('/user/youtube/videos/remuneration/:id', array('controller' => 'google', 'action' => 'remuneration', 'plugin' => 'obsi'));
Router::connect('/admin/youtube/videos/remuneration/', array('controller' => 'google', 'action' => 'history', 'plugin' => 'obsi', 'prefix' => 'admin'));

Router::connect('/user/twitter/link', array('controller' => 'twitter', 'action' => 'link', 'plugin' => 'obsi'));
Router::connect('/user/twitter/link/success', array('controller' => 'twitter', 'action' => 'linked', 'plugin' => 'obsi'));
Router::connect('/user/twitter/link/notification', array('controller' => 'twitter', 'action' => 'notification', 'plugin' => 'obsi'));
