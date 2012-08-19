<?php

/**
 * MapController
 * 
 * @author Janjic Milos
 * @version 1.0
 */

class MapController extends App_Controller_Action {
	
	protected $_calendarAdapter = null;
	public $isLogged = false;
	
    public function init()
    {
        parent::init();
        $calAdapter = $this->_getCalendarAdapter();
        if ($calAdapter == null) $this->isLogged = false;
        else {
            $this->_calendarAdapter = $calAdapter;
            $this->isLogged = true;
        }
    }
    
	public function showMapAction(){
		if ($this->isLogged){
			$this->view->username = $this->_session->username;
			$this->view->events = $this->_calendarAdapter->getMapEvents();
		}else  $this->_helper->redirector("notauth","home");
	}
	
	public function getDirectionAction(){
		if ($this->isLogged){
			$this->view->username = $this->_session->username;
			$this->view->end = urldecode($this->_getParam('end'));
			
			$this->view->html = '<strong>' . urldecode($this->_getParam('title')) . '</strong><br />' . 
			                     $this->view->end . '<br />' . 
			                     urldecode($this->_getParam('when'));
        }else  $this->_helper->redirector("notauth","home");
	}
	
}

