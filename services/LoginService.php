<?php


use danog\MadelineProto\AbstractAPI;
use danog\MadelineProto\API;
use MongoDB\Collection;

class LoginService{
    private AbstractAPI $MadelineProto;
    private DatabaseService $dbService;
    public function __construct(API $MadelineProto, DatabaseService $dbService){
        $this->MadelineProto = $MadelineProto;
        $this->dbService = $dbService;
    }
    //This will start an interactive login prompt via console (if running via CLI), or a login web UI (if running in the browser).
    function autoLogin(): void
    {
        $this->MadelineProto->start();
    }

    function phoneNumberLogin(string $phoneNumber): void
    {
        $this->MadelineProto->phoneLogin($phoneNumber);
    }

    function confirmPhoneNumberLogin(string $sms_code): void
    {
        $this->MadelineProto->completePhoneLogin($sms_code);
    }

    function getCurrentUserAndInsertIntoDB(): void
    {
        $current_user = $this->MadelineProto->getSelf();
        $this->dbService->saveUserIntoDb($current_user);
    }

    function authAndStartParse(ChatService $chatService, Collection $users_collection, Collection $messages_collection){
        //parse post requests
        try {
            if (isset($_POST["formtype"])) {

                if ($_POST["formtype"] === "phonelogin" && isset($_POST["phone"])) {
                    $this->phoneNumberLogin($_POST["phone"]);
                } elseif ($_POST["formtype"] === "codelogin" && isset($_POST["code"])) {
                    $this->confirmPhoneNumberLogin($_POST["code"]);
                    $this->getCurrentUserAndInsertIntoDB($users_collection);

                    //setup command to load messages every 12 hour
                    CronService::setupCron(realpath('daily_messages.php'));

                    $chatService->getMessagesFromAllDialogsAndUploadInDb($messages_collection);
                }
            }
        }
        catch (Exception){
            http_response_code(400);
            echo "The entered data is invalid";
        }
    }
}
