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
            ->andWhere(["_id" => '1112'])
            ->andWhere(['OR', ['_id' => '12222'], ['!=', "_id", "44444"]]);

        $build = new QueryBuilder($query);
        echo $build->build();
        
        $raw = "FOR `post` IN `post` RETURN { _id: `post`.`_id`, _key: `post`.`_key`}";
        $this->assertEquals($raw, $build->build());
    }
}