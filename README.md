# Execute Magento's setup scripts manually
This module will prevent the automatic execution of Magento modules setup script. Instead, you will choose when to execute them.  
There are 2 options to run the setup scripts
* an admin interface: system > tools > Manual setup scripts execution
* by shell: php -f shell/doWhileTrue/manualSetupScriptsExecution.php -- -maintenance true|false

Highly inspired by http://vinaikopp.com/2014/11/03/magento-setup-scripts/
