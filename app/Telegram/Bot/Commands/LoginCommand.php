<?php


namespace App\Telegram\Bot\Commands;


use App\Custom\Helpers;
use App\TelegramUser;
use App\User;
use Telegram\Bot\Actions;
use Telegram\Bot\Keyboard\Keyboard;

class LoginCommand extends Command
{

    protected $name = 'login';

    protected $description = 'Авторизация';

    /**
     * {@inheritdoc}
     */
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

        $user = $this->getUserFromChat();

        $this->sendAuthMessage($telegramUser, $user);
    }


    protected function sendAuthMessage(TelegramUser $telegramUser, User $user)
    {
        $lwtAuthUrl = route('lwt', [
            'token' => Helpers::genToken($user)
        ]);

        $button = Keyboard::button([
            'text' => 'Авторизоваться',
            'url'  => $lwtAuthUrl
        ]);

        $replyMarkup = Keyboard::make([
            'inline_keyboard' => [[$button]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $fullUserName = trim(
            $this->getChatFromUpdate()->getFirstName()
            . " "
            .  $this->getChatFromUpdate()->getLastName()
        );

        return $this->replyWithMessage([
            "text" => "Здравствуйте, {$fullUserName}!\n\nВаш код для авторизации \n`{$telegramUser->refreshCode()->code}`\n\nСправка по командам бота [/help]",
            "parse_mode" => "markdown",
            "reply_markup" => $replyMarkup
        ]);
    }
}
