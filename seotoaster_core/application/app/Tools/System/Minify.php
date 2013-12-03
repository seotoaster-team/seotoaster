<?php
/**
 * Minify.php
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Tools_System_Minify {

	public static function minify($list, $concat = false){
		switch ($list) {
			case ($list instanceof Zend_View_Helper_HeadLink):
				return self::minifyCss($list, $concat);
				break;
			case ($list instanceof Zend_View_Helper_HeadScript):
				return self::minifyJs($list);
				break;
		}
	}

	public static function minifyCss($cssList, $concat = false) {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('website');
		$cacheHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('cache');
		if (null === ($hashStack = $cacheHelper->load(strtolower(__CLASS__), ''))){
			$hashStack = array();
		}

		$container = $cssList->getContainer();
		foreach ($container->getArrayCopy() as $css) {
			if (preg_match('/^https?:\/\//',$css->href) != false && strpos($css->href, $websiteHelper->getUrl()) !== 0) {
				continue;
			}
			$path = str_replace($websiteHelper->getUrl(), '', $css->href);
            if(!file_exists($websiteHelper->getPath() . $path)) {
                continue;
            }
			$hash = sha1_file($websiteHelper->getPath() . $path);
			$name = Tools_Filesystem_Tools::basename($path);

			if (!$hash){
				continue;
			}

			if (!isset($hashStack[$path]) || $hashStack[$path]['hash'] !== $hash){
                 $compressor = new CssMin();
				$cssContent = Tools_Filesystem_Tools::getFile($path);
				$cssContent = preg_replace('/url\([\'"]?([^)\'"]*)[\'"]?\)/', 'url("../'.dirname($path).DIRECTORY_SEPARATOR.'${1}")', $cssContent);
				$hashStack[$path] = array(
					'hash' => $hash,
					'content' => $compressor->run($cssContent)
				);

				Tools_Filesystem_Tools::saveFile($websiteHelper->getPath().$websiteHelper->getTmp().$hash.'.css', $hashStack[$path]['content']);
				unset($cssContent);
			}

			if (!$concat){
				$css->href = $websiteHelper->getUrl().$websiteHelper->getTmp().$hash.'.css?'.$name;
			} else {
				$concatCss = isset($concatCss) ? $concatCss.PHP_EOL."/* $path */".PHP_EOL.$hashStack[$path]['content'] : "/* $path */".PHP_EOL.$hashStack[$path]['content'];
			}

		}

		if (isset($concatCss) && !empty($concatCss)){
			$cname = sha1($concatCss).'.concat.min.css';
			$concatPath = $websiteHelper->getPath().$websiteHelper->getTmp().$cname;
			if (!file_exists($concatPath) || sha1_file($concatPath) !== sha1($concatCss)){
				Tools_Filesystem_Tools::saveFile($concatPath, $concatCss);
			}
			$cssList->setStylesheet($websiteHelper->getUrl().$websiteHelper->getTmp().$cname);
		}

		$cacheHelper->save(strtolower(__CLASS__), $hashStack, '', array(), Helpers_Action_Cache::CACHE_LONG);

		return $cssList;
	}

	public static function minifyJs($jsList){
		$websiteHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('website');
		$cacheHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('cache');
		if (null === ($hashStack = $cacheHelper->load(strtolower(__CLASS__), ''))){
			$hashStack = array();
		}

		$container = $jsList->getContainer();
		foreach ($container->getArrayCopy() as $js) {
			if (isset($js->attributes['src'])){
				if (strpos($js->attributes['src'], $websiteHelper->getUrl()) === false ){
					continue; //ignore file if file from remote
				}
				if (isset($js->attributes['nominify']) || preg_match('/min\.js$/', $js->attributes['src']) != false ){
					continue; //ignore file if special attribute given or src ends with 'min.js'
				}

				$path   = str_replace($websiteHelper->getUrl(), '', $js->attributes['src']);
                if(!file_exists($websiteHelper->getPath() . $path))  {
                    continue;
                }
				$hash   = sha1_file($websiteHelper->getPath().$path);
				if (!isset($hashStack[$path]) || $hashStack[$path]['hash'] !== $hash){
					$hashStack[$path] = array(
						'hash' => $hash,
						'content' => JSMin::minify(Tools_Filesystem_Tools::getFile($websiteHelper->getPath().$path))
					);

					Tools_Filesystem_Tools::saveFile($websiteHelper->getPath().$websiteHelper->getTmp().$hash.'.min.js', $hashStack[$path]['content']);
				}

				$js->attributes['src'] = $websiteHelper->getUrl().$websiteHelper->getTmp().$hash.'.min.js?'.Tools_Filesystem_Tools::basename($path);

			} elseif (!empty($js->source)) {
				if (!isset($js->attributes['nominify'])){
					$js->source = JSMin::minify($js->source);
				}
			}
		}

		$cacheHelper->save(strtolower(__CLASS__), $hashStack, '', array(), Helpers_Action_Cache::CACHE_LONG);

		return $jsList;
	}
}
