<?php


use danog\MadelineProto\API;

//This will start an interactive login prompt via console (if running via CLI), or a login web UI (if running in the browser).
function autoLogin(API $MadelineProto): void
{
    $MadelineProto->start();
}

function phoneNumberLogin(API $MadelineProto, string $phoneNumber): void
{
    $MadelineProto->phoneLogin($phoneNumber);
}

function confirmPhoneNumberLogin(API $MadelineProto, string $sms_code): void
{
    $MadelineProto->completePhoneLogin($sms_code);
}