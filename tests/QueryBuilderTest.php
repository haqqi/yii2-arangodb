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

    public function testBuildFromAndSelect()
    {
        $expected = "FOR post IN post RETURN { title: post.title, content: post.content }";

        $query = new Query();
        $query->select(['title', 'content'])->from('post');

        $queryBuilder = new QueryBuilder($query);

        list($aql,) = $queryBuilder->build();

        $this->assertEquals($expected, $aql);
    }

    public function testBuildLimit()
    {
        $expected = "FOR post IN post LIMIT 1, 10 RETURN post";

        $query = new Query();
        $query->from('post')->limit(10)->offset(1);

        $queryBuilder = new QueryBuilder($query);

        list($aql,) = $queryBuilder->build();

        $this->assertEquals($expected, $aql);
    }

    public function testBuildOrderBy()
    {
        $expected = "FOR post IN post SORT post.title DESC, post.content RETURN post";

        $query = new Query();
        $query->from('post')->orderBy(['title' => \SORT_DESC, 'content' => \SORT_ASC]);

        $queryBuilder = new QueryBuilder($query);

        list($aql,) = $queryBuilder->build();

        $this->assertEquals($expected, $aql);
    }
}
