<?php
namespace Levu;

require_once('lib/bootstrap.inc.php');

Templating::instance()->addAllowedActions([
	'index.get',
	'login.get',
	'login.reply.get',
])->addVars([
//	'twitter_username' => Twitter::screenName(),
])->registerActionDispatcher(function($action, $templating) {
	var_dump($action);
})->run();


