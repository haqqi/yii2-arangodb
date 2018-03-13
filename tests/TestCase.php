<?php

namespace haqqi\tests\arangodb;

use yii\console\Application;
use yii\helpers\ArrayHelper;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function mockApplication($config = [], $appClass = Application::class)
    {
        new $appClass(ArrayHelper::merge([
            'id'          => 'yii2-arangodb-test',
            'basePath'    => __DIR__,
            'vendorPath'  => dirname(__DIR__),
            'runtimePath' => __DIR__ . '/runtime'
        ], $config));
    }

    protected function destroyApplication()
    {
        \Yii::$app = null;
    }
}
