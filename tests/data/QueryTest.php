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
        $query
            ->select(["_id", "_key"])
            ->from("post")
            ->andWhere(['OR', ['_id' => '12222'], ['!=', "_id", "44444"]]);

        $build = new QueryBuilder($query);
        
        // @TODO mas haq, tolong kira" ini outputnya gimana? untuk penyesuaian
        $raw = "FOR `post` IN `post` FILTER ( `post`.`_id` == @paramWhere0 OR `post`.`_id` != @paramWhere1 ) RETURN { _id: `post`.`_id`, _key: `post`.`_key`}";
        list($aql, $params) = $build->build();
        $this->assertEquals($raw, $aql);
    }
}