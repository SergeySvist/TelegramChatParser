<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'services/userinfo_service.php';
require_once 'services/chat_service.php';
require_once 'services/login_service.php';
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
$collection = $client->selectCollection($_ENV['MDB_DATABASE'], $_ENV['MDB_COLLECTION']);

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

try {
    if (isset($_POST["formtype"])) {
        $MadelineProto = new API('session.madeline', $settings);

        if ($_POST["formtype"] === "phonelogin" && isset($_POST["phone"])) {
            phoneNumberLogin($MadelineProto, $_POST["phone"]);
        } elseif ($_POST["formtype"] === "codelogin" && isset($_POST["code"])) {
            confirmPhoneNumberLogin($MadelineProto, $_POST["code"]);

            $path_to_daily_script = realpath('daily_messages.php');
            $deprecatedStatus = new ShellJob();
            $deprecatedStatus->setCommand("php $path_to_daily_script");
            $deprecatedStatus->setSchedule(new CrontabSchedule('0 */12 * * *'));

            getMessagesFromAllDialogsAndUploadInDb($MadelineProto, $collection);

        }
    }
}
catch (Exception){
    http_response_code(400);
    echo "Еhe entered data is invalid";
}


