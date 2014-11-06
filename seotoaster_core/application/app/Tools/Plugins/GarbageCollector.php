<?php

/**
 * GarbageCollector
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Plugins_GarbageCollector extends Tools_System_GarbageCollector
{

    protected function _runOnDefault()
    {
    }

    protected function _runOnUpdate()
    {
        if ($this->_object->getStatus() === Application_Model_Models_Plugin::ENABLED) {
            Tools_Plugins_Tools::registerPluginsIncludePath(true);
        }
        Application_Model_Mappers_EmailTriggersMapper::getInstance()->toggleTriggersStatuses(
            $this->_object->getName(),
            $this->_object->getStatus()
        );
    }

    protected function _runOnDelete()
    {
        $this->_removePluginOccurrences();
        Tools_Plugins_Tools::removePluginRoute($this->_object->getName());
        $cacheHelper = new Helpers_Action_Cache();
        $cacheHelper->init();
        $cacheHelper->clean();
        //Application_Model_Mappers_EmailTriggersMapper::getInstance()->unregisterTriggers($this->_object->getName());
        //Application_Model_Mappers_EmailTriggersMapper::getInstance()->unregisterRecipients($this->_object->getName());
    }

    protected function _runOnCreate()
    {
        Tools_Plugins_Tools::registerPluginsIncludePath(true);
        Tools_Plugins_Tools::registerPluginRoute($this->_object->getName());
        Application_Model_Mappers_EmailTriggersMapper::getInstance()->registerTriggers($this->_object->getName());
        Application_Model_Mappers_EmailTriggersMapper::getInstance()->registerRecipients($this->_object->getName());
    }

    private function _removePluginOccurrences()
    {
        $name = $this->_object->getName();
        $patterns = array('~{\$plugin:' . $name . '[^{}]*}~usU');

        //removing plugin occurrences from content
        $miscData = Zend_Registry::get('misc');
        $containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $pluginDirectory = $websiteHelper->getPath() . $miscData['pluginsPath'] . strtolower($name);
        $pluginAppPath = $pluginDirectory . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
        $widgetPath = $pluginAppPath . 'Widgets';
        $magicSpacePath = $pluginAppPath . 'MagicSpaces';
        $adapter = $containerMapper->getDbTable()->getAdapter();
        $where = $adapter->quoteInto(
            'content ?',
            new Zend_Db_Expr("REGEXP '({[[.dollar-sign.]]plugin[[.colon.]]" . $name . "[[.colon.]].*})'")
        );

        if (is_dir($widgetPath)) {
            $widgetNames = Tools_Filesystem_Tools::scanDirectory($widgetPath, false, false);
            if (!empty($widgetNames)) {
                foreach ($widgetNames as $widgetName) {
                    $patterns[] = '~{\$' . strtolower($widgetName) . ':[^{}]*}~usU';
                    $where .= ' OR ' . $adapter->quoteInto(
                            'content ?',
                            new Zend_Db_Expr("REGEXP '({[[.dollar-sign.]]" . strtolower(
                                    $widgetName
                                ) . "[[.colon.]].*})'")
                        );
                }
            }
        }
        if (is_dir($magicSpacePath)) {
            $magicSpaceNames = Tools_Filesystem_Tools::scanDirectory($magicSpacePath, false, false);
            if (!empty($magicSpaceNames)) {
                foreach ($magicSpaceNames as $magicSpaceName) {
                    $patterns[] = '~{' . strtolower($magicSpaceName) . '.*}.*{\/' . strtolower($magicSpaceName) . '}~usU';
                    $where .= ' OR ' . $adapter->quoteInto(
                            'content ?',
                            new Zend_Db_Expr("REGEXP '({" . strtolower(
                                    $magicSpaceName
                                ) . ".*}.*{/" . strtolower(
                                    $magicSpaceName) . "})'")
                        );
                }
            }
        }

        $containers = $containerMapper->fetchAll($where);
        if (!empty ($containers)) {
            array_walk(
                $containers,
                function ($container, $key, $data) {
                    foreach ($data['patterns'] as $pattern) {
                        $container->setContent(preg_replace($pattern, '', $container->getContent()));
                    }
                    $data['mapper']->save($container);
                },
                array('patterns' => $patterns, 'mapper' => $containerMapper)
            );
        }

        unset($containers);

        //removing plugin occurrences from the templates
        $templateMapper = Application_Model_Mappers_TemplateMapper::getInstance();
        $templates = $templateMapper->fetchAll($where);
        if (!empty ($templates)) {
            array_walk(
                $templates,
                function ($template, $key, $data) {
                    foreach ($data['patterns'] as $pattern) {
                        $template->setContent(preg_replace($pattern, '', $template->getContent()));
                    }
                    $data['mapper']->save($template);
                },
                array('patterns' => $patterns, 'mapper' => $templateMapper)
            );
        }
        unset($templates);
    }

}

