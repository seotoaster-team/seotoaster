<?php

/**
 * Zend framework plugin to register seotoaster plugins routes and hooks
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Plugins_Plugin extends Zend_Controller_Plugin_Abstract
{

    /**
     * Seotoaster before controller plugin hook name
     *
     */
    const PREDISPATCH_METHOD = 'beforeController';

    /**
     * Seotoaster after controller plugin hook name
     *
     */
    const POSTDISPATCH_METHOD = 'afterController';

    /**
     * Seotoaster before router plugin hook name
     *
     */
    const BEFOREROUTER_METHOD = 'beforeRouter';

    /**
     * Register Seotoaster plugins routes and hooks
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        // register plugins to include path
        Tools_Plugins_Tools::registerPluginsIncludePath();
        // register plugins translations
        Tools_Plugins_Tools::registerPluginsTranslations();
        //trigger plugins before router starts
        $this->_callPlugins(self::BEFOREROUTER_METHOD);
    }

    /**
     * Trigger pre dispatch plugins hooks
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_callPlugins(self::PREDISPATCH_METHOD);
    }

    /**
     * Trigger post dispatch plugins hooks
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->_callPlugins(self::POSTDISPATCH_METHOD);

        //replace http with https for internal links if requested via https
        if ($request->isSecure()) {
            $websiteConfig = Zend_Registry::get('website');
            $body = strtr(
                $this->_response->getBody(),
                array(
                    Zend_Controller_Request_Http::SCHEME_HTTP . '://' . $websiteConfig['url'] => Zend_Controller_Request_Http::SCHEME_HTTPS . '://' . $websiteConfig['url']
                )
            );
            $this->_response->setBody($body);
        }
    }

    /**
     * Trigger Seotoaster plugins hooks
     *
     * @param $method string Method to trigger
     */
    private function _callPlugins($method)
    {
        $enabledPlugins = Tools_Plugins_Tools::getEnabledPlugins();
        $pluginPath = Tools_Plugins_Tools::getPluginsPath();
        $websiteUrl = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
        if (is_array($enabledPlugins) && !empty ($enabledPlugins)) {
            array_walk(
                $enabledPlugins,
                function ($plugin) use ($method, $websiteUrl, $pluginPath) {
                    try {
                        $name = $plugin->getName();
                        $pluginBootstrap = $pluginPath.$name.DIRECTORY_SEPARATOR.ucfirst($name).'.php';
                        if (is_readable($pluginBootstrap)) {
                            require_once $pluginBootstrap;
                        }
                        $reflection = new Zend_Reflection_Class(ucfirst($name));

                        if ($reflection->hasMethod($method)) {
                            $pluginInstance = Tools_Factory_PluginFactory::createPlugin(
                                $plugin->getName(),
                                array(),
                                array('websiteUrl' => $websiteUrl)
                            );
                            $pluginInstance->$method();
                        }

                    } catch (Exceptions_SeotoasterException $se) {
                        error_log($se->getMessage());
                        error_log($se->getTraceAsString());
                    }
                }
            );
        }
    }
}

