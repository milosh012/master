<?php

/**
 * Action
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class App_Controller_Action extends Zend_Controller_Action {

	/**
     * @var Zend_Config
     */
    protected $_settings;

    /**
     * @var Zend_Session_Namespace
     */
    protected $_session;
    
	public function init() {
		parent::init();
		
		$this->_session = new Zend_Session_Namespace();
		$this->_session->setExpirationSeconds(18000);
		
		$this->_settings = Zend_Registry::get('settings');
		
	}
	
	/**
	 * return link for authentication with google
	 */
    protected function _getAuthSubRequestUrl()
    {
        $next = "http://master.dev/home";
        $scope = "http://www.google.com/calendar/feeds/";
        $secure = false;
        $session = true;
        return Zend_Gdata_AuthSub::getAuthSubTokenUri($next, $scope, $secure, $session);
    }
    
    protected function _getAuthSubHttpClient()
    {
        if (!($this->_session->sessionToken) && !($this->_getParam('token')) ){
            return null;
        } else if (!$this->_session->sessionToken && $this->_getParam('token')) 
            $this->_session->sessionToken = Zend_Gdata_AuthSub::getAuthSubSessionToken($this->_getParam('token'));
        
    
        $httpClient = Zend_Gdata_AuthSub::getHttpClient($this->_session->sessionToken);
        return $httpClient;
    }
    
    protected function _getCalendarAdapter(){
    	$client = $this->_getAuthSubHttpClient();
        if ($client == null)
            return null;
        
        return new App_CalendarAdapter($client);
    }
    
    protected function _fb($message, $label=null)
    {
        if ($label!=null) {
            $message = array($label,$message);
        }
        Zend_Registry::get('logger')->debug($message);
    }
    
    protected function _json($data, $jsonEncoded = false,  $sendNow = true, $keepLayouts = false, $contentType = 'application/json; charset=UTF-8;' )
    {
    	if (!$jsonEncoded)
            $json = $this->_helper->json($data, false, $keepLayouts);
        else $json = $data;
        
        $response = $this->getResponse();
        $response->setBody($json);
        $response->clearAllHeaders();
        $response->setHeader('Content-Type', $contentType, true);
        $response->sendResponse();
        exit;
    }

}

