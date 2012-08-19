<?php

/**
 * HomeController
 * 
 * @author Janjic Milos
 * @version 1.0
 */

class HomeController extends App_Controller_Action {
	
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
    
    public function notauthAction(){
    	$this->view->loginUrl = $this->_getAuthSubRequestUrl();
    }
	
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		if ($this->isLogged){
		    if (!isset($this->_session->username)){
	            $calFeed = $this->_calendarAdapter->getCalendarList();
	            $this->_session->username = substr($calFeed->title->text,0,-26);
		    }
	        
	        $this->view->username = $this->_session->username;
		}else  $this->_helper->redirector("notauth","home");
	}
	
	public function logoutAction() {
		$this->_session->unsetAll();
		$this->_helper->redirector("index", "index");
	}		
	
	public function allEventsAction(){
		if ($this->isLogged){
            $this->view->username = $this->_session->username;
        }else  $this->_helper->redirector("notauth","home");
	}
	
	
	public function rerenderEventsAction(){
		if ($this->isLogged){
			$events = $this->_calendarAdapter->outputCalendar(date('Y-m-d',$this->_getParam('start')), date('Y-m-d',$this->_getParam('end')));
	        $this->_json($events, true);
        }else  $this->_helper->redirector("notauth","home");
	}
	
	public function createQuickAddEventAction() {
		if ($this->isLogged){
			$this->_calendarAdapter->createQuickAddEvent(stripslashes(trim($this->_getParam("what"))));
			$this->_json(array("OK"));
		}else  $this->_helper->redirector("notauth","home");
	}
	
	
	public function deleteEventAction(){
		if ($this->isLogged){
			$this->_calendarAdapter->deleteEventById($this->_getParam("eventId"));
			$this->_json(array("OK"));
		}else  $this->_helper->redirector("notauth","home");
	}
	
	public function createEventAction(){
		if ($this->isLogged){
			$this->view->username = $this->_session->username;
		    
			$this->view->startDate = date('Y-m-d');
	        if ($this->_hasParam("startDate")) {
	        	$startDate = $this->_getParam('startDate');
	        	$startAr = explode(" ", $startDate);
	            
	            $this->view->startDate = $startDate;
	            
	            if (count($startAr) != 0){
	            	$this->view->startDate = $startAr[0];
	            	$this->view->startTime = $startAr[1];
	            }
	        }
		    $this->view->endDate = date('Y-m-d');
	        if ($this->_hasParam("endDate")) {
	            $endDate = $this->_getParam('endDate');
	            $endAr = explode(" ", $endDate);
	            
	            $this->view->endDate = $endDate;
	            
	            if (count($endAr) != 0){
	                $this->view->endDate = $endAr[0];
	                $this->view->endTime = $endAr[1];
	            }
	        }
			
			if ($this->_request->isPost()){
				$params = $this->_getAllParams();
	            //get remider array
	            //die(var_dump($params));
				$reminderAr = $this->_createReminderArray($params);
				
				$title = stripcslashes($params['title']);
				$desc = stripslashes($params['desc']);
				$where = stripslashes($params['where']);
				$startDate = stripslashes($params['startDate']);
				$endDate = stripslashes($params['endDate']);
				$allday = $params['allday'];
				
				if ($allday == '1') {
					$startTime = '00:00';
	                $endTime = '00:00';
				}else {
					$startTime = stripslashes($params['startTime']);
	                $endTime = stripslashes($params['endTime']);
				}
				
				$this->_calendarAdapter->createEvent($title, $desc, $where, 
				                     $startDate, $endDate, $startTime, $endTime ,$reminderAr, $allday);
	            $this->_helper->redirector("all-events");
			}
		}else  $this->_helper->redirector("notauth","home");
		
	}
	
	
	public function editEventDetailsAction(){
		if ($this->isLogged){
			$this->view->username = $this->_session->username;
			
			if ($this->_request->isPost()){
				$params = $this->_getAllParams();
	            //get remider array
	            $reminderAr = $this->_createReminderArray($params);
	            
	            $title = trim(stripcslashes($params['title']));
	            $desc = trim(stripslashes($params['desc']));
	            $where = trim(stripslashes($params['where']));
	            
	            $startDate = stripslashes($params['startDate']);
	            $endDate = stripslashes($params['endDate']);
	            
	            if ($this->_hasParam('startTime'))
	                $startTime = stripslashes($params['startTime']);
	            else $startTime = '00:00';
	            
	            if ($this->_hasParam('endTime'))
	                $endTime = stripslashes($params['endTime']);
	            else $endTime = '00:00';
	            
	            $eventId = $params['eventId'];
	            
	            if ($params['allday'] == '1') {
	            	$startTime = '00:00';
	            	$endTime = '00:00';
	            }
	            
	            $newEvent = $this->_calendarAdapter->updateEvent($eventId, $title, $desc, $where, 
	                                 $startDate, $endDate, $startTime, $endTime ,$reminderAr);
	            if ($newEvent != null)
	                $this->_helper->redirector("all-events");
	            else die();
	            
			}else {
				$this->view->eventId = $this->_getParam("eventId");
	            
	            $event = $this->_calendarAdapter->getEvent($this->view->eventId);
	            //die(var_dump($event));
	            $this->view->event = $event;
			}
		}else  $this->_helper->redirector("notauth","home");
	}
	
	protected function _createReminderArray($params){
	   $reminderAr = array();
            foreach ($params as $key => $param) {
                if (preg_match ( "/reminderType/", $key ) || preg_match ( "/reminderUnit/", $key ) || preg_match ( "/remCount/", $key )) {
                    $temp = explode("reminderType", $key);
                    if (count($temp) > 1){
                        $value = explode("reminderType", $key);
                        $reminderAr[$value[0]]['reminderType'] = $param;   
                    }
                    
                    $temp = explode("reminderUnit", $key);
                    if (count($temp) > 1){
                        $value = explode("reminderUnit", $key);
                        $reminderAr[$value[0]]['reminderUnit'] = $param;   
                    }
                        
                    $temp = explode("remCount", $key);
                    if (count($temp) > 1){
                        $value = explode("remCount", $key);
                        $reminderAr[$value[0]]['remCount'] = $param;   
                    }
                }
            }
            
            return $reminderAr;
	}
	
	public function getEventAction(){
		if ($this->isLogged){
			$event = $this->_calendarAdapter->getEvent($this->_getParam("eventId"));
			$this->_json($event);
		}else  $this->_helper->redirector("notauth","home");
	}
	
	
}

