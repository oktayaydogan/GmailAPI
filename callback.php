<?php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/GmailClass.php';

if ($_GET['code']) {
    $gmail = new GmailClass();

    try {
        $gmail->setAccessToken($_GET['code']);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}