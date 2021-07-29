<?php

namespace  Tests;
require __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;
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


    public function Log()
    {

        // create a log channel

        $log = new \Monolog\Logger('Tester');

        $log->pushHandler(new StreamHandler(__DIR__ . '/logs/test.log', Logger::WARNING));

        $log->error("Error");

        return $log;

    }
}
