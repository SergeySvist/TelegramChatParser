<?php


use danog\MadelineProto\AbstractAPI;
use danog\MadelineProto\API;

class LoginService{
    private AbstractAPI $MadelineProto;

    public function __construct(API $MadelineProto){
        $this->MadelineProto = $MadelineProto;
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
}
