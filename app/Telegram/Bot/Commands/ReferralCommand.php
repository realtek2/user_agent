<?php

namespace App\Telegram\Bot\Commands;

use Telegram\Bot\Actions;

class ReferralCommand extends Command
{
    protected $name = 'referral';

    protected $description = 'Реферальная система';

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

        $userId = $this->getUpdate()->getMessage()->get('from')->id;

        return $this->replyWithMessage([
            "text" => "Добрый день, ваш реферальный код: `{$userId}`\n" .
                "Ваша реферральная ссылка для приглашения друзей:\n" .
                "t.me/uaidbot?start={$userId}\n" .
                "user-agent.cc/?ref={$userId}\n\n" .
                "Вы будете получать вознаграждение в 20% от всех транзакций ваших рефералов.\n" .
                "Регистрация через вашу реферральную ссылку дает скидку в 5% для вашего реферала на все платные функции.",
            "parse_mode" => "markdown"
        ]);
    }
}
