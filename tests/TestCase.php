<?php

namespace haqqi\tests\arangodb;

use haqqi\arangodb\Connection;
use yii\console\Application;
use yii\helpers\ArrayHelper;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var Connection */
    protected $arangodb;

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

    /**
     * @since 2018-03-13 08:30:56
     *
     * @param bool  $reset
     * @param array $config
     *
     * @return Connection|object
     * @throws \yii\base\InvalidConfigException
     */
    protected function getConnection($reset = false, $config = [])
    {
        if (!$reset && !\is_null($this->arangodb)) {
            return $this->arangodb;
        }

        $config = ArrayHelper::merge(require(__DIR__ . '/data/config.php'), $config);

        $this->arangodb = \Yii::createObject($config);

        return $this->arangodb;
    }
}
