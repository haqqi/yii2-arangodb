<?php
/**
 * Created by PhpStorm.
 * User: Haqqi
 * Date: 3/29/2018
 * Time: 6:07 PM
 */

namespace haqqi\tests\arangodb;


use haqqi\arangodb\Query;
use haqqi\arangodb\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    public function testBuildFromOnly()
    {
        $expected = "FOR post IN post RETURN post";

        $query = new Query();
        $query->from('post');

        $queryBuilder = new QueryBuilder($query);

        list($aql,) = $queryBuilder->build();

        $this->assertEquals($expected, $aql);

        return $queryBuilder;
    }

    public function testBuildFromOnlyEscaped()
    {
        $expected = "FOR `inbound` IN `inbound` RETURN `inbound`";
        $query = new Query();

        $query->from('inbound');

        $queryBuilder = new QueryBuilder($query);

        list($aql,) = $queryBuilder->build();

        $this->assertEquals($expected, $aql);
    }

    /**
     * @depends testBuildFromOnly
     *
     * @param QueryBuilder $queryBuilder
     */
    public function testBuildFromAndSelect(QueryBuilder $queryBuilder)
    {
        $expected = "FOR post IN post RETURN { title: post.title, content: post.content }";

        $queryBuilder->query->select(['title', 'content']);

        list($aql,) = $queryBuilder->build();

        $this->assertEquals($expected, $aql);
    }
}
