<?php

namespace Workdo\ZoomMeeting\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ZoomService
{
    private $client;
    private $baseUrl = 'https://api.zoom.us/v2';

    public function __construct()
    {
        $this->client = new Client();
    }

    private function getAccessToken()
    {
        $apiKey = company_setting('zoom_api_key');
        $apiSecret = company_setting('zoom_api_secret');
        $accountId = company_setting('zoom_account_id');

        if (!$apiKey || !$apiSecret || !$accountId) {
            throw new \Exception('Zoom API credentials not configured');
        }

        try {
            $response = $this->client->post('https://zoom.us/oauth/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($apiKey . ':' . $apiSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'grant_type' => 'account_credentials',
                    'account_id' => $accountId
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'];
        } catch (RequestException $e) {
            throw new \Exception('Failed to get Zoom access token: ' . $e->getMessage());
        }
    }

    public function createMeeting($data)
    {
        try {
            $token = $this->getAccessToken();
            
            $meetingData = [
                'topic' => $data['title'],
                'type' => 2, // Scheduled meeting
                'start_time' => date('c', strtotime($data['start_time'])),
                'duration' => (int)$data['duration'],
                'timezone' => 'UTC',
                'password' => $data['meeting_password'] ?? '',
                'settings' => [
                    'host_video' => $data['host_video'] ?? false,
                    'participant_video' => $data['participant_video'] ?? false,
                    'waiting_room' => $data['waiting_room'] ?? false,
                    'auto_recording' => $data['recording'] ? 'local' : 'none',
                    'join_before_host' => false,
                    'mute_upon_entry' => true
                ]
            ];

            $response = $this->client->post($this->baseUrl . '/users/me/meetings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ],
                'json' => $meetingData
            ]);

            $result = json_decode($response->getBody(), true);
            
            // Ensure URLs are included in response
            if (!isset($result['start_url']) && isset($result['id'])) {
                $result['start_url'] = "https://zoom.us/s/{$result['id']}";
            }
            if (!isset($result['join_url']) && isset($result['id'])) {
                $result['join_url'] = "https://zoom.us/j/{$result['id']}";
                if (!empty($data['meeting_password'])) {
                    $result['join_url'] .= "?pwd={$data['meeting_password']}";
                }
            }
            
            return $result;
        } catch (RequestException $e) {
            throw new \Exception('Failed to create Zoom meeting: ' . $e->getMessage());
        }
    }

    public function updateMeeting($meetingId, $data)
    {
        try {
            $token = $this->getAccessToken();
            
            $meetingData = [
                'topic' => $data['title'],
                'start_time' => date('c', strtotime($data['start_time'])),
                'duration' => (int)$data['duration'],
                'password' => $data['meeting_password'] ?? '',
                'settings' => array_filter([
                    'host_video' => $data['host_video'] ?? false,
                    'participant_video' => $data['participant_video'] ?? false,
                    'waiting_room' => $data['waiting_room'] ?? false,
                    'auto_recording' => isset($data['recording']) ? ($data['recording'] ? 'local' : 'none') : null
                ], function($value) { return $value !== null; })
            ];

            $response = $this->client->patch($this->baseUrl . '/meetings/' . $meetingId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ],
                'json' => $meetingData
            ]);

            $result = json_decode($response->getBody(), true);
            
            // Get updated meeting details to return URLs
            try {
                $updatedMeeting = $this->getMeeting($meetingId);
                $result['start_url'] = $updatedMeeting['start_url'] ?? "https://zoom.us/s/{$meetingId}";
                $result['join_url'] = $updatedMeeting['join_url'] ?? "https://zoom.us/j/{$meetingId}";
                if (!empty($data['meeting_password'])) {
                    $result['join_url'] .= "?pwd={$data['meeting_password']}";
                }
            } catch (\Exception $e) {
                // Fallback URLs if API call fails
                $result['start_url'] = "https://zoom.us/s/{$meetingId}";
                $result['join_url'] = "https://zoom.us/j/{$meetingId}";
            }
            
            return $result;
        } catch (RequestException $e) {
            throw new \Exception('Failed to update Zoom meeting: ' . $e->getMessage());
        }
    }

    public function deleteMeeting($meetingId)
    {
        try {
            $token = $this->getAccessToken();

            $this->client->delete($this->baseUrl . '/meetings/' . $meetingId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            return true;
        } catch (RequestException $e) {
            throw new \Exception('Failed to delete Zoom meeting: ' . $e->getMessage());
        }
    }

    public function getMeeting($meetingId)
    {
        try {
            $token = $this->getAccessToken();

            $response = $this->client->get($this->baseUrl . '/meetings/' . $meetingId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to get Zoom meeting: ' . $e->getMessage());
        }
    }

    public function getStartUrl($meetingId)
    {
        try {
            $meeting = $this->getMeeting($meetingId);
            return $meeting['start_url'] ?? "https://zoom.us/s/{$meetingId}";
        } catch (\Exception $e) {
            return "https://zoom.us/s/{$meetingId}";
        }
    }
}