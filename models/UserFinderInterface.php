<?php

/* 
 * 依赖注入的实验类
 */

namespace app\models;

// 定义接口
interface UserFinderInterface
{
    function findUser();
}