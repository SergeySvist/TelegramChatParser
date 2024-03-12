<?php

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Stream\Proxy\HttpProxy;
use danog\MadelineProto\Settings\Connection;

function replaceDeviceForAuth(Settings $settings, string $device): void
{
    $settings->setAppInfo($settings->getAppInfo()->setDeviceModel($device));
    $settings->applyChanges();
}

/**
 * @throws \danog\MadelineProto\Exception
 */
function addHttpProxy(Settings $settings, array $httpProxy): void
{
    $settings->setConnection((new Connection())->addProxy(HttpProxy::class, $httpProxy));
    $settings->applyChanges();
}