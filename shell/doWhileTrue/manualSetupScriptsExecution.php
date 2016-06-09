<?php

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'abstract.php';

class DoWhileTrue_Shell_ManualSetupScriptsExecution extends Mage_Shell_Abstract
{

    public function run()
    {
        $maintenance = $this->getArg('maintenance');
        if ($maintenance === FALSE) {
            echo Mage::helper('dwt_update')->__('Please specify if you want put the store in maintenance while the setup scripts are executed') . PHP_EOL;
        } else {
            if ($maintenance == 'true') {
                echo Mage::helper('dwt_update')->__('Creating maintenance.flag') . PHP_EOL;
                fopen(Mage::getBaseDir() . DS . 'maintenance.flag', 'w');
            }

            Mage_Core_Model_Resource_Setup::applyAllUpdates();
            Mage_Core_Model_Resource_Setup::applyAllDataUpdates();

            if ($maintenance == 'true') {
                echo Mage::helper('dwt_update')->__('Removing maintenance.flag') . PHP_EOL;
                unlink(Mage::getBaseDir() . DS . 'maintenance.flag');
            }
            echo Mage::helper('dwt_update')->__('The setup scripts have been executed') . PHP_EOL;
        }
    }

    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f shell/doWhileTrue/manualSetupScriptsExecution.php -- -maintenance true|false

USAGE;
    }

}

$shell = new DoWhileTrue_Shell_ManualSetupScriptsExecution();
$shell->run();
