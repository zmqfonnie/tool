<?php

namespace  Tests;
require __DIR__.'/../vendor/autoload.php';

use Fonnie\Util\Random;
use GuzzleHttp\Client;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;


class Test extends TestCase
{
    public function testTest()
    {


        $stack = [];
        $this->assertEquals(0, count($stack));
        array_push($stack, 'foo');
        // 添加日志文件,如果没有安装monolog，则有关monolog的代码都可以注释掉
        $this->Log()->error('hello', $stack);
        $this->assertEquals('foo', $stack[count($stack)-1]);
        $this->assertEquals(1, count($stack));
        $this->assertEquals('foo', array_pop($stack));
        $this->assertEquals(0, count($stack));

//        $this->assertEquals('《三体》', \Fonnie\Util\Random::alnum());
    }

    public function testChange(){

        $this->assertEquals(true, 123);
    }


    public function Log()
    {

        // create a log channel
        echo __DIR__ . '/logs/'.__CLASS__.'.log';
        $logger = new \Monolog\Logger('日志实例标识');

        $stream_handler = new StreamHandler(__DIR__ . '/logs/'.__CLASS__.'.log', Logger::INFO);
        $stream_handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($stream_handler);

        $logger->error("Error");

        $logger->info('Adding a new user', ['username' => new Random()]);
        return $logger;

    }
}
