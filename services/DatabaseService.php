<?php

use MongoDB\Client;
use MongoDB\Collection;

class DatabaseService{
    private Client $client;
    private Collection $collection;
    private int $currentUserId = 0;

    public function __construct(string $conn_string, string $db_name, string $collection_name){
        $this->client = new Client($conn_string);
        $this->collection = $this->client->selectCollection($db_name, $collection_name);
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }


    public function saveUserIntoDb($current_user): void
    {
        $this->currentUserId = $current_user['id'];
        $user_info = [
            'user_id' => $current_user['id'],
            'bot' => $current_user['bot'],
            'premium' => $current_user['premium'],
            'first_name' => $current_user['first_name'],
            'last_name' => $current_user['last_name'] ?? null,
            'username' => $current_user['username'] ?? null,
            'phone' => $current_user['phone'] ?? null,
            'dialogs' => [],
            'dialogs_count' => 0,
            'all_messages_count' => 0,
        ];

        $this->collection->createIndex(
            ["user_id" => 1 ],
            ["unique" => true]
        );
        $this->collection->updateOne(
            [ 'user_id' => $user_info['user_id'] ],
            [ '$set' => $user_info],
            [ 'upsert' => true]
        );
    }

    public function addDialogsIntoUser($dialogs): void
    {
        if(isset($this->currentUserId)){
            $this->collection->updateOne(
                [ 'user_id' => $this->currentUserId ],
                [ '$set' => ['dialogs' => $dialogs, 'dialogs_count' => count($dialogs)]]
            );
        }
    }

    public function addMessagesIntoUserDialogs($peer_id, $messages)
    {
        if(isset($this->currentUserId)) {
            $this->collection->updateOne(
                [ 'user_id' => $this->currentUserId],
                [ '$addToSet' => ["dialogs.$peer_id.messages" => $messages],
                    '$inc' => ["dialogs.$peer_id.message_count" => count($messages), "all_messages_count" => count($messages)]
                ]
            );
        }
    }
}