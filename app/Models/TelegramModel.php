<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;

class TelegramModel
{
    private static function callApi($tokenBot, $method, array $params)
    {
        $response = Http::timeout(15)
            ->asForm()
            ->post("https://api.telegram.org/bot{$tokenBot}/{$method}", $params);

        return $response->body();
    }

    public static function sendMessage($tokenBot, $chatID, $message)
    {
        return self::callApi($tokenBot, 'sendMessage', [
            'chat_id'    => $chatID,
            'text'       => $message,
            'parse_mode' => 'HTML',
        ]);
    }

    public static function sendMessageReply($tokenBot, $chatID, $message, $messageID)
    {
        return self::callApi($tokenBot, 'sendMessage', [
            'chat_id'             => $chatID,
            'text'                => $message,
            'parse_mode'          => 'HTML',
            'reply_to_message_id' => $messageID,
        ]);
    }

    public static function sendMessageThread($tokenBot, $chatID, $threadID, $message)
    {
        return self::callApi($tokenBot, 'sendMessage', [
            'chat_id'           => $chatID,
            'message_thread_id' => $threadID,
            'text'              => $message,
            'parse_mode'        => 'HTML',
        ]);
    }

    public static function sendMessageReplyThread($tokenBot, $chatID, $threadID, $message, $messageID)
    {
        return self::callApi($tokenBot, 'sendMessage', [
            'chat_id'             => $chatID,
            'message_thread_id'   => $threadID,
            'text'                => $message,
            'parse_mode'          => 'HTML',
            'reply_to_message_id' => $messageID,
        ]);
    }

    public static function sendMessageWithMarkup($tokenBot, $chatID, $message, array $button)
    {
        return self::callApi($tokenBot, 'sendMessage', [
            'chat_id'      => $chatID,
            'text'         => $message,
            'parse_mode'   => 'HTML',
            'reply_markup' => json_encode($button),
        ]);
    }

    public static function sendMessageThreadWithMarkup($tokenBot, $chatID, $threadID, $message, array $button)
    {
        return self::callApi($tokenBot, 'sendMessage', [
            'chat_id'           => $chatID,
            'message_thread_id' => $threadID,
            'text'              => $message,
            'parse_mode'        => 'HTML',
            'reply_markup'      => json_encode($button),
        ]);
    }

    public static function sendPhoto($tokenBot, $chatID, $caption, $photo)
    {
        $response = Http::timeout(30)
            ->attach('photo', file_get_contents($photo), basename($photo))
            ->post("https://api.telegram.org/bot{$tokenBot}/sendPhoto", [
                'chat_id'    => $chatID,
                'parse_mode' => 'HTML',
                'caption'    => $caption,
            ]);

        return $response->body();
    }

    public static function sendPhotoThread($tokenBot, $chatID, $threadID, $caption, $photo)
    {
        $response = Http::timeout(30)
            ->attach('photo', file_get_contents($photo), basename($photo))
            ->post("https://api.telegram.org/bot{$tokenBot}/sendPhoto", [
                'chat_id'           => $chatID,
                'message_thread_id' => $threadID,
                'parse_mode'        => 'HTML',
                'caption'           => $caption,
            ]);

        return $response->body();
    }

    public static function answerCallbackQuery($tokenBot, $callback_query_id)
    {
        return self::callApi($tokenBot, 'answerCallbackQuery', [
            'callback_query_id' => $callback_query_id,
        ]);
    }
}
