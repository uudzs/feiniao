## 飞鸟阅读
![输入图片说明](https://www.paheng.com/data/attachment/portal/202410/28/160845g0ifqjlillzazq0r.png)

### 相关链接
- 项目会不定时进行更新。
- 演示地址：https://feiniao.paheng.net
- 文档地址：[https://www.paheng.com/forum-2-1.html](https://www.paheng.com/forum-2-1.html)
- 开发交流QQ3群：177260545

### 系统介绍
- 飞鸟阅读是一套基于ThinkPHP6 + MySql + Layui + BUI 开发的小说行业内容管理系统。
- 飞鸟阅读针对小说行业增加了作家模块、并提供了创建作品，添加章节，定时发布，电子签约，实名认证，草稿箱等特有功能，以满足作者的创作需要。
- 飞鸟阅读除具备通用型的后台权限管理功能外，还实现了作品管理，章节审核，章节去重等相关功能。
- 飞鸟阅读不光支持单域名运行，还支持二级域名绑定到模块运行，让同一套系统能分域名运行。
- 飞鸟阅读读者功能有：小说分类、小说搜索、小说推荐、阅读、收藏、书架、点赞、提现、邀请、任务、VIP会员等。
- 飞鸟阅读易于功能扩展，方便二次开发，增加了插件市场，让更多服务可无缝集成于系统中，同时增加了模板定制功能，方便不同网站定制自己独有的风格，帮助开发者降低二次开发难度。

### 适用对象
飞鸟阅读适用于小说阅读平台运营者、小说作者、以及希望搭建小说阅读平台的个人或企业。通过本系统，您可以快速搭建起一个功能完善、界面美观的小说阅读平台，吸引大量用户，实现业务增长和盈利。

### 安装教程

**一、服务器。**

服务器最低配置：
~~~
    1核CPU (建议2核+)
    1G内存 (建议4G+)
    1M带宽 (建议3M+)
~~~
服务器运行环境要求：
~~~
    PHP >= 7.4  
    Mysql >= 5.5.0 (需支持innodb引擎)  
    Apache 或 Nginx  
    PDO PHP Extension  
    MBstring PHP Extension  
    CURL PHP Extension  
    Composer (用于管理第三方扩展包)
~~~

**二、系统安装**

推荐使用命令行安装，因为采用命令行安装的方式可以和飞鸟阅读随时保持更新同步。使用命令行安装请提前准备好Git、Composer。

**飞鸟阅读的安装步骤，以下加粗的内容需要特别留意：**

第一步：克隆飞鸟阅读到你本地 **（如果不用git的可以在代码仓库上角打包下载代码，然后解压上传到服务器）** 

    git clone https://gitee.com/paheng/feiniao.git

第二步：进入目录

    cd feiniao  
    
第三步：下载PHP依赖包
    
composer install  
    
第四步：添加虚拟主机并绑定到项目的public目录， **实际部署中，确保绑定域名访问到的是public目录** 。

第五步：配置伪静态规则，使用的是thinkphp的伪静态规则，**具体看下面的第三点的伪静态配置内容**。
    
第六步：访问 http://www.你的域名.com/install/index 进行安装

**注意：安装过程中，系统会自动创建数据库，请确保填写的数据库用户的权限可创建数据库，如果权限不足，请先手动创建空的数据库，然后填写刚创建的数据库名称和用户名也可完成安装。** 

**提醒：安装过程中，如果进度条卡住，一般都是数据库写入权限或者安装环境配置问题，请注意检查。** 

**PS：如需要重新安装，请删除目录里面 config/install.lock 的文件，即可重新安装。** 

**三、伪静态配置**

**Nginx**
修改nginx.conf 配置文件 加入下面的语句。
~~~
    location / {
        if (!-e $request_filename){
        rewrite  ^(.*)$  /index.php?s=$1  last;   break;
        }
    }
~~~

**Apache**
把下面的内容保存为.htaccess文件放到应用入 public 文件的同级目录下。
~~~
    <IfModule mod_rewrite.c>
    Options +FollowSymlinks -Multiviews
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php?/$1 [QSA,PT,L]
    </IfModule>
~~~


### 常见问题

1.  安装失败，可能存在php配置文件禁止了putenv 和 proc_open函数。解决方法，查找php.ini文件位置，打开php.ini，搜索 disable_functions 项，看是否禁用了putenv 和 proc_open函数。如果在禁用列表里，移除putenv proc_open然后退出，重启php即可。

2.  如果安装后打开页面提示404错误，请检查服务器伪静态配置，如果是宝塔面板，网站伪静态请配置使用thinkphp规则。

3.  安装过程中，如果进度条卡住(99%)，一般都是数据库写入权限或者安装环境配置config目录无法写入问题，请注意检查权限。

4.  如果安装成功后，无法显示图形验证码的，请看是否已安装（开启）了PHP的GD库。

5.  如果安装成功后，无法上传文件的，请看是否已安装（开启）了PHP的fileinfo扩展。

6. **如果需要代安装服务，可通过QQ号或去官网得到微信联系方式进行沟通，开源不易，该服务为收费项目，特在此申明以免造成误解。**

### PC端截图预览
|页面截图      |
| :--------: |
|![页面截图](https://www.paheng.com/static/image/feiniao/pc_home.png "页面截图")|

### 移动端截图预览
|页面截图      |    页面截图|
| :--------: | :--------:|
| ![页面截图](https://www.paheng.com/static/image/feiniao/首页.png "页面截图")|![页面截图](https://www.paheng.com/static/image/feiniao/首页2.png "页面截图")|
|![页面截图](https://www.paheng.com/static/image/feiniao/首页3.png "页面截图")|![页面截图](https://www.paheng.com/static/image/feiniao/书详情.png "页面截图")|
|![页面截图](https://www.paheng.com/static/image/feiniao/首页3.png "页面截图")|![页面截图](https://www.paheng.com/static/image/feiniao/书架.png "页面截图")|
|![页面截图](https://www.paheng.com/static/image/feiniao/任务.png "页面截图")|![页面截图](https://www.paheng.com/static/image/feiniao/邀请.png "页面截图")|
|![页面截图](https://www.paheng.com/static/image/feiniao/VIP.png "页面截图")|![页面截图](https://www.paheng.com/static/image/feiniao/我的.png "页面截图")|
|![页面截图](https://www.paheng.com/static/image/feiniao/章节.png "页面截图")|![页面截图](https://www.paheng.com/static/image/feiniao/提现.png "页面截图")|

### 特别感谢
- 飞鸟阅读基于[勾股CMS](https://gitee.com/gouguopen/gougucms)二次开发完成，同样遵循Apache2开源协议发布。 
- 在这里特别感谢勾股CMS的付出与努力，谢谢！

### 支持我们
- If the project is very helpful to you, you can buy the author a cup of coffee.
- 非常感谢您一直以来对我们的关注和支持，我们将不断努力，为您带来更好的体验和回报。
- 感谢您给予我们的信任、鼓励和支持。我们会加倍努力，为您带来更好的服务！

|支付宝      |    微信|
| :--------: | :--------:|
| <img src="https://www.paheng.com/static/image/zfb.png" width="300"  align=center />|<img src="https://www.paheng.com/static/image/wx.png" width="300"  align=center />|
