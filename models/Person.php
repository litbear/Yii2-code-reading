<?php
 
namespace app\models;
 
use yii\base\Model;
 
class Person extends Model{
    
    const EVENT_GREET = 'EVENT_GREET';
    
    public function say_hello($parm){
        echo "你应该会看到:".$parm->data.'<br>';
    }
     
    public static function say_goodbye($parm){
        echo "你应该会看到:".$parm->data.'<br>';
    }
}