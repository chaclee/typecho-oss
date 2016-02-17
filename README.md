typecho附件上传至阿里云OSS

******
>阿里云OSS。    —— [OSS管理控制台](https://oss.console.aliyun.com/index)

> 参考[OSS开发者资源]    —— [OSS文档](http://www.aliyun.com/product/oss?spm=5176.2020520105.103.15.HAyFbj#Help) 

## 使用方法
* ①将所有文件解压到`AliOSS`文件夹,上传至:/usr/plugins,插件文件夹名应为`AliOSS` ![示例](https://o0z4bgym5.qnssl.com/QQ%E5%9B%BE%E7%89%8720160217142457.png)

* ②启用插件

* ③填写配置信息,保存

* ③开始撰写文章体验附件上传
 
 
###请注意:
> * 确保插件文件夹名为`AliOSS`,要与`Plugin.php`中的{$name}_Plugin一致,否则无法使用
> * 所选地域要与bucket一致
