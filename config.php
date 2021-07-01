<?php
/**
 * Created by : fonnie
 * Date: 2021/07/01
 * Time: 19:31:52
 */
require_once __DIR__.'/vendor/autoload.php';
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();