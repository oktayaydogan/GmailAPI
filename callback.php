<?php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/GmailClient.php';

if ($_GET['code']) {
    $gmail = new GmailClient();

    try {
        $gmail->setAccessToken($_GET['code']);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}