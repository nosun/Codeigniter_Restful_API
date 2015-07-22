## Codeigniter Restful API

### 说明

随着移动互联网的兴起，服务器端变的越来越轻，更多的转向API接口开发，如何能快速的开发出效率高，安全，能限速，便于做版本控制的API服务呢？这里提供了一种方案。

系统基于Codeigniter 3.0进行开发，参考了chriskacerguis/codeigniter-restserver项目，做了许多改进。

该方案能快速构架RESTFUL风格的API，提供Version 分发，Auth 验证，访问限速，黑名单过滤，白名单过滤，签名验证等多种功能，提供了接口服务应有的基本功能。

最重要的，该框架结构简单，易于理解，易于扩展，您即可以直接使用该框架，也可以根据自己的实际业务需求进行扩展。

Codeigniter 的优点是非常明显的，简单，轻便，文档细致，易于学习，效率高！

Codeigniter 的缺点也是非常明显的，设计理念是简单的MVC，自己开发的类库与系统类库耦合性高。

不过，因为API服务大部分是比较简单的，CI就显得非常适合了。

### 特点

- Speed：CI框架非常小巧，执行效率高，开发也同样快。
- RestFul: 非常容易进行控制器编写，支持Get，Post，Put，Delete，Head等多种方法。
- Format:  支持多种格式的输入输出，常规的为Json，XML。
- Version: 支持多个版本的api。
- Token:   Token机制，用户登录之后，分发一个Token，代表用户特定的资源，Token有时效性，过期需要重新获取。
- Signature: 签名机制，不合法的访问将直接被屏蔽。
- BlackList: 黑名单机制，被加入黑名单的IP地址，将直接被屏蔽，如果配合Iptables等防火墙,效果更佳。
- WhiteList: 白名单机制，跳过各种检查机制，便于测试。
- AccessLimit : 访问速率控制，同一个IP地址在一定的时间内，不能超过设定的访问次数。

### 系统要求
- CI3.0
- php5.4+
- Redis: 用于 Auth验证，Limit Control,用Mysql也可以，不过Reids效率更高。

### 安装

安装好CI之后，增加几个文件就好，一共不超过10个。

### 配置
配置config文件rest.php


### 如何使用


