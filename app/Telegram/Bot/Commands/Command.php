<?php


namespace App\Telegram\Bot\Commands;

use App\TelegramUser;
use App\User;
use Cache;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Log;
use Psr\SimpleCache\InvalidArgumentException;
use Telegram;
use Telegram\Bot\Commands\Command as BaseCommand;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\Chat;


abstract class Command extends BaseCommand
{
    /**
     * Get or create TelegramUser model from chat
     *
     * @param string|null $command - Название последней команды
     * @return TelegramUser
     */
    protected function getTelegramUserFromChat($command = null)
    {
        $chat = $this->getChatFromUpdate();
        $chatId = $chat->getId();
        $telegramUser = TelegramUser::firstOrNew([
            'chat_id' => $chatId,
        ]);

        if (empty($command))
            $command = '';

        if (!$telegramUser->id) {
            try
            {
                $params = array(
                    'first_name' => $chat->getFirstName(),
                    'last_name' => $chat->getLastName(),
                    'user_name' => $chat->getUserName(),
                    'last_command' => $command
                );
                if ($chatId > 0)
                    $params['owner_id'] = $chatId;
                $telegramUser = $telegramUser->fill($params);

                $telegramUser->save();
            }
            catch (Exception $ex)
            {
                Log::error('Command getTelegramUserFromChat() exception.');
                Log::error('Error Message: ' . $ex->getMessage());
                Log::error('Stack Trace: ' . $ex->getTraceAsString());
            }
        }
        else if (!empty($command))
        {
            $telegramUser->fill([ 'last_command' => $command ]);
            $telegramUser->save();
        }

        return $telegramUser;
    }

    /**
     * Get or create user model from chat
     * @param int|null $partnerId
     * @return User|Model
     */
    protected function getUserFromChat($partnerId = null)
    {
        $params = array( "name" => $this->getChatFromUpdate()->getId() );
        if (is_int($partnerId))
            $params["partner_id"] = $partnerId;
        return User::firstOrCreate($params);
    }


    /**
     * Get chat object from update
     *
     * @return Collection|Chat
     */
    protected function getChatFromUpdate()
    {
        return $this->getUpdate()->getChat();
    }


    /**
     * Get full username
     *
     * @return string
     */
    protected function getFullUserNameFromChat()
    {
        $update = $this->getChatFromUpdate();

        return trim(
            $update->getFirstName() . " " .  $update->getLastName()
        );
    }


    /**
     * @return mixed
     */
    protected function sendVerifyPhoneMessage()
    {
        $requestPhoneButton = Keyboard::button([
            'text' => 'Отправить номер',
            'request_contact' => true,
        ]);

        return $this->replyWithMessage([
           "text" => "Пожалуйста, завершите регистрацию, отправив номер телефона, через кнопку `[Отправить номер]` в панели.\n " .
               "Номер телефона нужен для защиты вашего аккаунта.",
            "parse_mode" => "markdown",
            "reply_markup" => Keyboard::make([
                "keyboard" => [[$requestPhoneButton]],
                "one_time_keyboard" => true,
                "resize_keyboard" => true
            ])
        ]);

        # На iPhone не присылается replyMessage при нажатии на кнопку отправить телефон
        /*if ($response instanceof Telegram\Bot\Objects\Message) {
            Cache::set(
                "tg.phone_verify.{$this->getChatFromUpdate()->getId()}",
                $response->messageId,
                600 // 10 Minute
            );
        }*/
    }

    /**
     * @return array
     */
    protected function parseArguments()
    {
        $update = $this->getUpdate();
        $args = (explode(' ', $update->getMessage()->getText()));
        unset($args[0]);
        $args = array_values($args);

        return $this->arguments = !empty($args) ? $args : [];
    }
}
