<?php

require __DIR__.'/../vendor/autoload.php';


use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;


class Test extends TestCase
{
    public function testTest()
    {
        var_dump(\Fonnie\Random::alnum());
    }


    public function Log()
    {

        // create a log channel

        $log = new \Monolog\Logger('Tester');

        $log->pushHandler(new StreamHandler(ROOT_PATH . 'storage/logs/app.log', Logger::WARNING));

        $log->error("Error");

        return $log;

    }
}
