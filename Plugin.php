<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * typecho附件上传阿里云OSS,基于OSS 1.1.7文档开发
 *
 * @package 阿里云OSS文件上传
 * @author raintao
 * @version 1.0.1
 * @link https://github.com/rainwsy
 */
class AliOSS_Plugin implements Typecho_Plugin_Interface
{
    // 激活插件
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array(
            'AliOSS_Plugin',
            'uploadHandle'
        );
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array(
            'AliOSS_Plugin',
            'modifyHandle'
        );
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array(
            'AliOSS_Plugin',
            'deleteHandle'
        );
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array(
            'AliOSS_Plugin',
            'attachmentHandle'
        );
        return _t('插件已经激活，请设置OSS信息！');
    }
    
    // 禁用插件
    public static function deactivate()
    {
        return _t('插件已被禁用');
    }
    
    // 插件配置面板
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /**
         * 节点
         */
        $endpointList = array(
            "" => _t('请选择所属地域'),
            "oss-cn-beijing.aliyuncs.com" => _t('北京'),
            "oss-cn-qingdao.aliyuncs.com" => _t('青岛'),
            "oss-cn-shenzhen.aliyuncs.com" => _t('深圳'),
            "oss-cn-hangzhou.aliyuncs.com" => _t('杭州'),
            "oss-cn-shanghai.aliyuncs.com" => _t('上海')
        );

        $endpoint = new Typecho_Widget_Helper_Form_Element_Select('endpoint', $endpointList, 'oss-cn-beijing.aliyuncs.com', _t('所属地域'), _t('请选择<strong style="color:#C33;">bucket对应节点</strong>,否则无法使用,默认为北京！'));
        $form->addInput($endpoint->addRule('required', _t('所属地域 不能为空！')));
        $bucket = new Typecho_Widget_Helper_Form_Element_Text('bucket', null, null, _t('Bucket名称：'));
        $form->addInput($bucket->addRule('required', _t('“空间名称”不能为空！')));

        $accessid = new Typecho_Widget_Helper_Form_Element_Text('accessid', null, null, _t('Access Key ID'), _t('点击<a href="https://ak-console.aliyun.com/#/accesskey">这里</a>查看Access Key ID&Access Key Secret'));
        $form->addInput($accessid->addRule('required', _t('AccessID 不能为空！')));

        $accesskey = new Typecho_Widget_Helper_Form_Element_Text('accesskey', null, null, _t('Access Key Secret：'));
        $form->addInput($accesskey->addRule('required', _t('AccessKey 不能为空！')));

        $domain = new Typecho_Widget_Helper_Form_Element_Text('domain', null, null, _t('图片HTTP访问前缀：'), _t('OSS可供外网访问的域名前缀，比如https://cdn.mydomain.com'));
        $form->addInput($domain->addRule('required', _t('请填写OSS图片服务的域名前缀！')));

        $savepath = new Typecho_Widget_Helper_Form_Element_Text('savepath', null, 'img/{year}/{month}/', _t('图片保存路径格式：'), _t('图片附件保存路径的格式<br />可选参数：{year} 年份、{month} 月份、{day} 日期。<br /><strong style="color:#C33;">因为阿里云图片处理服务不支持故而GIF视为非图片</strong>'));
        $form->addInput($savepath->addRule('required', _t('请填写图片保存路径格式！')));

        $style = new Typecho_Widget_Helper_Form_Element_Text('style', null, null, _t('图片样式后缀：'), _t('阿里云图片处理服务的后缀，比如@600w.webp'));
        $form->addInput($style);

        $nonimg_domain = new Typecho_Widget_Helper_Form_Element_Text('nonimg_domain', null, null, _t('非图片HTTP访问前缀：'), _t('OSS可供外网访问的域名前缀，比如https://oss.mydomain.com'));
        $form->addInput($nonimg_domain->addRule('required', _t('请填写OSS的域名前缀！')));

        $nonimg_savepath = new Typecho_Widget_Helper_Form_Element_Text('nonimg_savepath', null, 'atta/{year}/{month}/', _t('非图片保存路径格式：'), _t('非图片附件保存路径的格式<br />可选参数：{year} 年份、{month} 月份、{day} 日期'));
        $form->addInput($nonimg_savepath->addRule('required', _t('请填写非图片保存路径格式！')));
    }

    // 个人用户配置面板
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {}
    
    // 获得插件配置信息
    public static function getConfig()
    {
        return Typecho_Widget::widget('Widget_Options')->plugin('AliOSS');
    }
    
    // 初始化OSS SDK
    public static function initSDK()
    {
        // 引入 SDK
        require_once 'sdk.class.php';
    }
    
    // 删除文件
    public static function deleteFile($filepath)
    {
        // 获取插件配置
        $option = self::getConfig();
        self::initSDK();
        $obj = new ALIOSS($option->accessid, $option->accesskey, $option->endpoint);
        $obj->delete_object($option->bucket, $filepath);
    }
    
    public static function isImage($ext)
    {
        return in_array($ext, array('jpg', 'jpeg', 'bmp', 'png', 'tiff'));
    }

    // 上传文件
    public static function uploadFile($file, $content = null)
    {
        // 获取上传文件
        if (empty($file['name'])) return false;
        $option = self::getConfig();
        if (!isset($option->accessid)) {
            return false;
        }
        self::initSDK();
        $obj = new ALIOSS($option->accessid, $option->accesskey, $option->endpoint);
        // 校验扩展名
        $part = explode('.', $file['name']);
        $ext = (($length = count($part)) > 1) ? strtolower($part[$length - 1]) : '';
        if (!Widget_Upload::checkFileType($ext)) return false;
        // 保存位置
        $savename = str_replace(
                array('{year}', '{month}', '{day}'),
                array(date('Y'), date('m'), date('d')),
                ltrim(self::isImage($ext) ? $option->savepath : $option->nonimg_savepath, '/')
            ) . sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $response = $obj->upload_file_by_file($option->bucket, $savename, $file['tmp_name']);
        if ($response->status===200) {
            return array(
                'name' => $file['name'],
                'path' => $savename,
                'size' => $file['size'],
                'type' => $ext,
                'mime' => Typecho_Common::mimeContentType($savename)
            );
        }
        
        return false;
    }
    
    // 上传文件处理函数
    public static function uploadHandle($file)
    {
        return self::uploadFile($file);
    }
    
    // 修改文件处理函数
    public static function modifyHandle($content, $file)
    {
        return self::uploadFile($file, $content);
    }
    
    // 删除文件
    public static function deleteHandle(array $content)
    {
        self::deleteFile($content['attachment']->path);
    }
    
    // 获取实际文件绝对访问路径
    public static function attachmentHandle(array $content)
    {
        $option = self::getConfig();
        $isImage = self::isImage($content['attachment']->type);
        return Typecho_Common::url(
            $content['attachment']->path,
            $isImage ? $option->domain : $option->nonimg_domain
        ) . ($isImage ? $option->style : '');
    }
}
