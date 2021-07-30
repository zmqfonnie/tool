<?php

namespace Tests;
require __DIR__ . '/../vendor/autoload.php';

use Fonnie\Util\Random;
use GuzzleHttp\Client;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;


class Test extends TestCase
{
    public function testRandom()
    {
        $this->log();
        $this->assertEquals(true, Random::alnum());
    }





    public function log($data = [], $msg = '')
    {

        // create a log channel
        echo __DIR__ . '/logs/' . __CLASS__ . '.log';
        $logger = new \Monolog\Logger('日志实例标识');

        $stream_handler = new StreamHandler(__DIR__ . '/logs/' . __CLASS__. '.log', Logger::INFO);
        $stream_handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($stream_handler);

        $logger->info($msg, $data);
        return $logger;

    }
}
