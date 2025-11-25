<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

date_default_timezone_set('Asia/Jakarta');

class TelegramModel extends Model
{
    public static function sendMessage($tokenBot, $chatID, $message)
    {
        $text = urlencode($message);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendmessage?chat_id=$chatID&text=$text&parse_mode=HTML",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendMessageReply($tokenBot, $chatID, $message, $messageID)
    {
        $text = urlencode($message);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendmessage?chat_id=$chatID&text=$text&parse_mode=HTML&reply_to_message_id=$messageID",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendMessageThread($tokenBot, $chatID, $threadID, $message)
    {
        $text = urlencode($message);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendmessage?chat_id=$chatID&message_thread_id=$threadID&text=$text&parse_mode=HTML",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendMessageReplyThread($tokenBot, $chatID, $threadID, $message, $messageID)
    {
        $text = urlencode($message);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendmessage?chat_id=$chatID&message_thread_id=$threadID&text=$text&parse_mode=HTML&reply_to_message_id=$messageID",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendMessageReplyMarkupButton($tokenBot, $chatID, $message, $button)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendMessage",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'chat_id'      => $chatID,
                'text'         => $message,
                'parse_mode'   => 'HTML',
                'reply_markup' => json_encode($button)
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendMessageReplyMarkupKeyboard($tokenBot, $chatID, $message, $keyboard)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendMessage",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'chat_id'      => $chatID,
                'text'         => $message,
                'parse_mode'   => 'HTML',
                'reply_markup' => json_encode($keyboard),
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendPhoto($tokenBot, $chatID, $caption, $photo)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendPhoto",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'chat_id'    => $chatID,
                'parse_mode' => 'HTML',
                'caption'    => $caption,
                'photo'      => new \CURLFILE($photo),
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendPhotoThread($tokenBot, $chatID, $threadID, $caption, $photo)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendPhoto",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'chat_id'           => $chatID,
                'message_thread_id' => $threadID,
                'parse_mode'        => 'HTML',
                'caption'           => $caption,
                'photo'             => new \CURLFILE($photo),
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendMessageWithMarkup($tokenBot, $chatID, $message, array $button)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendMessage",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'chat_id'      => $chatID,
                'text'         => $message,
                'parse_mode'   => 'HTML',
                'reply_markup' => json_encode($button)
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function sendMessageThreadWithMarkup($tokenBot, $chatID, $threadID, $message, array $button)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/sendMessage",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'chat_id'           => $chatID,
                'message_thread_id' => $threadID,
                'text'              => $message,
                'parse_mode'        => 'HTML',
                'reply_markup'      => json_encode($button)
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function answerCallbackQuery($tokenBot, $callback_query_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => "https://api.telegram.org/bot$tokenBot/answerCallbackQuery",
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'callback_query_id' => $callback_query_id,
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}
