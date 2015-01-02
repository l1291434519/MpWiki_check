# 获取微信公众平台WIKI文档更新
项目作者：[binsee](https://coding.net/u/binsee)


## 项目功能：
检查微信公众平台、企业号平台的开发文档(wiki)及公告是否有更新，
如果有更新会邮件通知指定人员。


## 项目说明：
以在Coding上部署所建立，在部署到演示运行环境时，需要定义一些环境变量。


## 环境变量：
部署时需设定的环境变量列表：
* WIKI_DIR            监测缓存的本地存放主目录(使用此参数可免设置下面3个参数)
* WIKI_DIR_MP         微信公众平台wiki的本地存放目录
* WIKI_DIR_QY         微信企业平台wiki的本地存放目录
* NOTICE_DIR          微信平台公告的本地存放目录

* REMOTE_GIT          微信平台更新监测总库的远程git地址
                      使用此参数则将几个监测作为不同分支保管，就可以不建立好几个git库了
* REMOTE_GIT_MP       微信公众平台wiki的远程git地址
* REMOTE_GIT_QY       微信企业平台wiki的远程git地址
* REMOTE_GIT_NOTICE   微信平台公告的远程git地址

* GIT_EMAIL           标注GIT库的作者邮箱
* GIT_NAME            标注GIT库的作者

* SMTP_SERVER         SMTP服务器
* SMTP_SERVER_PORT    SMTP服务器端口
* SMTP_USER_MAIL      SMTP服务器的用户邮箱
* SMTP_MAIL_TO        邮件收件人,多个手机人用|或,或;分隔
* SMTP_USER           SMTP服务器的用户帐号
* SMTP_PASS           SMTP服务器的用户密码


## 注意事项：
环境变量设置注意事项：
本地缓存目录系列变量，设置为绑定的文件系统访问路径。
如未设置且远程git地址也未设置，则演示被重启时，会出现wiki被重新建立而无法分辨是否更新。
需要注意路径要以`/`做结尾，否则会出现问题。

远程git地址系列变量，需选择https格式的远程GIT地址。
将账号密码附在前部，如账号和密码中有@符号，用%40进行替换。
格式如：https://user:pass@coding.net/binsee/wiki.git
注意如果使用总库就不用按不同项建立分库。
如果某个项设置了分库，则不会更新到总库(优先使用独立库)。

GIT库作者部分，可以不设置，默认的是作者binsee

SMTP系列变量，如果未设置，将不会发送邮件进行提醒。

