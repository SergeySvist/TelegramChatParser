<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'services/userinfo_service.php';
require_once 'services/ChatService.php';
require_once 'services/LoginService.php';
require_once 'services/datareplace_service.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Cron\Job\ShellJob;
use Cron\Schedule\CrontabSchedule;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Tools;
use MongoDB\Collection;

//server configuration to solve an error related to CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: token, Content-Type');
    header('Access-Control-Max-Age: 1728000');
    header('Content-Length: 0');
    header('Content-Type: text/plain');
    die();
}
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

//When using JSON content-type $_POST is empty this to solve it
$_POST = json_decode(file_get_contents("php://input"), true);

//Connect to MongoDb
$client = new MongoDB\Client(
    'mongodb+srv://'.urlencode($_ENV['MDB_USER']).':'.urlencode($_ENV['MDB_PASS']).'@'.$_ENV['ATLAS_CLUSTER_SRV'].'/?retryWrites=true&w=majority&appName=Cluster0'
);
$messages_collection = $client->selectCollection($_ENV['MDB_DATABASE'], $_ENV['MDB_MESSAGES_COLLECTION']);
$users_collection = $client->selectCollection($_ENV['MDB_DATABASE'], $_ENV['MDB_USERS_COLLECTION']);

//connect to MadelineProto
$settings = new Settings;

$settings->setAppInfo((new AppInfo)
    ->setApiId($_ENV['TG_APIID'])
    ->setApiHash($_ENV['TG_APIHASH'])
);

//for replace data in user active devices
//replaceDeviceForAuth($settings, "Telegram Web Test");
/*addHttpProxy($settings,
    [
        'address'  => '0.0.0.0',
        'port'     =>  2343,
    ]);*/
$MadelineProto = new API('session.madeline', $settings);
$LoginService = new LoginService($MadelineProto);
$ChatService = new ChatService($MadelineProto);


//main endpoint
$LoginService->authAndStartParse($ChatService, $users_collection, $messages_collection);

