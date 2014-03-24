<?php
/**
 * Minify.php
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Tools_System_Minify {

    public static function minify($list, $concat = false) {
        switch ($list) {
            case ($list instanceof Zend_View_Helper_HeadLink):
                return self::minifyCss($list, $concat);
                break;
            case ($list instanceof Zend_View_Helper_HeadScript):
                return self::minifyJs($list, $concat);
                break;
        }
    }

    public static function minifyCss($cssList, $concat = false) {
        // Detect version IE < 9
        if (!Tools_System_Tools::isBrowserIe()) {
            return $cssList;
        }

        $websiteHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('website');
        $cacheHelper   = Zend_Controller_Action_HelperBroker::getExistingHelper('cache');
        if (null === ($hashStack = $cacheHelper->load(strtolower(__CLASS__), ''))) {
            $hashStack = array();
        }

        $container       = $cssList->getContainer();
        $compressor      = new CssMin();
        $concatCssPrefix = MagicSpaces_Concatcss_Concatcss::FILE_NAME_PREFIX;
        foreach ($container->getArrayCopy() as $css) {
            if (preg_match('/^https?:\/\//', $css->href) != false
                && strpos($css->href, $websiteHelper->getUrl()) !== 0) {
                continue;
            }

            $path = str_replace($websiteHelper->getUrl(), '', $css->href);
            if (!is_file($websiteHelper->getPath().$path) || !file_exists($websiteHelper->getPath().$path)) {
                continue;
            }

            $hash = sha1_file($websiteHelper->getPath().$path);
            if (!$hash) {
                continue;
            }

            if (!isset($hashStack[$path]) || $hashStack[$path]['hash'] !== $hash) {
                $cssContent = Tools_Filesystem_Tools::getFile($path);
                $cssContent = preg_replace(
                    '/url\([\'"]?([^)\'"]*)[\'"]?\)/',
                    'url("../'.dirname($path).DIRECTORY_SEPARATOR.'${1}")',
                    $cssContent
                );

                // Ignoring files generated magic space concatcss
                if ((bool) preg_match('/'.$concatCssPrefix.'[a-zA-Z0-9]+\.css/i', $path) === false) {
                    $cssContent = $compressor->run($cssContent);
                }

                $hashStack[$path] = array(
                    'hash'    => $hash,
                    'content' => $cssContent
                );

                Tools_Filesystem_Tools::saveFile(
                    $websiteHelper->getPath().$websiteHelper->getTmp().$hash.'.css', 
                    $hashStack[$path]['content']
                );
                unset($cssContent);
            }

            if (!$concat) {
                $css->href = $websiteHelper->getUrl().$websiteHelper->getTmp().$hash.'.css?'
                    .Tools_Filesystem_Tools::basename($path);
            }
            else {
                $concatCss = isset($concatCss) ? $concatCss.PHP_EOL."/* $path */".PHP_EOL.$hashStack[$path]['content'] 
                    : "/* $path */".PHP_EOL.$hashStack[$path]['content'];
            }
        }

        if (isset($concatCss) && !empty($concatCss)) {
            $cname      = sha1($concatCss).'.concat.min.css';
            $concatPath = $websiteHelper->getPath().$websiteHelper->getTmp().$cname;

            if (!file_exists($concatPath) || sha1_file($concatPath) !== sha1($concatCss)) {
                Tools_Filesystem_Tools::saveFile($concatPath, $concatCss);
            }

            $cssList->setStylesheet($websiteHelper->getUrl().$websiteHelper->getTmp().$cname);
        }

        $cacheHelper->save(strtolower(__CLASS__), $hashStack, '', array(), Helpers_Action_Cache::CACHE_LONG);

        return $cssList;
    }

    public static function minifyJs($jsList, $concat = false) {
        $websiteHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('website');
        $cacheHelper   = Zend_Controller_Action_HelperBroker::getExistingHelper('cache');
        if (null === ($hashStack = $cacheHelper->load(strtolower(__CLASS__), ''))) {
            $hashStack = array();
        }

        $container = $jsList->getContainer();
        foreach ($container->getArrayCopy() as $js) {
            if (isset($js->attributes['src'])) {
                // Ignore file if file from remote
                if (strpos($js->attributes['src'], $websiteHelper->getUrl()) === false) {
                    continue;
                }

                // Check file exists
                $path = str_replace($websiteHelper->getUrl(), '', $js->attributes['src']);
                if (!is_file($websiteHelper->getPath().$path) || !file_exists($websiteHelper->getPath().$path)) {
                    continue;
                }

                $hash = sha1_file($websiteHelper->getPath().$path);
                if (!isset($hashStack[$path]) || $hashStack[$path]['hash'] !== $hash) {
                    // Ignore file if special attribute given or src ends with 'min.js'
                    $jsContent = Tools_Filesystem_Tools::getFile($websiteHelper->getPath().$path);
                    if (isset($js->attributes['nominify'])
                        || (bool) preg_match('/min\.js$/', $js->attributes['src']) === false
                    ) {
                        $jsContent = JSMin::minify($jsContent);
                    }

                    $hashStack[$path] = array(
                        'hash'    => $hash,
                        'content' => $jsContent
                    );
                    unset($jsContent);

                    Tools_Filesystem_Tools::saveFile(
                        $websiteHelper->getPath().$websiteHelper->getTmp().$hash.'.min.js',
                        $hashStack[$path]['content']
                    );
                }

                if (!$concat) {
                    $js->attributes['src'] = $websiteHelper->getUrl().$websiteHelper->getTmp().$hash.'.min.js?'
                        .Tools_Filesystem_Tools::basename($path);
                }
                else {
                    $concatJs = isset($concatJs) ? $concatJs.PHP_EOL."/* $path */".PHP_EOL.$hashStack[$path]['content']
                        : "/* $path */".PHP_EOL.$hashStack[$path]['content'];
                }

            }
            elseif (!empty($js->source)) {
                $contentSource = $js->source;
                if (!isset($js->attributes['nominify'])) {
                    $contentSource = JSMin::minify($contentSource);
                    $js->source    = $contentSource;
                }
                if ($concat) {
                    $concatJs = isset($concatJs) ? $concatJs.PHP_EOL."/* Source JS */".PHP_EOL.$contentSource
                        : "/* Source JS */".PHP_EOL.$contentSource;
                }
            }
        }

        if (isset($concatJs)) {
            $cname      = sha1($concatJs).'.concat.min.js';
            $concatPath = $websiteHelper->getPath().$websiteHelper->getTmp().$cname;

            if (!file_exists($concatPath) || sha1_file($concatPath) !== sha1($concatJs)) {
                Tools_Filesystem_Tools::saveFile($concatPath, $concatJs);
            }

            $jsList->exchangeArray(array());
            $jsList->appendFile($websiteHelper->getUrl().$websiteHelper->getTmp().$cname);
        }

        $cacheHelper->save(strtolower(__CLASS__), $hashStack, '', array(), Helpers_Action_Cache::CACHE_LONG);

        return $jsList;
    }
}
