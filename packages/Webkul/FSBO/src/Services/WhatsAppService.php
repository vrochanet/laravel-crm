<?php

namespace Webkul\FSBO\Services;

class WhatsAppService
{
    public function buildUrl(string $phone, string $message): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        // Converte números portugueses nacionais para o formato internacional.
        if (strlen($phone) === 9) {
            $phone = '351'.$phone;
        } elseif (str_starts_with($phone, '00351')) {
            $phone = substr($phone, 2);
        }

        return 'https://wa.me/'.$phone.'?text='.urlencode($message);
    }
}
