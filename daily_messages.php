<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'services/chat_service.php';

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$client = new MongoDB\Client(
    'mongodb+srv://'.urlencode($_ENV['MDB_USER']).':'.urlencode($_ENV['MDB_PASS']).'@'.$_ENV['ATLAS_CLUSTER_SRV'].'/?retryWrites=true&w=majority&appName=Cluster0'
);
$collection = $client->selectCollection($_ENV['MDB_DATABASE'], $_ENV['MDB_COLLECTION']);

$settings = new Settings;

$settings->setAppInfo((new AppInfo)
    ->setApiId($_ENV['TG_APIID'])
    ->setApiHash($_ENV['TG_APIHASH'])
);

$MadelineProto = new API('session.madeline', $settings);
$ChatService = new ChatService($MadelineProto);

$last12hours = strtotime("-12 Hours");
$ChatService->getMessagesFromAllDialogsAndUploadInDb($collection, $last12hours);