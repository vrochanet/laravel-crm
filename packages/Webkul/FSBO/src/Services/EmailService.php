<?php

namespace Webkul\FSBO\Services;

class EmailService
{
    public function buildUrl(string $email, string $subject, string $message): string
    {
        return 'mailto:'.rawurlencode($email)
            .'?subject='.rawurlencode($subject)
            .'&body='.rawurlencode($message);
    }
}
