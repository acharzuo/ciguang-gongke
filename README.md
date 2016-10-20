# ciguang-gongke
慈光功课系统，基于Wecenter开发的插件




## 在首页插入内容


## 设置默认首页的方法
在：system\core\uri.php有个默认控制器，修改这个参数

···php
var $default_vars = array(
'app_dir' => 'home', //应用名
'controller' => 'main', //文件名
'action' => 'index'//执行的程序
); 
···

