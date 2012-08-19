<?php

class IndexController extends App_Controller_Action
{

    public function init()
    {
    	parent::init();
    }

    public function indexAction() {
        $this->view->loginUrl = $this->_getAuthSubRequestUrl();
    }

}

