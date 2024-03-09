<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Tools;
use MongoDB\Collection;

//Connect to MongoDb
$client = new MongoDB\Client(
    'mongodb+srv://'.urlencode($_ENV['MDB_USER']).':'.urlencode($_ENV['MDB_PASS']).'@'.$_ENV['ATLAS_CLUSTER_SRV'].'/?retryWrites=true&w=majority&appName=Cluster0'
);
$collection = $client->selectCollection($_ENV['MDB_DATABASE'], $_ENV['MDB_COLLECTION']);

//connect to MadelineProto
$settings = (new AppInfo)
    ->setApiId($_ENV['TG_APIID'])
    ->setApiHash($_ENV['TG_APIHASH']);

$MadelineProto = new API('session.madeline', $settings);
//Login
$MadelineProto->start();

//get ids of all user dialogs
$dialogs = $MadelineProto->getDialogIds();
$inserted_count = 0;

foreach ($dialogs as $peer){
    $inserted_count += getMessagesAndUploadInDb($MadelineProto, $collection, $peer);
}

echo "\n\n Count of inserted rows: $inserted_count \n\n";

function getMessagesAndUploadInDb(API $MadelineProto, Collection $collection, int $peer): int{
    $offset_id = 0;
    $inserted_count = 0;
    do {
        //get messages from user dialog
        $messages = $MadelineProto->messages->getHistory([
                'peer' => $peer,
                'offset_id' => $offset_id,
                'offset_date' => 0,
                'add_offset' => 0,
                'limit' => 100,
                'max_id' => 0,
                'min_id' => 0,
                'hash' => 0 ]
        );

        if (count($messages['messages']) == 0) break;

        $offset_id = end($messages['messages'])['id'];

        //insert messages in MongoDb
        $inserted_count += $collection->insertMany($messages['messages'])->getInsertedCount();

        $MadelineProto->sleep(2);
    } while(true);

    return $inserted_count;
}

