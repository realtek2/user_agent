<?php

namespace App\Telegram\Bot\Commands;

use App\User;
use Exception;
use Log;
use Telegram;
use Telegram\Bot\Actions;
use Telegram\Bot\Keyboard\Keyboard;

class StartCommand extends Command
{
    protected $name = 'start';

    protected $description = 'Запустить бота, авторизоваться';

    public function handle()
    {
        $this->replyWithChatAction([ 'action' => Actions::TYPING ]);

        # Создаём или получаем пользователя и обновляем его последнюю команду
        $telegramUser = $this->getTelegramUserFromChat($this->name);

        $chatId = $this->getChatFromUpdate()->getId();
        $user = User::whereName($chatId)->first();

        $title = $this->getChatFromUpdate()->getTitle();

        if (empty($user))
        {
            $args = $this->parseArguments();
            $partnerId = null;
            if (is_array($args) && !empty($args))
            {
                $partnerId = filter_var($args[0], FILTER_VALIDATE_INT, array("options" => array("min_range" => 0, "max_range" => PHP_INT_MAX)));
                if (is_int($partnerId) && ($partnerId != $chatId))
                {
                    $partnerUser = User::whereName($partnerId)->first();
                    $partnerId = (!empty($partnerUser) ? $partnerId : null);
                }
            }

            $full_name = (!empty($title) ? $title : $this->getFullUserNameFromChat());
            $msg = "Добро пожаловать, {$full_name}.";
            if (is_int($partnerId))
                $msg .= "\nВы зарегистрировались по реферальной ссылке знакомого. Все платные функции вы сможете оплачивать со скидкой в 5%.";
            $this->getUserFromChat($partnerId);
            $this->replyWithMessage([
                "text" => $msg
            ]);
            try
            {
                if (is_int($partnerId))
                {
                    $msg = "Урра! Новая регистрация по вашей реферальной ссылке:\nId: {$telegramUser->chat_id}\nName: {$full_name}";
                    Telegram::sendMessage([
                        "chat_id" => $partnerId,
                        "text" => $msg
                    ]);
                }
            }
            catch (Exception $ex)
            {
                Log::error('Telegram StartCommand exception. Failed to send message to partner with ID: ' . $partnerId . '.');
                Log::error('Error Message: ' . $ex->getMessage());
                Log::error('Stack Trace: ' . $ex->getTraceAsString());
            }
        }

        # If group/chat
        if ($chatId < 0)
        {
            if (empty($telegramUser->owner_id))
            {
                $ownerId = $this->getUpdate()->getMessage()->get('from')->get('id');
                $ownerUser = User::whereName($ownerId)->first();

                if (empty($ownerUser))
                {
                    return $this->replyWithMessage([
                        "text" => "В данный момент этот чат не закреплён за владельцем, а вы не зарегистрированы в боте.\n" .
                            "Закрепить чат может только человек зарегистрированный в боте @uaidbot."
                    ]);
                }

                if (empty($ownerUser->phone))
                {
                    return $this->replyWithMessage([
                        "text" => "Для того чтобы прикрепить бота к чату, нужно добавить номер телефона в личной переписке с ботом - @uaidbot с помощью команды /start\n" .
                            "После того как добавите телефон, запустите команду /start в этом чате еще раз."
                    ]);
                }
                else
                {
                    $button = Keyboard::button([
                        'text' => 'Закрепить',
                        'callback_data'  => json_encode(array(
                            'c' => 'jc', # Command => Join Chat
                            'oid' => $ownerId,
                        ))
                    ]);

                    $replyMarkup = Keyboard::make([
                        'inline_keyboard' => [[$button]],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true
                    ]);

                    return $this->replyWithMessage([
                        "text" => "Для продолжения использования бота в чате, необходимо закрепить его за владельцем.\n" .
                            "Внимание! Плата за платные функции бота будет сниматься с владельца!\n" .
                            "Нажмите кнопку `[Закрепить]`, если вы готовы закрепить себя как владельца.",
                        "parse_mode" => "markdown",
                        "reply_markup" => $replyMarkup
                    ]);
                }
            }
        }
        else
        {
            if (empty($user->phone))
            {
                return $this->sendVerifyPhoneMessage();
            }
            else if (empty($telegramUser->owner_id))
            {
                try
                {
                    $telegramUser->fill([
                        'owner_id' => $chatId,
                        'owner_has_phone' => true
                    ])->save();

                    return $this->replyWithMessage([
                        "text" => "Готово! Ваш бот готов к работе. Теперь вы можете добавить ваш первый сайт через команду /code или запустить бота в любом вашем чате."
                    ]);
                }
                catch (Exception $ex)
                {
                    Log::error('Telegram StartCommand exception. Failed to set owner_id to chat: ' . $chatId . '.');
                    Log::error('Error Message: ' . $ex->getMessage());
                    Log::error('Stack Trace: ' . $ex->getTraceAsString());

                    return $this->replyWithMessage([
                        "text" => "Не удалось закрепить данные по вашему телефону.\n" .
                            "Обратитесь в техническую поддержку.\n",
                    ]);
                }
            }
        }

        return $this->replyWithMessage([
            "text" => "Бот уже запущен, ваш ID {$user->id}.\nДля входа на сайт используйте команду /login\n\nДля получения справки по командам бота используйте команду /help",
            "parse_mode" => "markdown",
            "reply_markup" => Keyboard::remove()
        ]);
    }
}
