<?php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/GmailClass.php';

$Gmail = new GmailClass();

try {
    $request = $Gmail->sendMail('oktay@turkpin.com', 'Test Subject', 'Test Message');
    var_dump($request);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
