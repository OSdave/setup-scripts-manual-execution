<?php

class DoWhileTrue_ManualUpdate_Adminhtml_UpdateController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/tools/dwt_update');
    }

    public function indexAction()
    {
        $this->_title($this->__('System'))->_title($this->__('Tools'))->_title($this->__('Manual setup scripts execution'));

        $this->loadLayout();
        $this->_setActiveMenu('system/tools/dwt_update');
        $this->_addBreadcrumb(Mage::helper('dwt_update')->__('Manual setup scripts execution'), Mage::helper('dwt_update')->__('Manual setup scripts execution'));

        $this->_addContent($this->getLayout()->createBlock('dwt_update/adminhtml_update'));
        $this->renderLayout();
    }

    public function executeAction()
    {
        $maintenance = $this->getRequest()->getParam('maintenance', false);
        if ($maintenance) {
            fopen(Mage::getBaseDir() . DS . 'maintenance.flag', 'w');
        }

        Mage_Core_Model_Resource_Setup::applyAllUpdates();
        Mage_Core_Model_Resource_Setup::applyAllDataUpdates();

        if ($maintenance) {
            unlink(Mage::getBaseDir() . DS . 'maintenance.flag');
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('dwt_update')->__('The setup scripts have been executed.'));
        $this->_redirect('*/*/');
    }

}
