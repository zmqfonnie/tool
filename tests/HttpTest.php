<?php
/**
 * Created by : fonnie
 * Date: 2021/05/23
 * Time: 20:00:35
 */


use PHPUnit\Framework\TestCase;

//换源 composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
//运行 phpunit tests

require_once 'autoload.php';

class HttpTest extends TestCase
{

    public function testTest()
    {
        $data = \Fonnie\Http::post('nbxtkf.xxnmkj.cn/test.php');

        dump($data);
        log($data);
    }



}