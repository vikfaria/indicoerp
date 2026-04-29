<?php

namespace Workdo\Calendar\Services;

use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Workdo\Calendar\Models\GoogleCalendarSetting;

class GoogleCalendarService
{
    protected $service;
    protected $calendarId;

    public function __construct()
    {
        $this->service = GoogleCalendarSetting::getGoogleCalendarService();
        $this->calendarId = company_setting('google_calendar_id');
    }

    public function isAvailable()
    {
        return $this->service !== null && !empty($this->calendarId);
    }

    public function createEvent($eventData)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $event = new Event([
                'summary' => $eventData['title'],
                'description' => $eventData['description'] ?? '',
                'start' => [
                    'dateTime' => $eventData['start_datetime'],
                    'timeZone' => config('app.timezone'),
                ],
                'end' => [
                    'dateTime' => $eventData['end_datetime'],
                    'timeZone' => config('app.timezone'),
                ],
            ]);

            $createdEvent = $this->service->events->insert($this->calendarId, $event);
            return $createdEvent->getId();
        } catch (\Exception $e) {
            \Log::error('Google Calendar create event error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateEvent($googleEventId, $eventData)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $event = $this->service->events->get($this->calendarId, $googleEventId);
            
            $event->setSummary($eventData['title']);
            $event->setDescription($eventData['description'] ?? '');
            
            $start = new EventDateTime();
            $start->setDateTime($eventData['start_datetime']);
            $start->setTimeZone(config('app.timezone'));
            $event->setStart($start);

            $end = new EventDateTime();
            $end->setDateTime($eventData['end_datetime']);
            $end->setTimeZone(config('app.timezone'));
            $event->setEnd($end);

            $this->service->events->update($this->calendarId, $googleEventId, $event);
            return true;
        } catch (\Exception $e) {
            \Log::error('Google Calendar update event error: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteEvent($googleEventId)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $this->service->events->delete($this->calendarId, $googleEventId);
            return true;
        } catch (\Exception $e) {
            \Log::error('Google Calendar delete event error: ' . $e->getMessage());
            return false;
        }
    }

    public function getEvents($timeMin = null, $timeMax = null)
    {
        if (!$this->isAvailable()) {
            return [];
        }

        try {
            $optParams = [
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => $timeMin ?? date('c'),
                'timeMax' => $timeMax,
            ];

            $results = $this->service->events->listEvents($this->calendarId, $optParams);
            return $results->getItems();
        } catch (\Exception $e) {
            \Log::error('Google Calendar get events error: ' . $e->getMessage());
            return [];
        }
    }
}