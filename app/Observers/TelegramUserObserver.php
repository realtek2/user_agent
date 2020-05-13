<?php

namespace App\Observers;

use Telegram;
use App\TelegramUser;

class TelegramUserObserver
{

    private const ADMIN_CHAT_ID = '2550885'; //Rushan chat bot id

    /**
     * Handle the telegram user "created" event.
     *
     * @param  \App\TelegramUser $telegramUser
     * @return void
     */
    public function created(TelegramUser $telegramUser)
    {
        $sm = [
            'chat_id' => self::ADMIN_CHAT_ID,
            'text' => "Регистрация нового пользователя: \n" . implode("\n", $telegramUser->toArray()),
        ];

        Telegram::sendMessage($sm);
    }

    /**
     * Handle the telegram user "updated" event.
     *
     * @param  \App\TelegramUser $telegramUser
     * @return void
     */
    public function updated(TelegramUser $telegramUser)
    {
        //
    }

    /**
     * Handle the telegram user "deleted" event.
     *
     * @param  \App\TelegramUser $telegramUser
     * @return void
     */
    public function deleted(TelegramUser $telegramUser)
    {
        //
    }

    /**
     * Handle the telegram user "restored" event.
     *
     * @param  \App\TelegramUser $telegramUser
     * @return void
     */
    public function restored(TelegramUser $telegramUser)
    {
        //
    }

    /**
     * Handle the telegram user "force deleted" event.
     *
     * @param  \App\TelegramUser $telegramUser
     * @return void
     */
    public function forceDeleted(TelegramUser $telegramUser)
    {
        //
    }
}
