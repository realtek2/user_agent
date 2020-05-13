<?php


namespace App\Http\Controllers;


use App\Services\Helpers;
use App\Site;
use App\TelegramUser;
use App\User;
use Cache;
use Carbon\Carbon;
use Config;
use Exception;
use Log;
use Str;
use Telegram;
use Telegram\Bot\Keyboard\Keyboard;
use TrueBV\Punycode;

class TelegramBotController extends Controller
{
    /**
     * Cache time for callback answer in seconds.
     */
    private const CALLBACK_CACHE_TIME = 0;

    /**
     * Действия команды - /settings
     */
    private const CALLBACK_SETTINGS_ACTIONS = array(
        'visits' => array(
            'key' => 'v',
            'title' => 'Посещения'
        ),
        'start_of_input' => array(
            'key' => 'soi',
            'title' => 'Начало ввода'
        ),
        'form_submission' => array(
            'key' => 'fs',
            'title' => 'Отправка форм'
        ),
        'clicks_on_phone' => array(
            'key' => 'cop',
            'title' => 'Клики по телефону'
        ),
        'clicks_on_whatsapp' => array(
            'key' => 'cow',
            'title' => 'Клики по WhatsApp'
        ),
        'whatsapp_id' => array(
            'key' => 'wi',
            'title' => 'WhatsApp #Id'
        ),
        'whatsapp_btn' => array(
            'key' => 'wb',
            'title' => 'WhatsApp Кнопка'
        ),
        'notifications' => array(
            'key' => 'non',
            'title' => 'Уведомления'
        ),
        'delete' => array(
            'key' => 'del',
            'title' => 'Удалить сайт'
        ),
        'back_to_sites' => array(
            'key' => 'bts',
            'title' => 'Назад к списку сайтов'
        )
    );

    /**
     * Действия настроек виджета WhatsApp
     */
    private const CALLBACK_WHATSAPP_BUTTON_ACTIONS = array(
        'wb_widget_settings' => array(
            'key' => 'wb_s',
            'title' => 'Настроить'
        ),
        'wb_widget_state' => array( #
            'key' => 'wb_ws'
        ),
        'wb_widget_desktop_state' => array(
            'key' => 'wb_ds',
            'title' => 'Отображать на компьютере'
        ),
        'wb_widget_mobile_state' => array(
            'key' => 'wb_ms',
            'title' => 'Отображать на мобильных'
        ),
        'wb_widget_show_side' => array(
            'key' => 'wb_ss',
        ),
        'back_to_actions' => array(
            'key' => 'bta',
            'title' => 'Назад к настройкам сайта'
        ),
    );

    public function webHook()
    {
        try
        {
            /**
             * @var $update Telegram\Bot\Objects\Update
             */
            $update = Telegram::commandsHandler(true);

            if ($update->has('callback_query'))
            {
                /**
                 * @var $callbackQuery Telegram\Bot\Objects\CallbackQuery
                 */
                $callbackQuery = $update->getCallbackQuery();
                $callbackQueryId = $callbackQuery->getId();
                $callbackData = $callbackQuery->getData();
                $chatId = $callbackQuery->getMessage()->getChat()->id;
                $messageId = $callbackQuery->getMessage()->message_id;

                $callbackJson = json_decode($callbackData, true);

                if (json_last_error() != JSON_ERROR_NONE)
                {
                    if (strpos($callbackData, 'sites@') !== false)
                    {
                        Telegram::answerCallbackQuery([
                            "callback_query_id" => $callbackQueryId,
                            "cache_time" => 1
                        ]);
                        return;
                    }
                    else if (strpos($callbackData, 'partners@') !== false)
                    {
                        Telegram::answerCallbackQuery([
                            "callback_query_id" => $callbackQueryId,
                            "text" => "В процессе разработки",
                            "cache_time" => 1
                        ]);
                        return;
                    }
                    Log::error('TelegramBotController webHook() error.');
                    Log::error('CallbackQuery Error. Chat Id: ' . $chatId . ' Message Id: ' . $messageId);
                    Log::error('JSON decode error. Error msg: ' . json_last_error_msg() . ' Callback Data: ' . $callbackData);

                    Telegram::answerCallbackQuery([
                        "callback_query_id" => $callbackQueryId,
                        "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                        "cache_time" => self::CALLBACK_CACHE_TIME
                    ]);
                    return;
                }

                if (!isset($callbackJson['c']))
                {
                    Log::error('TelegramBotController webHook() error.');
                    Log::error('CallbackQuery Error. Chat Id: ' . $chatId . ' Message Id: ' . $messageId);
                    Log::error('Error msg: empty callback command. Callback Data: ' . $callbackData);
                    Telegram::answerCallbackQuery([
                        "callback_query_id" => $callbackQueryId,
                        "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                        "cache_time" => self::CALLBACK_CACHE_TIME
                    ]);
                    return;
                }

                switch ($callbackJson['c'])
                {
                    case 'st': # Command => Settings
                        $this->onCallbackSettings($callbackQueryId, $callbackData, $callbackJson, $chatId, $messageId);
                        break;
                    case 'st_a': # Command => Settings -> Action
                        $this->onCallbackSettingsAction($callbackQueryId, $callbackData, $callbackJson, $chatId, $messageId);
                        break;
                    case 'wa_wb_complete': # Command => Complete WhatsApp Widget
                        $this->onCallbackWhatsAppButtonWidgetComplete($callbackQueryId, $callbackData, $callbackJson, $chatId, $messageId);
                        break;
                    case 'jc':
                        $this->onCallbackJoinChatToOwner($callbackQueryId, $callbackData, $callbackJson, $chatId, $messageId);
                        break;
                }
            }
            else if ($update->has('message'))
            {
                $text = '';
                $chatId = 0;
                $userId = 0;
                try
                {
                    $getMessage = $update->getMessage();
                    $text = trim($update->getMessage()->text);
                    $userContact = $getMessage->get('contact');
                    if ((!empty($userContact)) ||
                        ((strlen($text) > 0) && ($text[0] !== '/')))
                    {
                        $chatId = $update->getMessage()->getChat()->id;
                        $userId = $update->getMessage()->get('from')->id;

                        # Ищем пользователя по Id чата и временем последнего обновления менее 10 минут
                        $telegramUser = TelegramUser::where([
                            'chat_id' => $chatId
                        ])->first(); /* ->where('updated_at', '>', Carbon::now()->subMinutes(10)) */

                        if (!empty($userContact) && !empty($telegramUser) && $telegramUser->last_command == 'start')
                        {
                            $user = User::whereName($userId)->first();
                            if (empty($user))
                            {
                                Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => "Не удалось вас идентифицировать, пожалуйста, запустите команду - /start еще раз.",
                                    "reply_markup" => Keyboard::remove()
                                ]);
                            }
                            else
                            {
                                $user->phone = $userContact->phoneNumber;
                                if ($user->save())
                                {
                                    Telegram::sendMessage([
                                        "chat_id" => $chatId,
                                        "text" => "Готово! Ваш бот готов к работе. Теперь вы можете добавить ваш первый сайт через команду /code или запустить бота в любом вашем чате.",
                                        "reply_markup" => Keyboard::remove()
                                    ]);

                                    # Delete cache value
                                    Cache::forget($cacheKey);

                                    $paramTelegramUser = array('owner_has_phone' => true);
                                    if ($chatId > 0)
                                        $paramTelegramUser['owner_id'] = $chatId;
                                    $telegramUser->fill($paramTelegramUser);
                                    $telegramUser->save();
                                }
                                else
                                {
                                    $requestPhoneButton = Keyboard::button([
                                        'text' => 'Отправить номер',
                                        'request_contact' => true,
                                    ]);

                                    Telegram::sendMessage([
                                        "chat_id" => $chatId,
                                        "text" => "Не удалось сохранить ваш телефон в нашу базу.\nПожалуйста, повторите попытку отправив телефон еще раз.",
                                        "reply_markup" => Keyboard::make([
                                            "keyboard" => [[$requestPhoneButton]],
                                            "one_time_keyboard" => true,
                                            "resize_keyboard" => true
                                        ])
                                    ]);
                                }
                            }
                        }
                        # Если текст из команды code
                        else if (!empty($telegramUser) && $telegramUser->last_command == 'code' &&
                            (strpos($text, '.') !== false) && (strpos($text, ' ') === false))
                        {
                            $inputs = array('site' => $text);
                            $parsed_url = parse_url($text);
                            if (!isset($parsed_url['scheme'])) {
                                $parsed_url['scheme'] = 'http';
                                $parsed_url = parse_url(Helpers::unparse_url($parsed_url));
                            }
                            $host = (isset($parsed_url['host']) ? $parsed_url['host'] : '');
                            if (isset($parsed_url['host'])) {
                                $punycode = new Punycode();
                                $parsed_url['host'] = $punycode->encode($parsed_url['host']);
                                $inputs['site'] = Helpers::unparse_url($parsed_url);
                            }
                            $validator = \Validator::make( $inputs, [
                                'site' => 'required|string|site'
                            ]);
                            if ($validator->fails())
                            {
                                Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => "К сожалению, не удалось определить ваш текст как ссылку.\nПопробуйте вставить только домен."
                                ]);
                            }
                            else if (!empty($host))
                            {
                                $app_url = Config::get('app.url');
                                if (empty($app_url))
                                    $app_url = 'https://user-agent.cc';

                                $user = User::where([
                                    'name' => $chatId
                                ])->first();

                                if (empty($user))
                                {
                                    $msg = ($chatId < 0 ?
                                            "Этот чат еще не зарегистрирован." :
                                            "Вы еще не зарегистрированы.") . "\nПожалуйста, зарегистрируйтесь через команду - /start";
                                    Telegram::sendMessage([
                                        "chat_id" => $chatId,
                                        "text" => $msg
                                    ]);
                                    $telegramUser->last_command = '';
                                    $telegramUser->save();
                                    return;
                                }

                                $site = Site::where([
                                    'user_id' => $user->id,
                                    'url' => $host
                                ])->first();

                                if (!empty($site))
                                {
                                    $message = "Вот код для размещения на вашем сайте - " . $host . "\n\n";
                                    $message .= "`<script src=\"" . $app_url . "/cdn/fpinit.js\"></script><script>FpInit('" . $site->id . "_" . $site->code . "')</script>`";
                                    $message .= "\n\nДобавьте его перед закрывающим тегом *</body>*\n";
                                    $message .= "Для настройки типов оповещений запустите команду - /settings";

                                    Telegram::sendMessage([
                                        "chat_id" => $chatId,
                                        "text" => $message,
                                        "parse_mode" => "markdown"
                                    ]);

                                    $site->update([ 'deleted' => false ]);
                                }
                                else
                                {
                                    $rand_code = Str::random(12);
                                    $site = new Site();
                                    $site->user_id = $user->id;
                                    $site->url = $parsed_url['host'];
                                    $site->code = $rand_code;
                                    $site->save();

                                    $message = "Вот код для размещения на вашем сайте - " . $host . "\n\n";
                                    $message .= "`<script src=\"" . $app_url . "/cdn/fpinit.js\"></script><script>FpInit('" . $site->id . "_" . $rand_code . "')</script>`";
                                    $message .= "\n\nДобавьте его перед закрывающим тегом *</body>*\n";
                                    $message .= "Для настройки типов оповещений запустите команду - /settings";


                                    Telegram::sendMessage([
                                        "chat_id" => $chatId,
                                        "text" => $message,
                                        "parse_mode" => "markdown"
                                    ]);
                                }

                                $telegramUser->last_command = '';
                                $telegramUser->save();
                            }
                        }
                        else if (!empty($telegramUser) && (strlen($telegramUser->last_command) > 15) &&
                            (substr($telegramUser->last_command, 0, 15) == 'wb_ww_get_phone'))
                        {
                            $siteId = intval(substr($telegramUser->last_command, 16, strlen($telegramUser->last_command)));
                            if (!empty($userContact))
                                $phone = (string)$userContact->phoneNumber;
                            else
                                $phone = $text;
                            $phone = preg_replace("/[^0-9]/", "", $phone);
                            $phone = filter_var($phone, FILTER_VALIDATE_INT, array("options" => array("min_range" => 0, "max_range" => PHP_INT_MAX)));
                            if (!is_int($phone) || ($phone == 0))
                            {
                                Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => "Не удалось идентифицировать ваш телефон для WhatsApp виджета.\n" .
                                        "Чтобы повторить попытку зайдите в настройки сайта и повторите попытку.",
                                ]);

                                $telegramUser->fill([
                                    'last_command' => ''
                                ]);

                                $telegramUser->save();
                                return;
                            }

                            $site = Site::whereId($siteId)->first();

                            if (empty($site))
                            {
                                Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => "Не удалось найти ваш сайт.\n" .
                                        "Обратитесь в тех. поддержку.",
                                ]);

                                $telegramUser->fill([
                                    'last_command' => ''
                                ]);

                                $telegramUser->save();
                                return;
                            }

                            try
                            {
                                $site->fill([
                                    'wb_widget_phone' => $phone,
                                    'wb_widget_text' => ''
                                ]);
                                $site->save();

                                $telegramUser->fill([
                                    'last_command' => 'wb_ww_get_text_' . $siteId
                                ]);
                                $telegramUser->save();

                                $buttons = array(
                                    [Keyboard::inlineButton([
                                        "text" => "Завершить настройку",
                                        "callback_data" => json_encode(array(
                                            'c' => 'wa_wb_complete',
                                            'sid' => $site->id,
                                            'uid' => $site->user_id,
                                        ))
                                    ])]);

                                $replyMarkup = Keyboard::make([
                                    'inline_keyboard' => $buttons
                                ]);

                                Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => "Готово, теперь пришлите текст для автозаполнения сообщения.\n" .
                                        "Например, «Добрый день».\n" .
                                        "Или нажмите на кнопку ниже, для завершения настройки виджета.\n" .
                                        "Текст прикреплен не будет. Внимание, проверьте работу виджета после настройки!",
                                    "reply_markup" => $replyMarkup
                                ]);
                            }
                            catch (Exception $ex)
                            {
                                Log::error('TelegramBotController webHook() exception. WhatsApp Button Widget get phone error.');
                                Log::error('Error Message: ' . $ex->getMessage());
                                Log::error('Stack Trace: ' . $ex->getTraceAsString());

                                Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => "Произошла непредвиденная ошибка.\n" .
                                        "Обратитесь в тех. поддержку.",
                                ]);
                            }
                        }
                        else if (!empty($telegramUser) && (strlen($telegramUser->last_command) > 14) &&
                            (substr($telegramUser->last_command, 0, 14) == 'wb_ww_get_text'))
                        {
                            $siteId = intval(substr($telegramUser->last_command, 15, strlen($telegramUser->last_command)));
                            $textLen = mb_strlen($text);
                            if ($textLen > 255)
                            {
                                return Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => "Текст не должен превышать 255 символов.\n" .
                                        "Сейчас ваш текст содержит {$textLen} символов.\n" .
                                        "Отправьте текст повторно, но короче."
                                ]);
                            }

                            $site = Site::whereId($siteId)->first();

                            if (empty($site))
                            {
                                Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => "Не удалось найти ваш сайт.\n" .
                                        "Обратитесь в тех. поддержку.",
                                ]);

                                $telegramUser->fill([
                                    'last_command' => ''
                                ]);

                                $telegramUser->save();
                                return;
                            }

                            try
                            {
                                $site->fill([
                                    'wb_widget_text' => $text
                                ]);
                                $site->save();

                                $telegramUser->fill([
                                    'last_command' => ''
                                ]);
                                $telegramUser->save();

                                Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => $this->getMessageForCompleteWhatsAppButtonWidget($site),
                                    "reply_markup" => Keyboard::remove()
                                ]);
                            }
                            catch (Exception $ex)
                            {
                                Log::error('TelegramBotController webHook() exception. WhatsApp Button Widget get phone error.');
                                Log::error('Error Message: ' . $ex->getMessage());
                                Log::error('Stack Trace: ' . $ex->getTraceAsString());

                                Telegram::sendMessage([
                                    "chat_id" => $chatId,
                                    "text" => "Произошла непредвиденная ошибка.\n" .
                                        "Обратитесь в тех. поддержку.",
                                ]);
                            }

                        }
                    }
                }
                catch (Exception $ex)
                {
                    Log::error('Error Message: ' . $ex->getMessage());
                    Log::error('Send Data: ', ['user_id' => $userId, 'chat_id' => $chatId, 'text_from_chat' => $text]);
                    Log::error('Stack Trace: ' . $ex->getTraceAsString());
                }
            }
        }
        catch (Exception $ex)
        {
            Log::error('TelegramBotController webHook() exception.');
            Log::error('Error Message: ' . $ex->getMessage());
            Log::error('Stack Trace: ' . $ex->getTraceAsString());
        }
//        if($update->has('message')){
//
//            $message = $update->getMessage();
//
//            if($message->replyToMessage && $message->contact){
//
//                $cacheKey = "tg.phone_verify.{$this->getChatFromUpdate()->getId()}";
//                if(Cache::get($cacheKey) == $message->replyToMessage->messageId){
//
//                    $user = User::whereName($message->replyToMessage->contact->userId)->first();
//                    $user->phone = $message->replyToMessage->contact->phoneNumber;
//                    if($user->save()){
//                        Telegram::sendMessage([
//                            'chat_id' => $message->replyToMessage->contact->userId,
//                            'text' => 'Телефонный номер успешно подтвержден.'
//                        ]);
//                    }
//
//
//                }
//            }
//        }
    }

    private function onCallbackJoinChatToOwner($callbackQueryId, $callbackData, $callbackJson, $chatId, $messageId)
    {
        if (!isset($callbackJson['oid']) || empty($chatId) || empty($messageId))
        {
            Log::error('TelegramBotController onCallbackJoinChatToOwner() error.');
            Log::error('CallbackQuery Error. Chat Id: ' . $chatId);
            Log::error('Error msg: empty website id. Callback Data: ' . $callbackData);
            Telegram::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                "cache_time" => self::CALLBACK_CACHE_TIME
            ]);
            return;
        }
        $ownerId = $callbackJson['oid'];

        $telegramUser = TelegramUser::whereChatId($chatId)->first();

        $save_state = false;
        if (!empty($telegramUser))
        {
            $msg = "Готово!";
            try
            {
                $telegramUser->owner_id = $ownerId;
                $telegramUser->owner_has_phone = true;
                $save_state = $telegramUser->save();
            }
            catch (Exception $ex)
            {
                Log::error('TelegramBotController onCallbackJoinChatToOwner() error.');
                Log::error('Save owner data in chat error. OwnerID: ' . $ownerId . ' ChatId: ' . $chatId);

                $msg = "Произошла ошибка. Не удалось закрепить вас как владельца.";
            }
        }
        else
        {
            $msg = "Не удалось найти этот чат в нашей базе данных.";
        }

        Telegram::answerCallbackQuery([
            "callback_query_id" => $callbackQueryId,
            "text" => $msg,
            "cache_time" => self::CALLBACK_CACHE_TIME
        ]);

        if ($save_state)
        {
            Telegram::sendMessage([
                "chat_id" => $chatId,
                "text" => "Ваш бот готов к работе. Теперь вы можете добавить ваш сайт через команду /code@uaidbot и пользоваться другими командами.\n" .
                    "Список команд можно узнать командой - /help@uaidbot"
            ]);
        }
    }

    private function onCallbackSettings($callbackQueryId, $callbackData, $callbackJson, $chatId, $messageId)
    {
        if (!isset($callbackJson['sid']) || !isset($callbackJson['uid']) || empty($chatId) || empty($messageId))
        {
            Log::error('TelegramBotController onCallbackSettings() error.');
            Log::error('CallbackQuery Error. Chat Id: ' . $chatId);
            Log::error('Error msg: empty website id. Callback Data: ' . $callbackData);
            Telegram::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                "cache_time" => self::CALLBACK_CACHE_TIME
            ]);
            return;
        }

        $siteId = $callbackJson['sid'];
        $userId = $callbackJson['uid'];

        $this->sendSiteInfoInlineButtons($callbackQueryId, $callbackData, $callbackJson, $siteId, $userId, $chatId, $messageId);
    }

    private function onCallbackSettingsAction($callbackQueryId, $callbackData, $callbackJson, $chatId, $messageId)
    {
        if (!isset($callbackJson['sid']) || !isset($callbackJson['uid']) || empty($chatId) || empty($messageId))
        {
            Log::error('TelegramBotController onCallbackSettingsAction() error.');
            Log::error('CallbackQuery Error. Chat Id: ' . $chatId . ' Message Id: ' . $messageId);
            Log::error('Error msg: empty website id. Callback Data: ' . $callbackData);
            Telegram::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                "cache_time" => self::CALLBACK_CACHE_TIME
            ]);
            return;
        }

        $siteId = $callbackJson['sid'];
        $userId = $callbackJson['uid'];
        $action = $callbackJson['a'];
        $originalActionKey = $this->getActionKey($callbackJson['a']);

        switch($action)
        {
            case 'v': # Visits
            case 'soi': # Start of input
            case 'fs': # Fors submission
            case 'cop': # Clicks on phone links
            case 'cow': # Clicks on WhatsApp links
            case 'wi': # WhatsApp #Id
            case 'non': # Enabled all notifications
            case 'noff': # Disabled all notifications
                $this->sendSiteInfoInlineButtons($callbackQueryId, $callbackData, $callbackJson, $siteId, $userId, $chatId, $messageId, $originalActionKey, 'Настройки успешно обновлены');
                break;
            case 'del': # Delete site
                $this->onCallbackSettingsActionDelete($callbackQueryId, $callbackData, $siteId, $userId, $chatId, $messageId);
                break;
            case 'del_yes': # Delete confirmation site
                $this->onCallbackSettingsActionDeleteConfirm($callbackQueryId, $callbackData, $siteId, $userId, $chatId, $messageId);
                break;
            case 'bta': # Back to Setting Actions from WhatsApp Button Actions
            case 'del_no': # Cancel delete site
                $this->sendSiteInfoInlineButtons($callbackQueryId, $callbackData, $callbackJson, $siteId, $userId, $chatId, $messageId);
                break;
            case 'bts': # Back to sites list from Setting Actions
                $this->onCallbackSettingsActionBackToSites($callbackQueryId, $userId, $chatId, $messageId);
                break;
            case 'wb': # WhatsApp Button Actions
                $this->sendWhatsAppButtonSettingInlineButtons($callbackQueryId, $callbackData, $callbackJson, $siteId, $userId, $chatId, $messageId);
                break;
            case 'wb_s':
            case 'wb_ws':
            case 'wb_ds':
            case 'wb_ms':
            case 'wb_ss':
                $this->sendWhatsAppButtonSettingInlineButtons($callbackQueryId, $callbackData, $callbackJson, $siteId, $userId, $chatId, $messageId, $originalActionKey, 'Настройки успешно обновлены');
                break;
        }
    }

    private function onCallbackSettingsActionDelete($callbackQueryId, $callbackData, $siteId, $userId, $chatId, $messageId)
    {
        if (empty($siteId))
        {
            Log::error('TelegramBotController onCallbackSettingsActionDelete() error.');
            Log::error('CallbackQuery Error. Chat Id: ' . $chatId . ' Message Id: ' . $messageId);
            Log::error('Error msg: empty website id. Callback Data: ' . $callbackData);
            Telegram::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                "cache_time" => self::CALLBACK_CACHE_TIME
            ]);
            return;
        }

        $buttons = array(
            array(
                Keyboard::button([
                    "text" => "✅ Да",
                    "callback_data" => json_encode(array(
                        "c" => "st_a", # Command
                        "a" => 'del_yes', # Action
                        "sid" => $siteId,
                        "uid" => $userId,
                    ))])
            ),
            array(
                Keyboard::button([
                    "text" => "❌ Отмена",
                    "callback_data" => json_encode(array(
                        "c" => "st_a", # Command
                        "a" => 'del_no', # Action
                        "sid" => $siteId,
                        "uid" => $userId,
                    ))])
            )
        );

        $replyMarkup = Keyboard::make([
            "inline_keyboard" => $buttons
        ]);

        Telegram::answerCallbackQuery([
            "callback_query_id" => $callbackQueryId,
            "cache_time" => self::CALLBACK_CACHE_TIME
        ]);

        Telegram::editMessageText([
            "chat_id" => $chatId,
            "message_id" => $messageId,
            "text" => "Вы уверены?",
            "reply_markup" => $replyMarkup
        ]);
    }

    private function onCallbackSettingsActionDeleteConfirm($callbackQueryId, $callbackData, $siteId, $userId, $chatId, $messageId)
    {
        if (empty($siteId))
        {
            Log::error('TelegramBotController onCallbackSettingsActionDelete() error.');
            Log::error('CallbackQuery Error. Chat Id: ' . $chatId . ' Message Id: ' . $messageId);
            Log::error('Error msg: empty website id. Callback Data: ' . $callbackData);
            Telegram::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                "cache_time" => self::CALLBACK_CACHE_TIME
            ]);
            return;
        }

        Site::where([ 'id' => $siteId, 'user_id' => $userId ])->update([ 'deleted' => true ]);

        $this->onCallbackSettingsActionBackToSites($callbackQueryId, $userId, $chatId, $messageId, 'Сайт успешно удалён');
    }

    private function onCallbackSettingsActionBackToSites($callbackQueryId, $userId, $chatId, $messageId, $answerCallbackMessage = null)
    {
        $answerCallbackData = array(
            "callback_query_id" => $callbackQueryId,
            "cache_time" => self::CALLBACK_CACHE_TIME
        );

        if (!empty($answerCallbackMessage))
            $answerCallbackData['text'] = $answerCallbackMessage;

        $sites = Site::where([ 'user_id' => $userId, 'deleted' => false ])->get();

        if (!count($sites))
        {
            Telegram::answerCallbackQuery($answerCallbackData);
            Telegram::editMessageText([
                "chat_id" => $chatId,
                "message_id" => $messageId,
                "text" => "Похоже вы удалили все свои сайты.\nДобавьте новый сайт с помощью команды - /code",
            ]);
            return;
        }

        $buttons = $sites->map(function (Site $site) {
            return [Keyboard::button([
                'text' => $site->url,
                'callback_data' => json_encode(array(
                    'c' => 'st', # Command => Settings
                    'sid' => $site->id,
                    'uid' => $site->user_id,
                ))
            ])];
        });

        $replyMarkup = Keyboard::make([
            "inline_keyboard" => $buttons
        ]);

        Telegram::answerCallbackQuery($answerCallbackData);

        Telegram::editMessageText([
            "chat_id" => $chatId,
            "message_id" => $messageId,
            "text" => 'Список сайтов',
            "reply_markup" => $replyMarkup
        ]);
    }

    private function sendSiteInfoInlineButtons($callbackQueryId, $callbackData, $callbackJson, $siteId, $userId, $chatId, $messageId, $updateField = null, $answerCallbackMessage = null)
    {
        $site = $this->getSite($siteId, $userId);

        if (empty($site))
        {
            Log::error('TelegramBotController onCallbackSettings() error.');
            Log::error('CallbackQuery Error. Chat Id: ' . $chatId . ' Message Id: ' . $messageId);
            Log::error('Error msg: not found website in database. Callback Data: ' . $callbackData);
            Telegram::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                "cache_time" => self::CALLBACK_CACHE_TIME
            ]);
            return;
        }

        # Обновляем поле, если нужно
        if (!empty($updateField))
        {
            try
            {
                if (strpos($updateField, 'notifications_') !== false)
                {
                    $fieldState = ($updateField == 'notifications_on');
                    $site->visits = $fieldState;
                    $site->start_of_input = $fieldState;
                    $site->form_submission = $fieldState;
                    $site->clicks_on_phone = $fieldState;
                    $site->clicks_on_whatsapp = $fieldState;
                    $site->whatsapp_id = $fieldState;
                }
                else
                {
                    $site->$updateField = !$site->$updateField;
                }
                $site->save();
            }
            catch (Exception $ex)
            {
                Log::error('TelegramBotController sendSiteInfoInlineButtons() error.');
                Log::error('Update website field error. Site: ' . $site->url . ' Field: ' . $updateField);
            }
        }
        $answerCallbackData = $this->getAnswerCallbackData($callbackQueryId, $answerCallbackMessage);

        $buttons = array();
        foreach (self::CALLBACK_SETTINGS_ACTIONS as $action => $action_value)
        {
            if ($action == 'notifications')
            {
                $allEnabled = ($site->visits && $site->start_of_input &&
                    $site->form_submission && $site->clicks_on_phone &&
                    $site->clicks_on_whatsapp && $site->whatsapp_id);
                if ($allEnabled)
                {
                    $text = '❌ Остановить все оповещения';
                    $action_value['key'] = 'noff';
                }
                else
                {
                    $text = '✅ Запустить все оповещения';
                    $action_value['key'] = 'non';
                }
            }
            else if ($action == 'whatsapp_btn')
            {
                $text = '📲 ' . $action_value['title'];
            }
            else if ($action == 'back_to_sites')
            {
                $text = '⬅ ' . $action_value['title'];
            }
            else if ($action == 'delete')
            {
                $text = '🗑 ' . $action_value['title'];
            }
            else
            {
                $text = ($site->$action ? '✅' : '❌') . ' ' . $action_value['title'];
            }
            array_push($buttons, array(
                Keyboard::button([
                    "text" => $text,
                    "callback_data" => json_encode(array(
                        "c" => "st_a", # Command
                        "a" => $action_value['key'], # Action
                        "sid" => $site->id,
                        "uid" => $site->user_id,
                    ))])
            ));
        }

        $replyMarkup = Keyboard::make([
            "inline_keyboard" => $buttons
        ]);

        Telegram::answerCallbackQuery($answerCallbackData);

        Telegram::editMessageText([
            "chat_id" => $chatId,
            "message_id" => $messageId,
            "text" => "Настройки оповещений сайта - " . $site->url,
            "reply_markup" => $replyMarkup
        ]);
    }

    private function sendWhatsAppButtonSettingInlineButtons($callbackQueryId, $callbackData, $callbackJson, $siteId, $userId, $chatId, $messageId, $updateField = null, $answerCallbackMessage = null)
    {
        $site = $this->getSite($siteId, $userId);

        if (empty($site))
        {
            Log::error('TelegramBotController sendWhatsAppButtonSettingInlineButtons() error.');
            Log::error('CallbackQuery Error. Chat Id: ' . $chatId . ' Message Id: ' . $messageId);
            Log::error('Error msg: not found website in database. Callback Data: ' . $callbackData);
            Telegram::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                "cache_time" => self::CALLBACK_CACHE_TIME
            ]);
            return;
        }

        # Обновляем поле, если нужно
        if (!empty($updateField))
        {
            try
            {
                if ($updateField == 'wb_widget_settings')
                {
                    $telegramUser = TelegramUser::firstOrNew([
                        'chat_id' => $chatId,
                    ]);

                    $telegramUser->fill([
                        'last_command' => 'wb_ww_get_phone_' . $siteId
                    ]);

                    $telegramUser->save();
                }
                else
                {
                    $site->$updateField = !$site->$updateField;
                    $site->save();
                }
            }
            catch (Exception $ex)
            {
                Log::error('TelegramBotController sendWhatsAppButtonSettingInlineButtons() error.');
                Log::error('Update website field error. Site: ' . $site->url . ' Field: ' . $updateField);
            }
        }

        $answerCallbackData = $this->getAnswerCallbackData($callbackQueryId,
            (($updateField == 'wb_widget_settings') ? null : $answerCallbackMessage),
            (($updateField == 'wb_widget_settings') ? 1 : null));


        $buttons = array();
        if ($updateField != 'wb_widget_settings')
        {
            $msg = "Настройки WhatsApp виджета для сайта - " . $site->url;
            foreach (self::CALLBACK_WHATSAPP_BUTTON_ACTIONS as $action => $action_value)
            {
                if ($action == 'wb_widget_settings')
                {
                    $text = '📱 ' . $action_value['title'];
                }
                else if ($action == 'wb_widget_state')
                {
                    $text = ($site->$action ? '❌ Отключить виджет' : '✅ Включить виджет');
                }
                else if ($action == 'wb_widget_desktop_state')
                {
                    $text = ($site->$action ? '❌ Не показывать на компьютере' : '✅ Показывать на компьютере');
                }
                else if ($action == 'wb_widget_mobile_state')
                {
                    $text = ($site->$action ? '❌ Не показывать на телефоне' : '✅ Показывать на телефоне');
                }
                else if ($action == 'wb_widget_show_side')
                {
                    # true - показывается справа, false - слева
                    $text = ($site->$action ?  '↙ Показывать слева' : '↘ Показывать справа');
                }
                else if ($action == 'back_to_actions')
                {
                    $text = '⬅ ' . $action_value['title'];
                }
                else
                {
                    $text = $action_value['title'];
                }
                array_push($buttons, array(
                    Keyboard::button([
                        "text" => $text,
                        "callback_data" => json_encode(array(
                            "c" => "st_a", # Command
                            "a" => $action_value['key'], # Action
                            "sid" => $site->id,
                            "uid" => $site->user_id,
                        ))])
                ));
            }
        }
        else
        {
            $msg = "Пришлите номер в формате 71234567890, на который будут писать с виджет-кнопки WhatsApp на сайте" .
                (($chatId > 0) ? " или просто нажмите на панели кнопку для отправки номера" : "") . ".\n" .
                "Номер можно будет поменять в любой момент через запуск команды [Настроить].\n" .
                "Не используйте в номере никакие символы + - ( ) . ,\n" .
                "Телефон должен начинаться с кода вашей страны: 7.., 380.. и т.д";
        }

        Telegram::answerCallbackQuery($answerCallbackData);

        if ($updateField != 'wb_widget_settings')
        {
            $replyMarkup = Keyboard::make([
                "inline_keyboard" => $buttons
            ]);

            Telegram::editMessageText([
                "chat_id" => $chatId,
                "message_id" => $messageId,
                "text" => $msg,
                "reply_markup" => $replyMarkup
            ]);
        }
        else
        {
            if ($chatId > 0)
            {
                $requestPhoneButton = Keyboard::button([
                    'text' => 'Отправить номер',
                    'request_contact' => true,
                ]);

                Telegram::sendMessage([
                    "chat_id" => $chatId,
                    "text" => $msg,
                    "reply_markup" => Keyboard::make([
                        "keyboard" => [[$requestPhoneButton]],
                        "one_time_keyboard" => true,
                        "resize_keyboard" => true
                    ])
                ]);
            }
            else
            {
                Telegram::sendMessage([
                    "chat_id" => $chatId,
                    "text" => $msg,
                ]);
            }
        }
    }

    /**
     * @param $callbackQueryId
     * @param $callbackData
     * @param $callbackJson
     * @param $chatId
     * @param $messageId
     */
    private function onCallbackWhatsAppButtonWidgetComplete($callbackQueryId, $callbackData, $callbackJson, $chatId, $messageId)
    {
        $siteId = $callbackJson['sid'];
        $userId = $callbackJson['uid'];
        $site = $this->getSite($siteId, $userId);

        if (empty($site))
        {
            Log::error('TelegramBotController onCallbackWhatsAppButtonWidgetComplete() error.');
            Log::error('CallbackQuery Error. Chat Id: ' . $chatId . ' Message Id: ' . $messageId);
            Log::error('Error msg: not found website in database. Callback Data: ' . $callbackData);
            Telegram::answerCallbackQuery([
                "callback_query_id" => $callbackQueryId,
                "text" => "Невозможно обработать это действие, повторите попытку или обратитесь в поддержку",
                "cache_time" => self::CALLBACK_CACHE_TIME
            ]);
            return;
        }

        $answerCallbackData = $this->getAnswerCallbackData($callbackQueryId, 'Готово', 1);

        Telegram::answerCallbackQuery($answerCallbackData);

        Telegram::sendMessage([
            "chat_id" => $chatId,
            "text" => $this->getMessageForCompleteWhatsAppButtonWidget($site),
            "reply_markup" => Keyboard::remove()
        ]);
    }

    /**
     * @param Site $site
     * @return string
     */
    private function getMessageForCompleteWhatsAppButtonWidget(Site $site)
    {
        $msg = "Готово! ";

        if ($site->wb_widget_state)
            $msg .= "Теперь можете проверить WhatsApp виджет на своём сайте: {$site->url}\n";
        else
            $msg .= "В данный момент виджет настроен, но не включен.\n" .
                "Включите виджет в настройках сайт - /settings и проверьте его работу на своём сайте: {$site->url}\n";

        $msg .= "Возможно, потребуется обновить кеш браузера для корректной работы виджета.";

        return $msg;
    }

    private function getAnswerCallbackData($callbackQueryId, $answerCallbackMessage, $cache_time = null)
    {
        $answerCallbackData = array(
            "callback_query_id" => $callbackQueryId,
            "cache_time" => (is_null($cache_time) ? self::CALLBACK_CACHE_TIME : $cache_time)
        );

        if (!empty($answerCallbackMessage))
            $answerCallbackData['text'] = $answerCallbackMessage;

        return $answerCallbackData;
    }

    /**
     * @param $siteId
     * @param $userId
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    private function getSite($siteId, $userId)
    {
        return Site::where([ 'id' => $siteId, 'user_id' => $userId, 'deleted' => false ])->first();
    }

    private function getActionKey($action_min_key)
    {
        if (($action_min_key == 'non') || ($action_min_key == 'noff'))
            return 'notifications_' . str_replace('no', 'o', $action_min_key);

        $result = '';
        if (substr($action_min_key, 0, 3) == 'wb_')
        {
            foreach (self::CALLBACK_WHATSAPP_BUTTON_ACTIONS as $action_key => $action) {
                if ($action['key'] == $action_min_key)
                {
                    $result = $action_key;
                    break;
                }
            }
        }
        else
        {
            foreach (self::CALLBACK_SETTINGS_ACTIONS as $action_key => $action) {
                if ($action['key'] == $action_min_key)
                {
                    $result = $action_key;
                    break;
                }
            }
        }
        return $result;
    }
}
