<?php

use haqqi\arangodb\Query;
use haqqi\arangodb\QueryBuilder;
use haqqi\tests\arangodb\TestCase;

/**
 * Class QueryTest
 *
 * @author  L Shaf <shafry2008@gmail.com>
 * @package ${NAMESPACE}
 */
class QueryTest extends TestCase
{
    public function testQuery()
    {
        $query = new Query();
        $query->select(["_id", "_key"])
            ->from("post")
            ->where(["_id" => 'asd'])
            ->andWhere(["_id" => '1112'])
            ->andWhere(['OR', ['_id' => '12222'], ['!=', "_id", "44444"]])
            ->andWhere(['LIKE', 'regex', 'regex']);

        $build = new QueryBuilder($query);
        echo $build->build();
        
        // @TODO mas haq, tolong kira" ini outputnya gimana? untuk penyesuaian
        $raw = "FOR `post` IN `post` RETURN { _id: `post`.`_id`, _key: `post`.`_key`}";
        $this->assertEquals($raw, $build->build());
    }
}