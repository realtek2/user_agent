<?php

namespace App\Telegram\Bot\Commands;

use Telegram\Bot\Actions;
use Telegram\Bot\Keyboard\Keyboard;

class PartnersCommand extends Command
{
    protected $name = 'partners';

    protected $description = 'Бонусы и партнёрство';

    public function handle()
    {
        $this->replyWithChatAction([ 'action' => Actions::TYPING ]);

        # Создаём или получаем пользователя и обновляем его последнюю команду
        $telegramUser = $this->getTelegramUserFromChat($this->name);
        if (empty($telegramUser->owner_id))
        {
            return $this->replyWithMessage([
                "text" => "Пожалуйста, подтвердите ваш телефон!\nЗапустив команду - /start"
            ]);
        }

        $buttons = array(
            [Keyboard::inlineButton([
                "text" => "Купоны для Яндекс.Директ",
                "url" => "https://t.me/mag_au"
            ])],
            /*[Keyboard::inlineButton([
                "text" => "Определение номеров на сайте",
                "callback_data" => "partners@detect.phone"
            ])],*/
            [Keyboard::inlineButton([
                "text" => "Стать партнером",
                "url" => "https://t.me/natfullin"
            ])]);

        $replyMarkup = Keyboard::make([
            'inline_keyboard' => $buttons
        ]);

        return $this->replyWithMessage([
            "text" => "Вы можете получить бонусы от наших партнёров или стать нашим партнёром.",
            "reply_markup" => $replyMarkup
        ]);
    }
}
