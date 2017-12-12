<?php
/**
 * Created by PhpStorm.
 * User: Haqqi
 * Date: 12/12/2017
 * Time: 7:14 AM
 */

namespace haqqi\arangodb;


use yii\base\Component;
use yii\db\MigrationInterface;
use yii\di\Instance;

abstract class Migration extends Component implements MigrationInterface
{
    /** @var string|Connection */
    public $db = 'arangodb';

    /**
     * @var bool indicates whether the console output should be compacted.
     * If this is set to true, the individual commands ran within the migration will not be output to the console.
     * Default is false, in other words the output is fully verbose by default.
     * @since 2.0.13
     */
    public $compact = false;

    /**
     * @since 2017-12-12 07:18:24
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();

        $this->db = Instance::ensure($this->db, Connection::className());
    }

    public function createCollection()
    {

    }

    public function dropCollection()
    {

    }

    public function truncateCollection()
    {

    }

    /**
     * Prepares for a command to be executed, and outputs to the console.
     *
     * @param string $description the description for the command, to be output to the console.
     *
     * @return float the time before the command is executed, for the time elapsed to be calculated.
     * @since 2.0.13
     */
    protected function beginCommand($description)
    {
        if (!$this->compact) {
            echo "    > $description ...";
        }
        return \microtime(true);
    }

    /**
     * Finalizes after the command has been executed, and outputs to the console the time elapsed.
     *
     * @param float $time the time before the command was executed.
     *
     * @since 2.0.13
     */
    protected function endCommand($time)
    {
        if (!$this->compact) {
            echo ' done (time: ' . \sprintf('%.3f', \microtime(true) - $time) . "s)\n";
        }
    }
}
