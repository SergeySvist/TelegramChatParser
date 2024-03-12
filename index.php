<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'services/userinfo_service.php';
require_once 'services/chat_service.php';
require_once 'services/login_service.php';
require_once 'services/datareplace_service.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Tools;
use MongoDB\Collection;

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


$MadelineProto = new API('session.madeline', $settings);
//Login
autoLogin($MadelineProto);


getMessagesFromAllDialogsAndUploadInDb($MadelineProto, $collection);


