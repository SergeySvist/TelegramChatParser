<?php

use MongoDB\Client;
use MongoDB\Collection;

class DatabaseService{
    private Client $client;
    private array $collections;
    private int $currentUserId = 0;

    public function __construct(string $conn_string, string $db_name){
        $this->client = new Client($conn_string);
        $db = $this->client->selectDatabase($db_name);

        $collection_names = [
            'users',
            'dialogs',
            'messages'
        ];

        foreach ($collection_names as $name){
            $db->createCollection($name);
            $this->collections[$name] = $db->selectCollection($name);
        }
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
            'dialogs_count' => 0,
            'messages_count' => 0,
        ];

        $this->collections['users']->createIndex(
            ["user_id" => 1 ],
            ["unique" => true]
        );
        $this->collections['users']->updateOne(
            [ 'user_id' => $user_info['user_id'] ],
            [ '$set' => $user_info],
            [ 'upsert' => true]
        );
    }

    public function saveDialogIntoDb($dialogs): void
    {
        if(isset($this->currentUserId)){
            $data = [
                'dialogs' => $dialogs,
                'client_id' => $this->currentUserId
            ];

            $this->collections['dialogs']->updateMany(
                [ 'client_id' => $this->currentUserId ],
                [ '$set' => $data],
                [ 'upsert' => true]
            );

            $this->collections['users']->updateOne(
                [ 'user_id' => $this->currentUserId ],
                [ '$inc' => ['dialogs_count' => count($dialogs)]],
            );
        }
    }

    public function saveMessagesIntoDb($peer_id, $messages): void
    {
        if(isset($this->currentUserId)) {
            $data = [];
            foreach ($messages as $key => $message){
                if (! isset($message['message']))
                    continue;
                $data[] = [
                    'client_id' => $this->currentUserId,
                    'message_id' => $message['id'],
                    'from_id' => $message['from_id'] ?? null,
                    'dialog_id' => $message['peer_id'] ?? null,
                    'date' => $message['date'] ?? null,
                    'message' => $message['message']
                ];
            }

            $this->collections['messages']->insertMany(
                $data
            );

            $this->collections['dialogs']->updateOne(
                [ 'client_id' => $this->currentUserId ],
                [ '$inc' => ["dialogs.$peer_id.message_count" => count($messages)]],
            );
        }
    }

    public function getAllUsers(): array
    {
        return $this->collections['users']->find()->toArray();
    }

    public function getAllDialogsByUserId(int $user_id): array
    {
        return $this->collections['dialogs']->find(['client_id' => $user_id])->toArray();
    }

    public function getAllMessagesInDialogByUserIdAndDialogId(int $user_id, int $dialog_id): array
    {
        return $this->collections['messages']->find(['client_id' => $user_id, 'dialog_id' => $dialog_id])->toArray();
    }
}