<?php

namespace App\Http\Controllers;

use Config;
use Illuminate\Http\Request;
use Telegram;
use Log;
use App\TelegramUser;
use Str;
use App\User;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\HttpClients\GuzzleHttpClient as TGGuzzleHttpClient;
use GuzzleHttp\Client as GuzzleHttpClient;
use App\Custom\Helpers;

class TelegramController extends Controller
{
    /**
     * @param string $token
     * @return Api
     * @throws TelegramSDKException
     */
    private function getClient($token)
    {
        $client = new TGGuzzleHttpClient(
            new GuzzleHttpClient([
                'verify' => false,
                //'proxy' => '81.91.130.1:3128' # 176.105.100.62:3128
            ])
        );
        return new Api( $token, false, $client);
    }

    public function setWebhook()
    {
        try
        {
            $token = Telegram::getAccessToken();
            if (empty($token))
                return 'Set webhook error, empty token.';
            $telegram = $this->getClient($token);
            # getenv('TELEGRAM_BOT_TOKEN')
            $app_url = Config::get('app.url');
            if (empty($app_url))
                return 'Register uri empty, please set uri in config file.';
            $telegram->setWebhook([
                'url' => $app_url . '/api/telegram/' . $token . '/webhook',
            ]);
            return 'Success set webhook for domain: ' . $app_url;
        }
        catch (TelegramSDKException $ex)
        {
            Log::error($ex->getTraceAsString());
            return 'Set webhook error, see log file. ' . PHP_EOL . $ex->getMessage();
        }
    }

    public function webHook(Request $request)
    {
        Log::debug('WebHook');
        $result = Telegram::getWebhookUpdates();
        Log::debug($result);

        $telegram = $this->getClient();

        if (isset($result["message"])) {
            $chat_id = $result["message"]["chat"]["id"];

            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => print_r($result, 1)
            ]);
            if ($chat_id == '114246789') { }

            $text = $result["message"]["text"] ?? '';
            $first_name = $result['message']['chat']['first_name'] ?? '';
            $last_name = $result['message']['chat']['last_name'] ?? '';
            $username = $result["message"]["chat"]["username"] ?? '';
            /*if( isset($result["message"]["contact"]) && isset($result["message"]["contact"]["phone_number"]) ){
                $user->phone = $result["message"]["contact"]["phone_number"];
                $user->save();
                try{
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => 'Для продолжения отправьте команду /start'
                    ]);
                }catch( \Exception $e ){
                    Log::error( ['tg_err', $chat_id] );
                    Log::error( $e );
                }
            }else */if ($text) {
                if (mb_stripos($text, '/start') !== false) {

                    // проверяем наличие телефона
                    //if( $user->phone ){

                        $tg_user = TelegramUser::where('chat_id', $chat_id)->first();
                        $user    = User::where('name', $chat_id)->first();
                        if (!$tg_user) {
                            $tg_user = new TelegramUser();
                            $tg_user->chat_id    = $chat_id;
                            $tg_user->first_name = $first_name;
                            $tg_user->last_name  = $last_name;
                            $tg_user->user_name  = $username;
                            $tg_user->save();
                        }
                        if(!$user){
                            $user       = new User();
                            $user->name = $chat_id;
                            $user->save();
                        }
                        $code = Str::random(32);
                        $tg_user->first_name = $first_name;
                        $tg_user->user_name  = $username;
                        $tg_user->last_name  = $last_name;
                        $tg_user->code       = $code;
                        $tg_user->save();
    
                        $lwt_token = Helpers::genToken( $user );
                        $lwturl = route('lwt', [
                            'token' => $lwt_token
                        ]);
                        $sm['text']    = "Ваш код для авторизации - $code\nВаша ссылка для авторизации - $lwturl\n\nНикому не сообщайте вашу ссылку для авторизации.";
                        $sm['chat_id'] = $chat_id;
                        try{
                            $telegram->sendMessage($sm);
                        }catch( \Exception $e ){
                            Log::error( ['tg_err', $chat_id] );
                            Log::error( $e );
                        }
                    /*}else{
                        try{
                            $telegram->sendMessage([
                                'chat_id' => $chat_id,
                                'text'    => 'Для продолжения отправьте свой контакт'
                            ]);
                        }catch( \Exception $e ){
                            Log::error( ['tg_err', $chat_id] );
                            Log::error( $e );
                        }
                    }*/
                }
            }
        }
    }
}
