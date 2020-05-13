<?php

namespace App\Http\Controllers;

use Exception;
use Log;
use Telegram;
use Validator;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Site;
use App\Action;
use App\Client;
use App\UserClient;
use App\GeoBase;
use App\GeoCity;
use Carbon\Carbon;
use Telegram\Bot\Api;
use Browser;

class ApiController extends Controller
{
    /**
     * Допустимые действия
     */
    const ACTIONS = array(
        'Visit',
        'Submit',
        'FormFirstChange',
        'ClickPhoneLink',
        'ClickWhatsAppLink'
    );

    /**
     * @var Action
     */
    private $action;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var array
     */
    private $_request;

    /**
     * @var Site
     */
    private $site;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var UserClient
     */
    private $user_client;

    private function getGeo($ip)
    {
        $returned = [
            'base' => false,
            'city' => false
        ];
        $long_ip = ip2long($ip);
        $long_ip = sprintf("%u", $long_ip);
        $geo_base = GeoBase::where('long_ip1', '<=', $long_ip)->where('long_ip2', '>=', $long_ip)->first();
        if ($geo_base)
        {
            $returned['base'] = $geo_base;
            $geo_city = GeoCity::where('id', $geo_base->city_id)->first();
            if ($geo_city)
                $returned['city'] = $geo_city;
        }
        else
        {
            Log::info('Локация не найдена', [ 'ip' => $ip, 'long_ip' => $long_ip]);
        }
        return $returned;
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function requestValidate($request)
    {
        $validator = Validator::make($request->all(), [
            'fingerprint' => 'required|string|min:32|max:32',
            'code'        => 'required|string',
            'action'      => 'required|string'
        ]);
        # If fails -> false, else true
        return !$validator->fails();
    }

    /**
     * Главный контроллер
     * @param Request $request
     * @return Response
     */
    public function getData(Request $request)
    {
        $response = new Response(['status' => 'ok'], 200);
        // кука только с принтом?
        // проверка оригин + сайт???????????????????????????????????????????????????????????????????????????????
        // проверить в браузерах

        try
        {
            # Все получаемые данные
            $_request = $request->all();
            if (!$this->requestValidate($request))
                return $response;

            # Проверяем валидность ссылки
            $parsedUrl = parse_url($_request['url']);
            if (!(isset($parsedUrl['scheme']) && isset($parsedUrl['host'])))
            {
                Log::error('Ошибка url:');
                Log::error($_request['url']);
                return $response->setContent(['status' => 'fail', 'message' => 'Invalid url']);
            }
            if ($parsedUrl['scheme'] !== 'http' && $parsedUrl['scheme'] !== 'https')
                return $response->setContent(['status' => 'fail', 'message' => 'Invalid url scheme']);

            $_request['url'] = $parsedUrl['scheme'].'://'.$parsedUrl['host'];
            if (isset($parsedUrl['port']))
                $_request['url'] .= ':' . $parsedUrl['port'];

            if (isset($parsedUrl['path']))
                $_request['url'] .= $parsedUrl['path'];

            if (isset($parsedUrl['query']))
                $_request['url'] .= '?' . $parsedUrl['query'];

            # Обрезаем url, если слишком длинный
            $_request['url'] = iconv_substr($_request['url'], 0 , 200 , "UTF-8");

            # Проверяем правильность действия
            if (!in_array($_request['action'], self::ACTIONS))
                return $response->setContent(['status' => 'fail', 'message' => 'Invalid action']);

            # Получаем айди и код сайта
            $siteArr = explode('_', $_request['code']);
            # Проверяем код сайта
            if (count($siteArr) != 2)
                return $response->setContent(['status' => 'fail', 'message' => 'Invalid site code']);

            # Ищем сайт и проверяем
            if (!($site = Site::where([ 'id' => $siteArr[0], 'code' => $siteArr[1] ])->first()))
                return $response->setContent(['status' => 'fail', 'message' => 'Invalid site code']);

            if ($site->deleted)
                return $response->setContent(['status' => 'fail', 'message' => 'Deleted']);

            # Ищем клиента по фингерпринту
            $fingerprint_2      = $_request['fingerprint'];
            $fingerprint_cookie = $request->cookie('_fp');
            //Log::info( [$fingerprint_cookie, $fingerprint_2] );

            # Проверяем соответсвие куки правилу
            if(is_string($fingerprint_cookie) && mb_strlen($_request['fingerprint'], 'UTF-8') === 32)
            {
                # Если кука есть ищем по ней
                $client = Client::where( 'fingerprint', $fingerprint_cookie )->first();
                //Log::info('ищем по куки');
                # Если не нашло пытаемся найти по принту
                if (!$client && ($fingerprint_2 !== $fingerprint_cookie))
                {
                    $client = Client::where( 'fingerprint', $fingerprint_2 )->first();
                    //Log::info('ищем по принту 1');
                }
            }
            else
            {
                # Если куки нет ищем по принту
                $client = Client::where( 'fingerprint', $fingerprint_2 )->first();
                //Log::info('ищем по принту 2');
            }

            # Если не нашли создаем нового клиента
            if (!$client)
            {
                $client = new Client();
                $client->fingerprint = $fingerprint_2;
                $client->save();
            }

            # Если отпечаток браузера не соответствует куки то обновляем отпечаток в базе
            if ($client->fingerprint !== $fingerprint_2)
            {
                $client->fingerprint = $fingerprint_2;
                $client->save();
            }

            # Ищем связь клиента и пользователя
            $user_client = UserClient::where([
                'user_id' => $site->user_id,
                'client_id' => $client->id
            ])->first();

            # Если нет, то создаем нового
            if (!$user_client)
            {
                $last_user_client = UserClient::select('local_client_id')
                    ->where('user_id', $site->user_id)
                    ->orderBy('local_client_id', 'desc')
                    ->first();
                $last_user_client_id = 1;
                if ($last_user_client)
                    $last_user_client_id = $last_user_client->local_client_id + 1;

                $user_client = new UserClient();
                $user_client->user_id = $site->user_id;
                $user_client->client_id = $client->id;
                $user_client->local_client_id = $last_user_client_id;
                $user_client->save();
            }

            $this->request     = $request;
            $this->_request    = $_request;
            $this->client      = $client;
            $this->site        = $site;
            $this->user_client = $user_client;

            $this->handleAction();

            if ($client->fingerprint !== $fingerprint_cookie)
                $response->withCookie(cookie()->forever('_fp', $client->fingerprint));
            if ($user_client->local_client_id > 0)
            {
                $resp_params = array(
                    'status' => 'ok',
                    'id' => $user_client->local_client_id,
                    'wid' => $site->whatsapp_id,
                    'ww' => false
                );

                if ($site->wb_widget_state && !empty($site->wb_widget_phone))
                {
                    $resp_params['ww'] = true;
                    $resp_params['ww_phone'] = $site->wb_widget_phone;
                    $resp_params['ww_text'] = $site->wb_widget_text;
                    $resp_params['ww_d'] = $site->wb_widget_desktop_state;
                    $resp_params['ww_m'] = $site->wb_widget_mobile_state;
                    $resp_params['ww_s'] = $site->wb_widget_show_side;
                }

                $response->setContent($resp_params);
            }
        }
        catch(Exception $e)
        {
            Log::error('Error Message: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
        }

        return $response;
    }

    /**
     * @throws Telegram\Bot\Exceptions\TelegramSDKException
     */
    private function handleAction()
    {
        $user_ip = $this->request->ip();
        $action = new Action();
        $action->site_id     = $this->site->id;
        $action->client_id   = $this->client->id;
        $action->action      = $this->_request['action'];
        $action->ip          = $user_ip;
        $action->url         = $this->_request['url'];

        $action->browser    = Browser::browserFamily();
        $action->browser_v  = Browser::browserVersion();
        $action->platform   = Browser::platformFamily();
        $action->platform_v = Browser::platformVersion();

        if (empty(trim($action->browser)))
        {
            Log::warning('Unknown user-agent: ' . Browser::userAgent() . ' visit web-site - ' . $this->_request['url']);
        }

        $geo = $this->getGeo($user_ip);
        if ($geo['base'])
        {
            $action->country = $geo['base']->country;
            if ($geo['city'])
                $action->city = $geo['city']->city;
        }

        $formData = [];
        if (isset($this->_request['data']) && is_string($this->_request['data']))
            parse_str($this->_request['data'], $formData);

        $action->data = json_encode($formData);

        $referer = parse_url($this->_request['referer'], PHP_URL_HOST);
        $action->referer = $referer;
        $action->save();

        $this->action = $action;

        if (Browser::isBot())
            return;

        $this->sendMessage($formData, $action->platform, $action->platform_v, $user_ip);
    }

    /**
     * @param $formData
     * @param string $platform
     * @param string $platformV
     * @param string $user_ip
     * @throws Telegram\Bot\Exceptions\TelegramSDKException
     */
    private function sendMessage($formData, $platform, $platformV, $user_ip)
    {
        # Формируем переменную на основе всех уведомлений
        $allNotificationDisabled = ($this->site->visits == false && $this->site->start_of_input == false &&
            $this->site->form_submission == false && $this->site->clicks_on_phone == false &&
            $this->site->clicks_on_whatsapp == false && $this->site->whatsapp_id == false);

        if ($allNotificationDisabled)
            return;

        $local_client_id = $this->user_client->local_client_id;
        $action = $this->action->action;
        $message = sprintf("Клиент %s %s %s", $local_client_id, $this->get_action_message($action), $this->site->url);

        $message .= "\nIP: " . $user_ip;
        if ($this->action->country && $this->action->city)
            $message .= "\n" . $this->action->country . ", " . $this->action->city;
        else if ($this->action->country)
            $message .= "\n" . $this->action->country;

        # OS + Version
        if (!empty($platform))
            $message .= sprintf("\nУстройство: %s%s", $platform, (!empty($platformV) ? ', ' . $platformV : ''));

        # Обрабатываем метки Referer + Yandex/Google/Facebook
        if (!empty($this->_request['referer']))
            $message .= "\nИсточник: " . $this->action->referer;
        if (!empty($this->_request['source']))
            $message .= "\nutm_source: " . $this->_request['source'];
        if (!empty($this->_request['medium']))
            $message .= "\nutm_medium: " . $this->_request['medium'];
        if (!empty($this->_request['campaign']))
            $message .= "\nutm_campaign: " . $this->_request['campaign'];
        if (!empty($this->_request['content']))
            $message .= "\nutm_content: " . $this->_request['content'];
        if (!empty($this->_request['term']))
            $message .= "\nutm_term: " . $this->_request['term'];
        if (!empty($this->_request['block']))
            $message .= "\nblock: " . $this->_request['block'];
        if (!empty($this->_request['pos']))
            $message .= "\npos: " . $this->_request['pos'];
        if (!empty($this->_request['yclid']))
            $message .= "\nyclid: " . $this->_request['yclid'];
        if (!empty($this->_request['gclid']))
            $message .= "\ngclid: " . $this->_request['gclid'];
        if (!empty($this->_request['fbclid']))
            $message .= "\nfbclid: " . $this->_request['fbclid'];

        foreach ($formData as $key => $value)
        {
            //// Array to string conversion {"exception":"[object] (ErrorException(code: 0): Array to string conversion at /var/www/app/Http/Controllers/ApiController.php:222)
            if (!is_array($value))
            {
                $message .= sprintf("\n%s - %s", $key, $value);
            }
            else
            {
                Log::error('Array to string conversion');
                Log::error((is_array($value) ? json_encode($value) : $value));
            }
        }

        $token = Telegram::getAccessToken();
        if (empty($token))
        {
            Log::error('Token bot not set in settings');
            return;
        }
        $telegram = new Api($token);

        $chat_id = $this->site->user->name;
        if ($action === 'Visit')
        {
            # Ищем последнее действие за 10 минут
            $lastActionCount = Action::where([
                'site_id'   => $this->site->id,
                'client_id' => $this->client->id
            ])->where('id', '!=', $this->action->id)->where('created_at', '>', Carbon::now()->subMinutes(10))->count();
            # Отправляем сообщение в телеграм, если таких не было
            if ($lastActionCount === 0)
            {
                try
                {
                    # Если включены уведомления по визитам
                    if ($this->site->visits)
                        $telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $message, 'caption' => $message ]);
                }
                catch (Exception $e)
                {
                    Log::error('Error Message: ' . $e->getMessage());
                    Log::error('Send Data: ', ['chat_id' => $chat_id, 'message' => $message]);
                    Log::error('Stack Trace: ' . $e->getTraceAsString());
                }
            }
        }
        else
        {
            try
            {
                if ($action === 'FormFirstChange' && !$this->site->start_of_input)
                    return;
                if ($action === 'Submit' && !$this->site->form_submission)
                    return;
                if ($action === 'ClickPhoneLink' && !$this->site->clicks_on_phone)
                    return;
                if ($action === 'ClickWhatsAppLink' && !$this->site->clicks_on_whatsapp)
                    return;
                $telegram->sendMessage( [ 'chat_id' => $chat_id, 'text' => $message, 'caption' => $message] );
            }
            catch(Exception $e)
            {
                Log::error('Error Message: ' . $e->getMessage());
                Log::error('Send Data: ', ['chat_id' => $chat_id, 'message' => $message]);
                Log::error('Stack Trace: ' . $e->getTraceAsString());
            }
        }
    }

    /**
     * @param string $action
     * @return string
     */
    private function get_action_message($action)
    {
        $result = '';
        switch ($action)
        {
            case 'Visit';
                $result = 'на сайте';
                break;
            case 'FormFirstChange';
                $result = 'начал ввод данных в форму на сайте';
                break;
            case 'Submit';
                $result = 'отправил форму на сайте';
                break;
            case 'ClickPhoneLink':
                $phone = '-';
                if (!empty($this->_request['phone']))
                    $phone = $this->_request['phone'];
                $result = 'кликнул по номеру ' . $phone . ' на сайте';
                break;
            case 'ClickWhatsAppLink':
                $link = '-';
                if (!empty($this->_request['wa']))
                    $link = $this->_request['wa'];
                $result = 'кликнул по Whatsapp-ссылке ' . $link . ' на сайте';
                break;
        }
        return $result;
    }
}
