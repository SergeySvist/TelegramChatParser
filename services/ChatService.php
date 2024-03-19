<?php

use danog\MadelineProto\AbstractAPI;
use danog\MadelineProto\API;
use MongoDB\Collection;

class ChatService{
    private AbstractAPI $MadelineProto;

    public function __construct(API $MadelineProto){
        $this->MadelineProto = $MadelineProto;
    }

    function getAllUserDialogs(): array
    {
        return $this->MadelineProto->getFullDialogs();
    }

    function filterDialogsExcludeChannels(array $dialogs): array{
        $filteredDialogs = [];
        foreach ($dialogs as $peer){
            $info = $this->MadelineProto->getInfo($peer['peer']);

            if($info['type']!=='channel')
                $filteredDialogs[] = $peer;
        }
        return $filteredDialogs;
    }

    function getMessagesFromAllDialogsAndUploadInDb(Collection $collection, int $min_date = 0): int
    {
        $dialogs = $this->getAllUserDialogs();
        $dialogs = $this->filterDialogsExcludeChannels($dialogs);
        $inserted_count = 0;

        foreach ($dialogs as $peer){
            $inserted_count += $this->getMessagesAndUploadInDb($collection, $peer, $min_date);
        }

        return $inserted_count;
    }

    function isMinDateBiggerThanTopMessageDate(int $min_date, array $top_message): bool{
        if (isset($top_message['messages'][0]['date']))
            $top_message_date = $top_message['messages'][0]['date'];
        else
            return false;
        if ($min_date > $top_message_date)
            return false;

        return true;
    }

    function getMessagesAndUploadInDb(Collection $collection, array $peer, int $min_date = 0): int
    {
        $offset_id = 0;
        $inserted_count = 0;
        if($min_date != 0) {
            $top_message = $this->MadelineProto->messages->getMessages(id: [$peer['top_message']]);
            if(!$this->isMinDateBiggerThanTopMessageDate($min_date, $top_message))
                return 0;
        }
        do {
            //get messages from user dialog
            $messages = $this->MadelineProto->messages->search([
                    'peer' => $peer['peer'],
                    'offset_id' => $offset_id,
                    'limit' => 100,
                    'min_date' => $min_date]
            );

            if (count($messages['messages']) == 0) break;
            $offset_id = end($messages['messages'])['id'];

            //insert messages in MongoDb
            $inserted_count += $collection->insertMany($messages['messages'])->getInsertedCount();

            $this->MadelineProto->sleep(2);
        } while(true);

        return $inserted_count;
    }
}

