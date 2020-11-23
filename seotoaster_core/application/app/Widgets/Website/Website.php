<?php
/**
 * Description of Website
 *
 * @author iamne
 */
class Widgets_Website_Website extends Widgets_Abstract {

	const OPT_URL = 'url';

    /**
     * Return website host
     */
    const OPT_HOST = 'host';

    /**
     * Return website domain
     */
    const OPT_DOMAIN = 'domain';

	protected function  _load() {
		$content = '';
		$type    = $this->_options[0];
		switch ($type) {
			case self::OPT_URL:
				$content = $this->_toasterOptions['websiteUrl'];
			break;
            case self::OPT_HOST:
                $content = Tools_System_Tools::getUrlHost(str_replace('www.', '', $this->_toasterOptions['websiteUrl']));
                break;
            case self::OPT_DOMAIN:
                $content = $this->_getDomainFromUrl($this->_toasterOptions['websiteUrl']);
                break;
		}
		return $content;
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Website url'),
				'option' => 'website:url'
			),
            array(
                'alias'   => $translator->translate('Website host name'),
                'option' => 'website:host'
            ),
            array(
                'alias'   => $translator->translate('Website domain name'),
                'option' => 'website:domain'
            )
		);
	}

    /**
     * Get domain name from url
     *
     * @param string $url url
     * @return string
     */
    protected function _getDomainFromUrl($url)
    {
        $urlParts = parse_url($url);
        if (!empty($urlParts['host'])) {
            if (preg_match('/(?<domain>[a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $urlParts['host'], $matches)) {
                if (!empty($matches['domain'])) {
                    return $matches['domain'];
                }
            }
        }

        return '';
    }

}

