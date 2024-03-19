<?php

use Cron\Job\ShellJob;
use Cron\Schedule\CrontabSchedule;

class CronService{
    public static function setupCron(string $path){
        $deprecatedStatus = new ShellJob();
        $deprecatedStatus->setCommand("php $path");
        $deprecatedStatus->setSchedule(new CrontabSchedule('0 */12 * * *'));
    }
}