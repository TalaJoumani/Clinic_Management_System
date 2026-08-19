<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $refreshToken;

    public function __construct(string $clientId, string $clientSecret, string $refreshToken)
    {
        parent::__construct();
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->refreshToken = $refreshToken;
    }

    protected function doSend(SentMessage $message): void
    {
        $accessToken = $this->getAccessToken();

        $rawMessage = $message->toString();

        $encoded = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $response = Http::withToken($accessToken)
            ->post('https://www.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $encoded,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gmail API request failed: '.$response->body());
        }
    }

    protected function getAccessToken(): string
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to refresh Gmail access token: '.$response->body());
        }

        return $response->json('access_token');
    }

    public function __toString(): string
    {
        return 'gmail+api';
    }
}