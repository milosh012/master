<?php


class App_CalendarAdapter {

    protected $calendarService;

    public function __construct($client){
        $this->calendarService = new Zend_Gdata_Calendar($client);
    }

    public function getCalendarList(){
        try{
            $listFeed = $this->calendarService->getCalendarListFeed();
            return $listFeed;
        } catch (Zend_Gdata_App_Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function getName() {
        $listFeed = $this->calendarService->getCalendarListFeed();
        $temp = explode('@', $listFeed->title->text);
        return $temp[0];
    }

    public function outputCalendar($startDate, $endDate){
        $query = $this->calendarService->newEventQuery();
        $query->setUser('default');
        $query->setVisibility('private');
        $query->setProjection('full');
        $query->setOrderby('starttime');
        $query->setSortorder('a');

        $query->setStartMin($startDate);
        $query->setStartMax($endDate);

        try{
            $eventFeed = $this->calendarService->getCalendarEventFeed($query);

            $jsonEvents = $this->_getEvents($eventFeed);
            return json_encode($jsonEvents);
        } catch (Zend_Gdata_App_Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function getMapEvents(){
        $query = $this->calendarService->newEventQuery();
        $query->setUser('default');
        $query->setVisibility('private');
        $query->setProjection('full');
        $query->setOrderby('starttime');
        $query->setSortorder('a');
        $query->setStartMin(date('Y-m-d'));
        $query->setStartMax(date("Y-m-d", strtotime("+6 month", time())));

        try{
            $eventFeed = $this->calendarService->getCalendarEventFeed($query);
            $jsonEvents = $this->_getMapEvents($eventFeed);
            return json_encode($jsonEvents);
        } catch (Zend_Gdata_App_Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    protected function _getEvents($eventFeed){
        $i = 0;
        $jsonEvents = array();
        foreach ($eventFeed as $event) {
            $pieces = explode("/", $event->id->text);
            $eventId = end($pieces);
            $jsonEvents[$i] = array(
               "id" => trim($eventId),
               "title" => trim($event->title->text),
            );

             // prepare when object if not exist
            if (empty($event->when)) {
                $whenAr = $this->_parseWhen($event->getRecurrence()->getText());
            } else {
                $whenAr = $event->when;
            }

            foreach ($whenAr as $when) {
                $startTime = $this->_getDate($when->startTime);
                $endTime = $this->_getDate($when->endTime);

                if ($startTime['time'] == "" && $endTime['time'] == "" ){
                    $jsonEvents[$i]["start"] = $startTime['date'];
                    $newdate = strtotime ( '-1 day' , strtotime ( $endTime['date'] ) ) ;
                    $newdate = date ( 'Y-m-d' , $newdate );
                    if ($newdate != $startTime['date']) {
                        $jsonEvents[$i]["end"] = $newdate;
                    }
                } else {
                    $jsonEvents[$i]["start"] = trim($startTime['date'] . " " . $startTime['time']);
                    $jsonEvents[$i]["end"] = trim($endTime['date']  . " " . $endTime['time']);
                }

                if ($startTime['time'] != "" || $endTime['time'] != "")
                    $jsonEvents[$i]["allDay"] = false;
                else $jsonEvents[$i]["allDay"] = true;
            }
            $i++;
        }

        return $jsonEvents;
    }

    protected function _getMapEvents($eventFeed){
        $i = 0;
        $jsonEvents = array();

        foreach ($eventFeed as $event) {
            $html = '<strong>' . trim($event->title->text) .'</strong><br /><br />';

            $temp = false;
            foreach ($event->where as $where) {
                if ($where->valueString != null) {
                    $html .= $where->valueString;
                    $jsonEvents[$i]['address'] = $where->valueString;
                    $temp = true;
                }
            }

            if ($temp) {
                //$jsonEvents[$i]['popup'] = true;
                $whenStr = "";

                // prepare when object if not exist
                if (empty($event->when)) {
                    $whenAr = $this->_parseWhen($event->getRecurrence()->getText());
                } else {
                    $whenAr = $event->when;
                }

                foreach ($whenAr as $when) {
                    $startTime = $this->_getDate($when->startTime, true);
                    $endTime = $this->_getDate($when->endTime, true);

                    if ($startTime['time'] == "" && $endTime['time'] == "" ){
                        $whenStr .= $startTime['date'];
                        $newdate = strtotime ( '-1 day' , strtotime ( $endTime['date'] ) ) ;
                        $newdate = date ( 'D, M j' , $newdate );
                        if ($newdate != $startTime['date'])
                            $whenStr .= ' ' . $newdate;
                    }else {
                        $whenStr .= ' ' . trim($startTime['date'] . " " . $startTime['time']) . ' - ';
                        $whenStr .= ' ' . trim($endTime['date']  . " " . $endTime['time']);
                    }
                }

                $link = urlencode($jsonEvents[$i]['address']);
                $title = urlencode(trim($event->title->text));
                $whenEnc = urlencode($whenStr);

                $html .= '<br />' . $whenStr . '<br /><a href="/map/get-direction?end=' . $link .'&title=' . $title . '&when=' . $whenEnc .'">Get Direction</a>';
                $jsonEvents[$i]['name'] = $html;
                $i++;
            }
        }

        return $jsonEvents;
    }

    public function createQuickAddEvent ($quickAddText) {
        $event = $this->calendarService->newEventEntry();
        $event->content = $this->calendarService->newContent($quickAddText);
        $event->quickAdd = $this->calendarService->newQuickAdd('true');
        $newEvent = $this->calendarService->insertEvent($event);
    }


    public function createEvent($title, $desc, $where, $startDate, $endDate,
                       $startTime = '00:00', $endTime = '00:00', $reminderAr = null, $allday = null)
    {
        $newEvent = $this->calendarService->newEventEntry();
        $newEvent->title = $this->calendarService->newTitle($title);

        if ($desc != null)
            $newEvent->content = $this->calendarService->newContent($desc);
        if ($where != null)
            $newEvent->where = array($this->calendarService->newWhere($where));

        $when = $this->calendarService->newWhen();

        if ($allday == null){
            $when->startTime = "{$startDate}T{$startTime}:00";
            $when->endTime = "{$endDate}T{$endTime}:00";
        }else {
              $newdate = strtotime ( '+1 day' , strtotime ( $endDate ) ) ;
              $endDate = date ( 'Y-m-d' , $newdate );
              $when->startTime = $startDate;
              $when->endTime = $endDate;
        }

        $newEvent->when = array($when);

        //add reminders
        if ($reminderAr != null && count($reminderAr) != 0){
            $reminders = array();
            foreach ($reminderAr as $key=>$value) {
                $reminder = $this->calendarService->newReminder();

                if ($value['reminderUnit'] == "min")
                    $reminder->setMinutes($value['remCount']);
                else if ($value['reminderUnit'] == "h")
                    $reminder->setHours($value['remCount']);
                else if ($value['reminderUnit'] == "days")
                    $reminder->setDays($value['remCount']);

                $reminder->setMethod($value['reminderType']);
                $reminders[] = $reminder;
            }

            $when->reminders = $reminders;
        }

        // Upload the event to the calendar server
        // A copy of the event as it is recorded on the server is returned

        $createdEvent = $this->calendarService->insertEvent($newEvent);
        return $createdEvent->id->text;
    }

    /**
     * Returns an entry object representing the event with the specified ID.
     *
     * @param  string           $eventId The event ID string
     * @param boolean           $returnAsObject return as object or array
     * @return array|null if the event is found, null if it's not
     */
    public function getEvent($eventId, $returnAsObject = false)
    {
        $query = $this->calendarService->newEventQuery();
        $query->setUser('default');
        $query->setVisibility('private');
        $query->setProjection('full');
        $query->setEvent($eventId);

        try {
            $eventEntry = $this->calendarService->getCalendarEventEntry($query);

            if ($returnAsObject) return  $eventEntry;

            return $this->_convertEventToArray($eventEntry);
        } catch (Zend_Gdata_App_Exception $e) {
            var_dump($e);
            return null;
        }
    }

    /**
     * Deletes the event specified by retrieving the atom entry object
     * and calling Zend_Feed_EntryAtom::delete() method.  This is for
     * example purposes only, as it is inefficient to retrieve the entire
     * atom entry only for the purposes of deleting it.
     *
     * @param  string           $eventId The event ID string
     * @return void
     */
    public function deleteEventById ($eventId)
    {
        $event = $this->getEvent($eventId, true);
        $event->delete();
    }


    /**
     * Adds a reminder to the event specified as a parameter.
     *
     * @param  string           $eventId The event ID string
     * @param  integer          $minutes Minutes before event to set reminder
     * @return Zend_Gdata_Calendar_EventEntry|null The updated entry
     */
    protected function _setReminder($eventId, $type, $value, $unit)
    {
        $method = "alert";
        if ($event = $this->getEvent($eventId, true)) {
            $times = $event->when;
            foreach ($times as $when) {
                $reminder = $this->calendarService->newReminder();
                $reminder->setMinutes($minutes);
                $reminder->setMethod($method);
                $when->reminder = array($reminder);
            }
            $eventNew = $event->save();
            return $eventNew;
        } else {
          return null;
        }
    }


    /**
     * Updates event details
     *
     * @param  string           $eventId  The event ID string
     * @param  string           $newTitle The new title to set on this event
     * @return Zend_Gdata_Calendar_EventEntry|null The updated entry
     */
    public function updateEvent ($eventId, $title, $desc, $where, $startDate, $endDate,
                       $startTime = '00:00', $endTime = '00:00', $reminderAr = null)
    {
        if ($eventOld = $this->getEvent($eventId, true)) {
            try {

                $eventOld->title = $this->calendarService->newTitle($title);
                if ($desc != null)
                    $eventOld->content = $this->calendarService->newContent($desc);
                if ($where != null)
                    $eventOld->where = array($this->calendarService->newWhere($where));

                $when = $this->calendarService->newWhen();
                $when->startTime = $startDate;
                if ($startDate != $endDate){
                    $newdate = strtotime ( '+1 day' , strtotime ( $endDate ) ) ;
                    $endDate = date ( 'Y-m-d' , $newdate );
                    $when->endTime = $endDate;
                }else if ($startDate == $endDate && $startTime != $endTime){
                    $when->startTime = "{$startDate}T{$startTime}:00";
                    $when->endTime = "{$endDate}T{$endTime}:00";
                }

                $eventOld->when = array($when);

                //add reminders
                if ($reminderAr != null && count($reminderAr) != 0){
                    $reminders = array();
                    foreach ($reminderAr as $key=>$value) {
                       $reminder = $this->calendarService->newReminder();

                        if ($value['reminderUnit'] == "min")
                            $reminder->setMinutes($value['remCount']);
                        else if ($value['reminderUnit'] == "h")
                            $reminder->setHours($value['remCount']);
                        else if ($value['reminderUnit'] == "days")
                            $reminder->setDays($value['remCount']);

                        $reminder->setMethod($value['reminderType']);
                        $reminders[] = $reminder;
                   }
                   $when->reminders = $reminders;
                }

                $eventOld->save();
            } catch (Zend_Gdata_App_Exception $e) {
              echo "Error: " . $e->getMessage();
              return null;
            }

            return $eventOld;
        }

        return null;
    }

    private function _parseWhen($text)
    {
        $text = explode("\n", $text);
        $date = new stdClass;
        $date->reminders = null;

        $startCompleted = false;
        $endCompleted = false;

        foreach ($text as $value) {
            if (!$startCompleted || !$endCompleted) {
                if (preg_match('#^DTSTART#', $value) || preg_match('#^DTEND#', $value)) {
                    $str = explode(':', $value);
                    $dateTime = new DateTime($str[1]);
                    $format = (strstr($str[1], 'T')) ? 'c' : 'Y-m-d';
                }

                if (preg_match('#^DTSTART#', $value)) {
                    $date->startTime = $dateTime->format($format);
                    $startCompleted = true;
                } else if (preg_match('#^DTEND#', $value)) {
                    $date->endTime = $dateTime->format($format);
                    $endCompleted = true;
                }
            }
        }

        $when = array();
        $when[0] = $date;

        return $when;
    }

    protected function _convertEventToArray($eventEntry) {
        $eventAr = array();
        $eventAr['title'] = $eventEntry->title->text;
        $eventAr['desc'] = $eventEntry->content->text;
        $i = 0;

        // prepare when object if not exist
        if (empty($eventEntry->when)) {
            $whenAr = $this->_parseWhen($eventEntry->getRecurrence()->getText());
        } else {
            $whenAr = $eventEntry->when;
        }

        foreach ($whenAr as $when) {
            $startTime = $this->_getDate($when->startTime);
            $endTime = $this->_getDate($when->endTime);

            if ($startTime['time'] == "" && $endTime['time'] == "" ) {
                $eventAr['when'][$i]["start"] = $startTime['date'];
                $eventAr['when'][$i]["end"] = $startTime['date'];
                $newdate = strtotime ( '-1 day' , strtotime ( $endTime['date'] ) ) ;
                $newdate = date ( 'Y-m-d' , $newdate );
                if ($newdate != $startTime['date'])  $eventAr['when'][$i]["end"] = $newdate;
            } else {
                $eventAr['when'][$i]["start"] = trim($startTime['date'] . " " . $startTime['time']);
                $eventAr['when'][$i]["end"] = trim($endTime['date']  . " " . $endTime['time']);
            }

            if ($startTime['time'] != "" || $endTime['time'] != ""){
                $eventAr['when'][$i]["allDay"] = false;
                $eventAr['when'][$i]["start"] = trim($startTime['date']);
                $eventAr['when'][$i]["end"] = trim($endTime['date']);
                $eventAr['when'][$i]["startTime"] = trim( $startTime['time']);
                $eventAr['when'][$i]["endTime"] = trim( $endTime['time']);
            }
            else {
                $eventAr['when'][$i]["allDay"] = true;
                $eventAr['when'][$i]["endTime"] = null;
                $eventAr['when'][$i]["startTime"] = null;
            }

            $r = 0;
            if ($when->reminders == null) $eventAr['when'][$i]["reminders"] = null;
            else
                foreach ($when->reminders as $reminder) {
                    if ($reminder->minutes % 60 == 0)
                          $eventAr['when'][$i]["reminders"][$r]['hours'] = $reminder->minutes % 60;
                    else if ($reminder->minutes % 3600 == 0)
                          $eventAr['when'][$i]["reminders"][$r]["days"] =  $reminder->minutes % 3600;

                    $eventAr['when'][$i]["reminders"][$r]["method"] = $reminder->method;
                    $eventAr['when'][$i]["reminders"][$r]['minutes'] = $reminder->minutes;
                    $r++;
                }
            $i++;
        }
        $i = 0;
        foreach ($eventEntry->where as $where) {
            $eventAr['where'][$i]['location'] = $where->valueString;
        }

        if (isset($eventAr['when']) === false) {
            $eventAr['when'][0]["allDay"] = true;
            $eventAr['when'][0]["start"] = null;
            $eventAr['when'][0]["end"] = null;
            $eventAr['when'][0]["endTime"] = null;
            $eventAr['when'][0]["startTime"] = null;
            $eventAr['when'][0]["reminders"] = null;
        }

        return $eventAr;
    }

    protected function _getDate($googleDate, $formated = false){
        //2009-05-04T13:00:00.000+01:00
        //{$startDate}T{$startTime}:00.000{$tzOffset}:00

        $googleDateAr = explode("T",$googleDate);

        if (count($googleDateAr) == 1) {
            return array(
                'date' => ($formated) ? date('D, M j', strtotime($googleDate)) : $googleDate,
                'time' => ''
            );
        } else {
            $googleTimeAr = explode(".",$googleDateAr[1]);

            if (count($googleTimeAr) == 1) {
                $googleTimeAr = explode("+",$googleDateAr[1]);
            }

            if ($formated){
                $date = strtotime($googleDateAr[0]);
                $date = date('D, M j', $date);
                return array(
                  "date" => $date,
                  "time" => substr($googleTimeAr[0],0,-3)
                );
            }
            return array(
                  "date" => $googleDateAr[0],
                  "time" => substr($googleTimeAr[0],0,-3)
            );
        }
    }
}
