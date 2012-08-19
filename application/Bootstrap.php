<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	
    protected function _initAutoloader()
    {
        $autoloader = $this->getApplication()->getAutoloader();
        $autoloader->suppressNotFoundWarnings(false);
        $autoloader->registerNamespace('App_');
    }
	
    protected function _initSettings()
    {
        Zend_Registry::set('settings', $this->getApplication()->getOptions());
        Zend_Registry::set('apiKey', $this->getApplication()->getOption('googleMapsKey'));
    }
    
    protected function _initFireBugLogger() {
    	$logger = new Zend_Log();
		$writer = new Zend_Log_Writer_Firebug();
		$logger->addWriter($writer);
		Zend_Registry::set('logger',$logger);
    }
    
}

