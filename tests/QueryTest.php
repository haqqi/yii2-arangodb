<?php
namespace haqqi\tests\arangodb;

use haqqi\arangodb\Query;
use haqqi\arangodb\QueryBuilder;

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
            ->andWhere(['OR', ['name' => "O'larys"], ['==', "name", "123"]]);

        // because there's no data can be query
        $this->assertEmpty($query->one($this->getConnection()));
    }
}
