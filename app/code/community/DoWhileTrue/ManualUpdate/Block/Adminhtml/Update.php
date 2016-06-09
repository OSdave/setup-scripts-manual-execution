<?php

class DoWhileTrue_ManualUpdate_Block_Adminhtml_Update extends Mage_Adminhtml_Block_Widget
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('dowhiletrue/manual_update/form.phtml');
    }

    public function getPendingUpdates()
    {
        $modules           = array();
        $resources         = Mage::getConfig()->getNode('global/resources')->children();
        $connection        = Mage::getSingleton('core/resource')->getConnection('write');
        $coreResourceTable = Mage::getSingleton('core/resource')->getTableName('core_resource');
        $query             = "SELECT * FROM `" . $coreResourceTable . "`;";
        $allResources      = $connection->fetchAssoc($query);
        foreach ($resources as $resName => $resource) {
            if (!$resource->setup) {
                continue;
            }

            if (!isset($allResources[$resName]) || !isset($allResources[$resName]['version'])) {
                $installedVersion = 0;
                $actionType       = 'install';
            } else {
                $installedVersion = $allResources[$resName]['version'];
                $actionType       = 'upgrade';
            }
            $module        = (string) $resource->setup->module;
            $moduleVersion = (string) Mage::getConfig()->getNode()->modules->$module->version;
            $status        = version_compare($moduleVersion, $installedVersion);
            if ($status == Mage_Core_Model_Resource_Setup::VERSION_COMPARE_GREATER) {
                $modules[$resName]['module_version']    = (string) Mage::getConfig()->getNode()->modules->$module->version;
                $modules[$resName]['module_name']       = $module;
                $modules[$resName]['installed_version'] = (isset($allResources[$resName]) && isset($allResources[$resName]['version'])) ? $allResources[$resName]['version'] : 0;
                $modules[$resName]                      = $this->_getSetupFiles($modules[$resName], $resName, $actionType);
                $modules[$resName]                      = $this->_getAvailableDataFiles($modules[$resName], $resName, $actionType);
            }
        }

        return $modules;
    }

    public function fileAsLink($path)
    {
        if (Mage::helper('core')->isModuleOutputEnabled('DoWhileTrue_BetterLog')) {
            $pattern = '|(.+)|';
            return Mage::helper('dwt_log')->IDELink($path, $pattern);
        } else {
            return $path;
        }
    }

    private function _getSetupFiles($module, $resName, $actionType)
    {
        $filesDir = Mage::getModuleDir('sql', $module['module_name']) . DS . $resName;
        if (!is_dir($filesDir) || !is_readable($filesDir)) {
            return array();
        }

        $dbFiles    = array();
        $typeFiles  = array();
        $regExpDb   = sprintf('#^%s-(.*)\.(php|sql)$#i', $actionType);
        $regExpType = sprintf('#^%s-%s-(.*)\.(php|sql)$#i', 'mysql4', $actionType);
        $handlerDir = dir($filesDir);
        while (false !== ($file       = $handlerDir->read())) {
            $matches = array();
            if (preg_match($regExpDb, $file, $matches)) {
                $dbFiles[$matches[1]] = $filesDir . DS . $file;
            } else if (preg_match($regExpType, $file, $matches)) {
                $typeFiles[$matches[1]] = $filesDir . DS . $file;
            }
        }
        $handlerDir->close();

        if (empty($typeFiles) && empty($dbFiles)) {
            return $module;
        }

        foreach ($typeFiles as $version => $file) {
            $dbFiles[$version] = $file;
        }

        $allFiles = $this->_getModifySqlFiles($actionType, $module['installed_version'], $module['module_version'], $dbFiles);

        $module['files'] = $allFiles;

        return $module;
    }

    protected function _getAvailableDataFiles($module, $resName, $actionType)
    {
        $files = array();

        $filesDir = Mage::getModuleDir('data', $module['module_name']) . DS . $resName;
        if (is_dir($filesDir) && is_readable($filesDir)) {
            $regExp     = sprintf('#^%s-(.*)\.php$#i', 'data-' . $actionType);
            $handlerDir = dir($filesDir);
            while (false !== ($file       = $handlerDir->read())) {
                $matches = array();
                if (preg_match($regExp, $file, $matches)) {
                    $files[$matches[1]] = $filesDir . DS . $file;
                }
            }
            $handlerDir->close();
        }

        if (empty($files)) {
            return $module;
        }

        $allFiles = array_merge($module['files'], $this->_getModifySqlFiles($actionType, $module['installed_version'], $module['module_version'], $files));

        $module['files'] = $allFiles;

        return $module;
    }

    protected function _getModifySqlFiles($actionType, $fromVersion, $toVersion, $arrFiles)
    {
        $arrRes = array();
        switch ($actionType) {
            case Mage_Core_Model_Resource_Setup::TYPE_DB_INSTALL:
            case Mage_Core_Model_Resource_Setup::TYPE_DATA_INSTALL:
                uksort($arrFiles, 'version_compare');
                foreach ($arrFiles as $version => $file) {
                    if (version_compare($version, $toVersion) !== Mage_Core_Model_Resource_Setup::VERSION_COMPARE_GREATER) {
                        $arrRes[0] = array(
                            'toVersion' => $version,
                            'fileName'  => $file
                        );
                    }
                }
                break;

            case Mage_Core_Model_Resource_Setup::TYPE_DB_UPGRADE:
            case Mage_Core_Model_Resource_Setup::TYPE_DATA_UPGRADE:
                uksort($arrFiles, 'version_compare');
                foreach ($arrFiles as $version => $file) {
                    $versionInfo = explode('-', $version);

                    // In array must be 2 elements: 0 => version from, 1 => version to
                    if (count($versionInfo) != 2) {
                        break;
                    }
                    $infoFrom = $versionInfo[0];
                    $infoTo   = $versionInfo[1];
                    if (version_compare($infoFrom, $fromVersion) !== Mage_Core_Model_Resource_Setup::VERSION_COMPARE_LOWER && version_compare($infoTo, $toVersion) !== Mage_Core_Model_Resource_Setup::VERSION_COMPARE_GREATER) {
                        $arrRes[] = array(
                            'toVersion' => $infoTo,
                            'fileName'  => $file
                        );
                    }
                }
                break;

            case Mage_Core_Model_Resource_Setup::TYPE_DB_ROLLBACK:
                break;

            case Mage_Core_Model_Resource_Setup::TYPE_DB_UNINSTALL:
                break;
        }
        return $arrRes;
    }

}
