<?php
declare(strict_types=1);
// Isolated test adapter. Never packaged or deployed to the application.
return new class {
    public function verify(array $settings): void {}
    public function send(array $settings,array $message): void {
        if(!empty($GLOBALS['notificationTestFail']))throw new RuntimeException('Test ambiguous result');
        $GLOBALS['notificationTestDb']->prepare('INSERT INTO notification_test_receipts(recipient,title,body) VALUES(?,?,?)')->execute([$settings['chat_id']??$settings['recipient'],$message['title'],$message['body']]);
    }
};
