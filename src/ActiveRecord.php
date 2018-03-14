<?php

namespace haqqi\arangodb;

class ActiveRecord extends BaseActiveRecord
{
    public function getAttribute($name)
    {
        // TODO: Implement getAttribute() method.
    }

    public function setAttribute($name, $value)
    {
        // TODO: Implement setAttribute() method.
    }

    public function hasAttribute($name)
    {
        // TODO: Implement hasAttribute() method.
    }

    public function getOldPrimaryKey($asArray = false)
    {
        // TODO: Implement getOldPrimaryKey() method.
    }

    public static function find()
    {
        // TODO: Implement find() method.
    }

    public static function findOne($condition)
    {
        // TODO: Implement findOne() method.
    }

    public static function findAll($condition)
    {
        // TODO: Implement findAll() method.
    }

    public static function updateAll($attributes, $condition = null)
    {
        // TODO: Implement updateAll() method.
    }

    public static function deleteAll($condition = null)
    {
        // TODO: Implement deleteAll() method.
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        // TODO: Implement save() method.
    }

    public function insert($runValidation = true, $attributes = null)
    {
        // TODO: Implement insert() method.
    }

    public function update($runValidation = true, $attributeNames = null)
    {
        // TODO: Implement update() method.
    }

    public function delete()
    {
        // TODO: Implement delete() method.
    }

    public function getIsNewRecord()
    {
        // TODO: Implement getIsNewRecord() method.
    }

    public function equals($record)
    {
        // TODO: Implement equals() method.
    }

    public function getRelation($name, $throwException = true)
    {
        // TODO: Implement getRelation() method.
    }

    public function populateRelation($name, $records)
    {
        // TODO: Implement populateRelation() method.
    }

    public function link($name, $model, $extraColumns = [])
    {
        // TODO: Implement link() method.
    }

    public function unlink($name, $model, $delete = false)
    {
        // TODO: Implement unlink() method.
    }
}