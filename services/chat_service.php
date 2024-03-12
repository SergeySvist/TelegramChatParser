<?php

use danog\MadelineProto\AbstractAPI;
use danog\MadelineProto\API;
use MongoDB\Collection;

function getAllUserDialogs(AbstractAPI $MadelineProto): array
{
    return $MadelineProto->getFullDialogs();
}

function getMessagesFromAllDialogsAndUploadInDb(AbstractAPI $MadelineProto, Collection $collection, int $min_date = 0): int
{
    $dialogs = getAllUserDialogs($MadelineProto);
    $inserted_count = 0;

    foreach ($dialogs as $peer){
        $inserted_count += getMessagesAndUploadInDb($MadelineProto, $collection, $peer, $min_date);
    }


    return $inserted_count;
}

function getMessagesAndUploadInDb(AbstractAPI $MadelineProto, Collection $collection, array $peer, int $min_date = 0): int
{
    $offset_id = 0;
    $inserted_count = 0;
    if($min_date!=0) {
        $top_message = $MadelineProto->messages->getMessages(id: [$peer['top_message']]);
        $top_message_date = 0;
        if (isset($top_message['messages'][0]['date']))
            $top_message_date = $top_message['messages'][0]['date'];
        else
            return 0;
        if ($min_date > $top_message_date)
            return 0;
    }
    do {
        //get messages from user dialog
        $messages = $MadelineProto->messages->search([
                'peer' => $peer['peer'],
                'offset_id' => $offset_id,
                'limit' => 100,
                'min_date' => $min_date]
        );

        if (count($messages['messages']) == 0) break;

        $offset_id = end($messages['messages'])['id'];

        //insert messages in MongoDb
        $inserted_count += $collection->insertMany($messages['messages'])->getInsertedCount();

        $MadelineProto->sleep(2);
    } while(true);

    return $inserted_count;
}

