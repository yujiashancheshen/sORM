# Sorm
## 概述
一个PHP实现的简单的ORM框架，希望达到如下目标：
1. 添加完数据库的配置信息就可以完成基础的增删改查
2. 支持数组和对象两种获取结果
3. 通过脚手架非常轻松的生成model
4. 可以非常简单的一句代码实现通用功能
5. 写出自己认为的简单可读的代码

## 目录结构
```
Sorm
├── bin
    ├── gen_model.sh        #生成model目录中对应表的model文件

├── orm
    ├── model.php           #调用db类的方法
    ├── db.php              #封装pdo的一些基本操作
├── model
    ├── modelItem.php       #bin/gen_model.sh生成
├── dbConfig.php            #数据库配置 
```

## 使用方法
1. 按照格式配置config.php文件 
2. 运行bin/gen_model.php脚本，就可以生成model中的各个文件 
3. 运行按照demo.php的例子进行调用就可以访问数据库 
