<?php
/**
 * Created by PhpStorm.
 * @Author: 天上
 * @Time  : 2020/4/30 6:32
 * @Email : 30191306465@qq.com
 */

namespace app;

use Nesk\Rialto\Data\JsFunction;
use QL\Ext\Chrome;
use QL\QueryList;

/**
 * 网易云音乐爬虫
 * Class NetEaseCloudMusic
 * @package app\admin\reptile
 */
class NetEaseCloudMusic{


    /**
     * 歌单爬虫
     * @param $url
     * @param array|string $options 需要拿去的信息
     * @return array|null
     */
    public static function songList($url,$options = ['name']){


        $query = self::go(function ($page) use ($url){
            // 获取页面HTML内容
            $page->goto($url);
            /**
             * 网易云歌单的内容实际是在一个 iframe中
             * 所以 这里切入到 那个iframe 中
             */
            $iframe = $page->mainFrame()->querySelector('#g_iframe')->contentFrame();
            /**
             * 然后再切入到 歌单表格
             * 注入 JS 方法 获取表格 HTML
             */
            return $iframe->querySelectorEval('table.m-table tbody',JsFunction::createWithParameters(['element'])
                ->body("return element.innerHTML"));
        });

        /**
         * 拿到html内容
         * 这里用的是正则匹配。
         * 并没用 QueryList 操作DOM
         * 需要操作DOM请查看 QueryList 文档
         * http://www.querylist.cc/docs/guide/v4/overview
         */
        $html = $query->getHtml();
        if (empty($html)) return null;


        if (is_string($options)){
            $options = [$options];
        }
        $result = [];
        foreach ($options as $val){
            foreach (self::songListMatch($val,$html) as $k=>$item){
                $result[$k][$val] = $item;
            }
        }
        return $result;
    }

    /**
     * 匹配歌单内容
     * @param string $type 值为 $pattern的key
     * @param string $str 要查找的字符串
     * @return array|mixed
     */
    private static function songListMatch($type,&$str){
        $pattern = [
            // 正则匹配歌名
            'name'          => '/<a\s*href="\/song\?id=[0-9]+">\s*<b\s*title="([^"]+)"/i',
            // 匹配时长
            'time'          => '/<td\s*class="\s*s-fc3">\s*<span\s*class="u-dur\s*">\s*([0-9|:]+)\s*<\/span>/i',
            // 链接
            'url'           => '/<a\s*href="(\/song\?id=[0-9]+)">/i',
            // 作者
            'author'        => '/<td\s*class="\s*"><div\s*class="text"\s*title="([^"]+)">\s*<span\s*title="[^"]*">/i',
            // 专辑
            'album'         => '/<td\s*class="\s*"><div\s*class="text"><a\s*href="\/album\?id=[0-9]*"\s*title="([^"]+)">/i'
            // ...
        ];

        if ($pattern[$type] && preg_match_all($pattern[$type],$str,$arr)){
            // 下标为 1 的才是匹配到元素
            foreach ($arr[1] as &$item){
                // 将HTML 实体转换为字符
                $item = htmlspecialchars_decode($item);
            }
            return $arr[1];
        }
        return [];
    }


    /**
     * 开始
     * @param callable $callback 需要执行方法
     * @param array $options 选项
     * @return QueryList
     */
    public static function go(callable $callback,$options = []){

        $ql = QueryList::getInstance();
        // 注册插件，默认注册的方法名为: chrome
        $ql->use(Chrome::class,'chrome');

        /**
         * 是否在 无头模式 下运行浏览器。默认是 true 除非 devtools 选项是 true
         * 这里设为false 然后程序暂停一定的时间可以看到浏览器的启动
         * 可选参数 api 地址
         * https://zhaoqize.github.io/puppeteer-api-zh_CN/#?product=Puppeteer&version=v2.1.1&show=api-puppeteerdefaultargsoptions
         */
        $options['headless'] = false;
        /**
         * linux 下 禁用 沙盒
         */
//        $options['args'] = ['--no-sandbox'];
        $query = $ql->chrome(function ($page,$browser) use ($callback) {
            // 模拟浏览器
            $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36';
            $page->setUserAgent($userAgent);
            /**
             * 暂停10s 看到chrome浏览器启动
             */
            sleep(10);

            $html = $callback($page,$browser);
            // 关闭浏览器
            $browser->close();
            // 返回值一定要是页面的HTML内容
            return $html;
        },$options);

        return $query;
    }
}