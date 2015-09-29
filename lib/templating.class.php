<?php
namespace Levu;

require_once('mustache.php/src/Mustache/Autoloader.php');

\Mustache_Autoloader::register();

class Templating extends \Mustache_Engine {
	public $vars = [];
	public $allowed_actions = [];
	protected $action_dispatcher = null;

	public function flash($message, $ft = null, $action = null, $params = null) {
		$ft = is_null($ft) ? '' : '&ft=' . (($ft === true) ? '+' : '-');
		if (!is_null($action)) {
			$ft .= '&action=' . urlencode($action);
			if (!is_null($params)) $ft .= '&' . $params;
		}
		header("Location: " . $_SERVER['PHP_SELF'] . '?flash=' . urlencode($message) . $ft);
	}

	public static function redirect($action = '') {
		header('Location: ' . $_SERVER['PHP_SELF'] . '?action=' . urlencode($action));
		die;
	}

	public function action() {
		$action = '';
		$m = strtolower($_SERVER['REQUEST_METHOD']);
		if (!isset($_GET['action'])) {
			$action = 'index.get';
		} else {
			if ($_GET['action'] == '') $_GET['action'] = 'index';
			if (!in_array($_GET['action'].'.'.$m, $this->allowed_actions)) {
				die;
			}
			$action = $_GET['action'].'.'.$m;
		}
		return $action;
	}

	public function ensure_files() {
		$e = ensure($_FILES, func_get_args());
		if (!$e) {
			$this->flash("Fehlerhafter Aufruf", false);
			die;
		}
	}

	public function ensure_get() {
		$e = ensure($_GET, func_get_args());
		if (!$e) {
			$this->flash("Fehlerhafter Aufruf", false);
			die;
		}
	}

	public function ensure_post() {
		$e = ensure($_POST, func_get_args());
		if (!$e) {
			$this->flash("Fehlerhafter Aufruf", false);
			die;
		}
	}

	public function run() {
		if ($this->action_dispatcher instanceof \Closure) {
			$this->action_dispatcher->__invoke($this->action(), $this);
		}
		echo $this->render($this->action(), $this->vars);
	}	

	public function addVars($vars) {
		$this->vars = array_merge($this->vars, $vars);
		return $this;
	}

	public function addAllowedActions($actions) {
		$this->allowed_actions = array_unique(array_merge($this->allowed_actions, $actions));
		return $this;
	}

	public function registerActionDispatcher(callable $f) {
		$this->action_dispatcher = $f;
		return $this;
	}

	public function __construct($options = []) {
		if (isset($options['pragmas'])) {
			$options['pragmas'] = array_unique(array_merge($options['pragmas'], [Templating::PRAGMA_BLOCKS, Templating::PRAGMA_FILTERS]));
		} else {
			$options['pragmas'] = [Templating::PRAGMA_BLOCKS, Templating::PRAGMA_FILTERS];
		}
		if (!isset($options['loader'])) {
			$options['loader'] = new \Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/../views', [
				'extension' => '.html',
			]);
		}

		parent::__construct($options);

		if (isset($_GET['flash'])) {
			$this->vars['flashtype'] = isset($_GET['ft']) ? ($_GET['ft'] == '-') ? 'danger' : 'success'  : 'info';
			$this->vars['flash'] = $_GET['flash'];
			$this->vars['flashaction'] = substr($action, 0, strrpos($action, '.'));
			$params = '';
			foreach($_GET as $k=>$v) {
				if (($k != "flash") && ($k != "ft") && ($k != "action")) {
					$params .= urlencode($k) ."=". urlencode($v) . "&";
				}
			}
			$this->vars['flashparams'] = $params;
		}

		$this->vars['app_name'] = APP_NAME;

		$this->addHelper('plurals', function($val) {return ($val != 1) ? 's' : '';});
		$this->addHelper('count', function($val) {return count($val);});
		$this->addHelper('boolify', function($val) {return !!$val;});
		$this->addHelper('german_date', function($val) {$d = new DateTime($val); return $d->format('j. F Y H:i');});
		$this->addHelper('urlencode', function($val) {return urlencode($val);});
		$this->addHelper('nl2br', function($val) {return nl2br($val);});
		$this->addHelper('trim', function($val) {return trim($val);});
		$this->addHelper('htmlspecialchars', function($val) {return htmlspecialchars($val);});
		$this->addHelper('humanreadable_number', function($val) {return ($val > 1000) ? (($val > 1000000) ? ((int) ($val / 1000000)) . "M" : ((int) ($val / 1000)) . "K") : $val;});
		$this->addHelper('if_differ_from_humanreadable', function($val) {return ($val > 1000) ? $val : '';});
		$this->addHelper('duration_to_repeation', function($val) {
			if ($val == 3600)
				return 'stündlich';
			if ($val == 86400)
				return 'täglich';
			if ($val == 604800)
				return 'wöchentlich';
			if ($val == 2419200)
				return 'vierwöchentlich';
			return 'alle ' . $val . ' Sekunden';
		});
	}


	protected static $_instance = null;

	public static function instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new static();
		}
		return self::$_instance;
	}
}

