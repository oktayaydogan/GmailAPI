<?php
require __DIR__ . '/vendor/autoload.php';

namespace App\Services;

use App\GmailEmail;
use Google_Client;
use Google_Service_Gmail;
use Illuminate\Support\Facades\Config;
use PhpSpec\Exception\Exception;

class GmailAPI
{
    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    public function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('Gmail API Mail Sender');
        $client->setScopes([Google_Service_Gmail::GMAIL_READONLY, Google_Service_Gmail::GMAIL_MODIFY, Google_Service_Gmail::GMAIL_SEND, Google_Service_Gmail::GMAIL_COMPOSE]);
        $client->setAuthConfig(__DIR__ . '/credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = __DIR__ . '/token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    /**
     * @param $sender string sender email address
     * @param $to string recipient email address
     * @param $subject string email subject
     * @param $messageText string email text
     * @return Google_Service_Gmail_Message
     */
    public function createMessage($sender, $to, $subject, $messageText)
    {
        $message = new Google_Service_Gmail_Message();

        $boundary = uniqid(rand(), true);

        $rawMessageString = "From: {$sender}\r\n";
        $rawMessageString .= "To: <{$to}>\r\n";
        $rawMessageString .= 'Subject: =?utf-8?B?' . base64_encode($subject) . "?=\r\n";
        $rawMessageString .= "MIME-Version: 1.0\r\n";
        $rawMessageString .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";
        $rawMessageString .= "\r\n--{$boundary}\r\n";
        $rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n";
        $rawMessageString .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
        $rawMessageString .= "{$messageText}\r\n";
        $rawMessageString .= "--$boundary\r\n";

        $rawMessage = rtrim(strtr(base64_encode($rawMessageString), '+/', '-_'), '=');
        $message->setRaw($rawMessage);
        return $message;
    }

    /**
     * @param $service Google_Service_Gmail an authorized Gmail API service instance.
     * @param $userId string User's email address
     * @param $message Google_Service_Gmail_Message
     * @return null|Google_Service_Gmail_Message
     */

    public function sendMail($to, $subject, $messageText)
    {
        $userId = 'me';
        $from = '"Tester" <noreply@mail.com>';

        $client = $this->getClient();
        $service = new Google_Service_Gmail($client);
        $message = $this->createMessage($from, $to, $subject, $messageText);

        try {
            $response = $service->users_messages->send($userId, $message);
            return $response->getId();
        } catch (Exception $e) {
            return 'An error occurred: ' . $e->getMessage();
        }
        return null;
    }
}


// Usage

$Gmail = new GmailAPI();
$request = $Gmail->sendMail('test@mail.com', 'Test S', 'Test C');
var_dump($request);
