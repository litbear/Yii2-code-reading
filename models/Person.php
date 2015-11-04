<?php
 
namespace app\models;
 
 
class Person extends \yii\db\ActiveRecord{
    public static function tableName()
    {
        return 'person';
    }
}