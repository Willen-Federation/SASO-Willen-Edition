<?php
namespace saso;

use saso\framework\Router;
use saso\framework\UserCompiler;

mb_internal_encoding('UTF-8');
const ENV = null;
require_once 'ConfigLoader.php';
$config = ConfigLoader::load();
$fullPath = $config['documentRoot'].$config['programDir'];
require_once 'ClassLoader.php';
spl_autoload_register(ClassLoader::load($config));

session_start();
$authed = isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time();
if($authed){
    $_SESSION['time'] = time();
}
$route = json_decode(file_get_contents($fullPath.'request.json'), true);
$flow = json_decode(file_get_contents($fullPath.'flow.json'), true);
$installerRoute = $fullPath.'installer/installer.json';
if(file_exists($installerRoute)) {
    $installer = json_decode(file_get_contents($installerRoute), true);
} else {
    $installer = [];
}

$input = new UserCompiler(
    $_SERVER['REQUEST_URI'],
    json_decode(file_get_contents('php://input'), true)??$_POST,
    $config,
    $authed,
    new \DateTime(),
);
$router = new Router(
    array_merge($route, $installer),
);
$view = $router->route($input)->flow($flow);
$view->display();
$view->getContent()($view);
