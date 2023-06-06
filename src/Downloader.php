<?php

namespace Abaturin\NtkVideoDownloader;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Downloader {
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'base_uri' => 'https://video.2090000.ru/',
            'cookies' => true
        ]);
    }

    public function login(string $login, string $password): void
    {
        $response = $this->httpClient->request('GET', 'login.html');

        $response = $this->httpClient->request('POST', 'login', [
            'form_params' => [
                'User[Login]' => $login,
                'User[Password]' => $password,
                'login' => '',
            ]
        ]);
    }

    public function downloadVideo(string $cameraId, int $startTimestamp, int $stopTime, string $outputFilename): void
    {
        $url = $this->fetchDownloadUrl($cameraId, $startTimestamp, $stopTime);

        $this->httpClient->request(
            'GET', 
            $url, 
            [
                RequestOptions::SINK => $outputFilename
            ]
        );
    }

    public function downloadVideoByUrl(string $url, string $outputFilename): void
    {
        $this->httpClient->request(
            'GET', 
            $url, 
            [
                RequestOptions::SINK => $outputFilename
            ]
        );
    }

    public function fetchDownloadUrl(string $cameraId, int $startTimestamp, int $stopTime): string
    {
        $response = $this->httpClient->request('POST', "account/camera/$cameraId/download.html", [
            'form_params' => [
                'action' => 'validate',
                'startTime' => (int)$startTimestamp,
                'stopTime' => (int)$stopTime,
                'timeZoneOffset' => '25200', // some magic
                'container' => ''
            ]
        ]);
        
        $responseJson = (string)$response->getBody();
        
        $data = \json_decode($responseJson, associative: true);
        
        if (!isset($data['error']) || $data['error'] !== false) {
            throw new \Exception('Validation response seems to be incorrect: error field is not present or is not false');
        }
        
        if (!isset($data['url'])) {
            throw new \Exception('Validation response seems to be incorrect: url field is not present');
        }
        
        return $data['url'];
    }
}