<?php

use danog\MadelineProto\API;
use MongoDB\Collection;

function getAllUserDialogs(API $MadelineProto): array
{
    return $MadelineProto->getDialogIds();
}

function getMessagesFromAllDialogsAndUploadInDb(API $MadelineProto, Collection $collection): int
{
    $dialogs = getAllUserDialogs($MadelineProto);
    $inserted_count = 0;

    foreach ($dialogs as $peer){
        $inserted_count += getMessagesAndUploadInDb($MadelineProto, $collection, $peer);
    }

    return $inserted_count;
}

function getMessagesAndUploadInDb(API $MadelineProto, Collection $collection, int $peer): int
{
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
