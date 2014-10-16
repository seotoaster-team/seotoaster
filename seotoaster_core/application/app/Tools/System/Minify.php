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

        $cacheHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('cache');
        $cacheKey    = strtolower(__CLASS__.'_'.__FUNCTION__);
        if (null === ($hashStack = $cacheHelper->load($cacheKey, ''))) {
            $hashStack = array();
        }

        $container       = $cssList->getContainer();
        $compressor      = new CssMin();
        $concatCssPrefix = MagicSpaces_Concatcss_Concatcss::FILE_NAME_PREFIX;
        $websiteHelper   = Zend_Controller_Action_HelperBroker::getExistingHelper('website');
        $concatCss       = '';
        foreach ($container->getArrayCopy() as $key => $css) {
            if ((bool)preg_match('/^https?:\/\//', $css->href) !== false
                && (bool)strpos($css->href, $websiteHelper->getUrl()) !== false
            ) {
                continue;
            }

            $path = str_replace($websiteHelper->getUrl(), '', str_replace('%20', ' ', $css->href));
            // Check file exists
            if (!is_file($websiteHelper->getPath().$path) || !file_exists($websiteHelper->getPath().$path)) {
                continue;
            }

            if (!($hash = sha1_file($websiteHelper->getPath().$path))) {
                continue;
            }

            if (!isset($hashStack[$path]) || $hashStack[$path]['hash'] !== $hash) {
                $cssContent = Tools_Filesystem_Tools::getFile($path);
                $cssContent = preg_replace(
                    '/url\([\'"]?((?!\w+:\/\/|data:)([^)\'"]*))[\'"]?\)/',
                    'url("../'.dirname($path).'/${1}")',
                    $cssContent
                );

                // Ignoring minify files created by magicspace concatcss
                if ((bool)preg_match('/'.$concatCssPrefix.'[a-zA-Z0-9]+\.css/i', $path) === false) {
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
                $concatCss .= '/**** '.strtoupper($path).' start ****/'.PHP_EOL.$hashStack[$path]['content'].PHP_EOL
                    .'/**** '.strtoupper($path).' end ****/'.PHP_EOL;
            }

            // Offset minify css
            $cssList->offsetUnset($key);
        }

        if (isset($concatCss) && !empty($concatCss)) {
            $cname      = sha1($concatCss).'.concat.min.css';
            $concatPath = $websiteHelper->getPath().$websiteHelper->getTmp().$cname;

            if (!file_exists($concatPath) || sha1_file($concatPath) !== sha1($concatCss)) {
                Tools_Filesystem_Tools::saveFile($concatPath, $concatCss);
            }

            $cssList->appendStylesheet($websiteHelper->getUrl().$websiteHelper->getTmp().$cname);
        }

        $cacheHelper->save($cacheKey, $hashStack, '', array(), Helpers_Action_Cache::CACHE_LONG);

        return $cssList;
    }

    public static function minifyJs($jsList, $concat = false) {
        $cacheHelper   = Zend_Controller_Action_HelperBroker::getExistingHelper('cache');
        $cacheKey      = strtolower(__CLASS__.'_'.__FUNCTION__);
        if (null === ($hashStack = $cacheHelper->load($cacheKey, ''))) {
            $hashStack = array();
        }

        $container     = $jsList->getContainer();
        $websiteHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('website');
        foreach ($container->getArrayCopy() as $js) {
            if (isset($js->attributes['src'])) {
                // Ignore file if file from remote
                if (strpos($js->attributes['src'], $websiteHelper->getUrl()) === false) {
                    continue;
                }

                $path = str_replace($websiteHelper->getUrl(), '', $js->attributes['src']);
                // Check file exists
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
                $jsContent = $js->source;
                $hash      = md5($jsContent);
                $path      = 'source_'.$hash;
                if (!isset($hashStack[$path]) || $hashStack[$path]['hash'] !== $hash) {
                    if (!isset($js->attributes['nominify'])) {
                        $jsContent  = JSMin::minify($jsContent);
                        $js->source = $jsContent;
                    }
                    
                    $hashStack[$path] = array(
                        'hash'    => $hash,
                        'content' => $jsContent
                    );
                }
                if ($concat) {
                    $concatJs = isset($concatJs)
                        ? $concatJs.PHP_EOL."/* Source JS */".PHP_EOL.$hashStack[$path]['content']
                        : "/* Source JS */".PHP_EOL.$hashStack[$path]['content'];
                }
            }
            unset($jsContent);
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

        $cacheHelper->save($cacheKey, $hashStack, '', array(), Helpers_Action_Cache::CACHE_LONG);

        return $jsList;
    }
}
