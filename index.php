<?php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/GmailClient.php';

$Gmail = new GmailClient();

/* Authorization */
/*
try {
    $Gmail->authorize();
} catch (\Google\Exception $e) {
    echo $e->getMessage();
}
*/

/* Send message */
try {
    $request = $Gmail->sendMail('test@test.com', 'Test Subject', 'Test Message');
    var_dump($request);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
