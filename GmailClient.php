<?php

use JetBrains\PhpStorm\NoReturn;

class GmailClient
{
    private string $redirectUri;
    private string $tokenPath;
    private string $sender;
    private string $userId;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->redirectUri = 'http://localhost:8080/callback.php';
        $this->tokenPath =  __DIR__.'/token.json';
        $this->sender = '"Test" <test@test.com>';
        $this->userId = 'me';
    }

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     * @throws \Google\Exception
     * @throws Exception
     */
    protected function getClient(): Google_Client
    {

        $client = new Google_Client();
        $client->setApplicationName('Gmail API Mail Sender');
        $client->setScopes(scope_or_scopes: [Google_Service_Gmail::GMAIL_SEND]);
        $client->setAuthConfig(__DIR__ . '/credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->setRedirectUri($this->redirectUri);

        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            }
        }
        return $client;
    }

    /**
     * @throws \Google\Exception
     */
    #[NoReturn] public function authorize(): void
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * @throws \Google\Exception
     */
    protected function getService(): Google_Service_Gmail
    {
        return new Google_Service_Gmail($this->getClient());
    }

    /**
     * @throws \Google\Exception
     * @throws Exception
     */
    public function setAccessToken(string $authCode): void
    {
        $client = $this->getClient();
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        if (array_key_exists('error', $accessToken)) {
            throw new Exception(join(', ', $accessToken));
        } else {
            if (!file_exists(dirname($this->tokenPath))) {
                mkdir(dirname($this->tokenPath), 0700, true);
            }
            file_put_contents($this->tokenPath, json_encode($accessToken));
        }
    }

    /**
     * @param $sender string sender email address
     * @param $to string recipient email address
     * @param $subject string email subject
     * @param $messageText string email text
     * @return string
     */
    private function createMessageRaw(string $sender, string $to, string $subject, string $messageText): string
    {
        $boundary = uniqid(rand(), true);

        $message = "From: $sender\r\n";
        $message .= "To: <$to>\r\n";
        $message .= 'Subject: =?utf-8?B?' . base64_encode($subject) . "?=\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";
        $message .= "\r\n--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=utf-8\r\n";
        $message .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
        $message .= "$messageText\r\n";
        $message .= "--$boundary\r\n";

        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }

    /**
     * @param $sender string sender email address
     * @param $to string recipient email address
     * @param $subject string email subject
     * @param $messageText string email text
     * @return Google_Service_Gmail_Message
     */
    public function createMessage(string $sender, string $to, string $subject, string $messageText): Google_Service_Gmail_Message
    {
        $message = new Google_Service_Gmail_Message();
        $message->setRaw($this->createMessageRaw($sender, $to, $subject, $messageText));
        return $message;
    }

    /**
     * @param $to string recipient email address
     * @param $subject string email subject
     * @param $messageText string email text
     * @return string
     */
    public function sendMail(string $to, string $subject, string $messageText): string
    {
        $message = $this->createMessage($this->sender, $to, $subject, $messageText);
        try {
            $response = $this->getService()->users_messages->send($this->userId, $message);
            return $response->getId();
        } catch (Exception $e) {
            return 'An error occurred: ' . $e->getMessage();
        }
    }
}
