<?php
// class Person extends Model{ } 类就省略不写了 

// 下面是控制器 class SiteController extends Controller { } 的代码节选
public function actionEvent() {

        $person = new Person;

// 使用PHP全局函数作为handler来进行绑定
//        $person->on(Person::EVENT_GREET, 'person_say_hello');

// 使用对象$obj的成员函数say_hello来进行绑定
        $person->on(Person::EVENT_GREET, [$person,'say_hello'],'hello');

// 使用类Greet的静态成员函数say_hello进行绑定
//        $person->on(Person::EVENT_GREET, ['app\helper\Greet', 'say_hello']);

// 使用匿名函数
//        $person->on(Person::EVENT_GREET, function ($event) {
//            echo 'Hello';
//        });
        $person->trigger(Person::EVENT_GREET);
}

// 在执行trigger时 
$person->_events = [
    "EVENT_GREET" => [
        [0]=> [
          [0]=>
          [
            [0]=> object(app\models\Person),
            [1]=>  "say_hello"
          ]
          [1]=> "hello"
        ]
    ],
    'EVENT_FOO' => [ ],
    'EVENT_BAR' => [ ],
];

public function trigger($name, Event $event = null)
{
    $this->ensureBehaviors();
    if (!empty($this->_events[$name])) {
        /**
         * 假如触发的时候没传$event，那就new一个
         * 然后在运行时配置这个新的Event对象。
         */
        if ($event === null) {
            $event = new Event;
        }
        if ($event->sender === null) {
            $event->sender = $this;
        }
        $event->handled = false;
        $event->name = $name;
        foreach ($this->_events[$name] as $handler) {
            $event->data = $handler[1];
//              调试信息 证明了$handler[0] 是个数组 
//              call_user_func([实例变量,方法名],方法参数);
//              if($name == 'EVENT_GREET'){var_dump($name);var_dump($handler[0]);}
            call_user_func($handler[0], $event);
            // stop further handling if the event is handled
            if ($event->handled) {
                return;
            }
        }
    }
    // invoke class-level attached handlers
    // 最后，把配置好的对象Event $event对象传给静态方法
    // Event::trigger
    Event::trigger($this, $name, $event);
}