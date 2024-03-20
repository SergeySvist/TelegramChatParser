<?php

use danog\MadelineProto\AbstractAPI;
use danog\MadelineProto\API;
use MongoDB\Collection;

class ChatService{
    private AbstractAPI $MadelineProto;
    private DatabaseService $dbService;

    public function __construct(API $MadelineProto, DatabaseService $dbService){
        $this->MadelineProto = $MadelineProto;
        $this->dbService = $dbService;
    }

    function getAllUserDialogs(): array
    {
        return $this->MadelineProto->getFullDialogs();
    }

    function filterDialogsExcludeChannelsAndUpdateDialogsArray(array &$dialogs): array{
        $filteredDialogs = [];
        foreach ($dialogs as $key => $peer){
            $info = $this->MadelineProto->getInfo($peer['peer']);

            if($info['type']!=='channel') {
                $filteredDialogs[] = $peer;
                $dialogs[$key] = [
                    'dialog_info' => $info['User'] ?? $info['Chat'] ?? null,
                    'type' => $info['type'],
                    'dialog_id' => $info['channel_id'] ?? $info['user_id'] ?? $info['chat_id'] ?? null,
                    'messages' => [],
                    'message_count' => 0
                ];
            }
            else{
                unset($dialogs[$key]);
            }
        }
        return $filteredDialogs;
    }

    function getMessagesFromAllDialogsAndUploadInDb(int $min_date = 0): void
    {
        $dialogs = $this->getAllUserDialogs();
        $filtered_dialogs = $this->filterDialogsExcludeChannelsAndUpdateDialogsArray($dialogs);

        $this->dbService->addDialogsIntoUser($dialogs);

        foreach ($filtered_dialogs as $peer){
            $this->getMessagesAndUploadInDb($peer, $min_date);
        }
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

    function getMessagesAndUploadInDb(array $peer, int $min_date = 0): void
    {
        $offset_id = 0;
        if($min_date != 0) {
            $top_message = $this->MadelineProto->messages->getMessages(id: [$peer['top_message']]);
            if(!$this->isMinDateBiggerThanTopMessageDate($min_date, $top_message))
                return;
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
            $this->dbService->addMessagesIntoUserDialogs($peer['peer'], $messages['messages']);

            $this->MadelineProto->sleep(2);
        } while(true);

    }
}

