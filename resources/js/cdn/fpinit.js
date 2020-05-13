import Fingerprint2 from 'fingerprintjs2';

window.FpInit = function (site_key) {

    var url      = window.location.href;
    var allWhatsAppLinks = [];
    var whatsAppLink = '';
    var apiData = {
        fingerprint:    '',
        code:           site_key,
        action:         '',
        referer:        document.referrer,
        data:           {},
        phone:          '',
        wa:             '',
        source:         getParameterByName('utm_source'),
        medium:         getParameterByName('utm_medium'),
        campaign:       getParameterByName('utm_campaign'),
        content:        getParameterByName('utm_content'),
        term:           getParameterByName('utm_term'),
        block:          getParameterByName('block'),
        pos:            getParameterByName('pos'),
        yclid:          getParameterByName('yclid'),
        gclid:          getParameterByName('gclid'),
        fbclid:         getParameterByName('fbclid'),
        url:            url
    };

    /**
     *
     * @param action - Действие
     * @param fingerprint - Fingerprint
     * @param formData - Данные формы
     * @param phone - Телефон
     * @param wa - WhatsApp ссылка
     */
    function sendRequestGetData(action, fingerprint, formData, phone, wa) {
        apiData.action = action;
        apiData.fingerprint = fingerprint;
        apiData.data = (formData ? formData : {});
        apiData.phone = (phone ? phone : '');
        apiData.wa = (wa ? wa : '');
        jQuery.ajax('https://user-agent.cc/api/getdata', { /* https://user-agent.cc/api/getdata */
            type: 'POST',
            data: apiData,
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            }
        }).done(function (data) {
            if (apiData.action === 'Visit' && data.hasOwnProperty('status') && data.status === 'ok' && data.hasOwnProperty('id'))
            {
                if (allWhatsAppLinks.length > 0 && data.hasOwnProperty('wid') && data.wid > 0)
                {
                    for (var i = 0; i < allWhatsAppLinks.length; i++)
                    {
                        var linkEl = allWhatsAppLinks[i],
                            textPos = linkEl.href.toLowerCase().indexOf('text=');
                        if (textPos > -1)
                        {
                            var text = linkEl.href.substr(textPos + 5),
                                posAmpersand = text.indexOf('&');
                            if (posAmpersand > -1)
                                text = text.substr(0, posAmpersand);
                            var newText = text + '%20%23' + data.id;
                            linkEl.setAttribute('href', linkEl.href.replace(text, newText));
                        }
                    }
                }
                if (data.hasOwnProperty('ww') && data.ww && data.hasOwnProperty('ww_phone') && data.ww_phone > 0) {
                    buildWhatsAppWidget(data);
                }
            }
        });
    }

    // внедрение сторонних скриптов
    function loadScript(url, callback) {
        // Добавляем тег сценария в head – как и предлагалось выше
        var head = document.getElementsByTagName('head')[0];
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;
        // Затем связываем событие и функцию обратного вызова.
        // Для поддержки большинства обозревателей используется несколько событий.
        script.onreadystatechange = callback;
        script.onload = callback;

        // Начинаем загрузку
        head.appendChild(script);
    }

    // парсим гет запросы
    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
        var results = regex.exec(location.search);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    }

    // получаем принт(получает только один раз, через коллбэк, дальше только возвращает)
    function getFingerprint(callback) {
        function _getFingerprint()
        {
            Fingerprint2.get(
                {
                    excludes: {
                        'enumerateDevices': true,
                        'pixelRatio':       true,
                        'doNotTrack':       true,
                        'fontsFlash':       true,
                        'deviceMemory':     true
                    }
                },
                function (components)
                {
                    var murmur = Fingerprint2.x64hash128(components.map(function (pair) { return pair.value }).join(), 31);
                    //console.log(components, murmur);
                    getFingerprint = () => murmur;
                    callback(murmur);
                });
        }
        if (window.requestIdleCallback)
            requestIdleCallback(_getFingerprint);
        else
            setTimeout(_getFingerprint, 500);
    }

    // отправляется один раз
    function requestInit(fingerprint) {
        sendRequestGetData('Visit', fingerprint);
    }

    function requestSubmit(formData) {
        sendRequestGetData('Submit', getFingerprint(), formData);
    }

    function requestFormFirstChange (formData) {
        sendRequestGetData('FormFirstChange', getFingerprint(), formData);
    }

    function requestClickPhoneLink(phone) {
        sendRequestGetData('ClickPhoneLink', getFingerprint(), {}, phone);
    }

    function requestClickWhatsAppLink(wa) {
        sendRequestGetData('ClickWhatsAppLink', getFingerprint(), {}, '', wa);
    }

    // обработчик формы
    function submitHandler(e) {
        var $el  = jQuery(this);
        var type = $el.attr('type');
        if ((typeof type === 'string' && type.toLowerCase() === 'submit')) {
            var form = $el.closest('form');
            if (form.length === 0)
                return;
            if (form.find('[type=password]').length)
                return;
            var form_data = form.serialize();
            requestSubmit(form_data);
        }
    }

    // ставим обработчики на формы
    function initForm() {
        jQuery('button, input').click(submitHandler);// поменять на тайп сабмит?????????
        jQuery('form').each(function (i, el) {
            // действие при первом изменении формы, стреляет один раз
            var inputCB = function(){
                inputCB = function(){};
                requestFormFirstChange($.param(data, true));
            };
            var $el    = jQuery(el);
            var action = $el.attr('action');
            var name   = $el.attr('name');

            var data = {};

            if (typeof action === 'string')
                if (action.length === 0)
                    action = './';
            else
                action = './';
            data.action = action;
            if (typeof name === 'string')
                data.name = name;

            $el.find('input, textarea').each(function (i, input) {
                var $input = jQuery(input);
                var focus  = false;
                $input.focusin(function () { focus = true; });
                $input.on('input', function () {
                    if (focus)
                        inputCB();
                });
            });
        });
        var allLinks = jQuery('a');
        allLinks.each(function (i, el) {
            if (el && el.href)
            {
                var href = el.href.toLowerCase();
                if (href.indexOf('tel:') > -1)
                    jQuery(el).on('click', onClickPhoneLink);
                else if ((href.indexOf('//wa.me/') > -1) || (href.indexOf('//api.whatsapp.com/') > -1))
                {
                    allWhatsAppLinks.push(el);
                    jQuery(el).on('click', onClickWhatsAppLink);
                }
            }
        })
    }

    function onClickPhoneLink(e) {
        var href = e.target.href;
        if ((typeof href === 'string') && href.length > 0)
        {
            try
            {
                var phone = href.substr(4).trim().replace(/\s?/, '');
                if (phone.length > 0)
                    requestClickPhoneLink(phone);
            }
            catch (err)
            {
                console.error('Parse phone error: ', err);
            }
        }
    }

    function onClickWhatsAppLink(e) {
        var target = e.target,
            href = '';
        if (target.localName.toLowerCase() === 'a')
            href = target.href;
        else if (target.closest)
            href = target.closest('a').href;
        if ((typeof href === 'string') && href.length > 0)
        {
            try
            {
                requestClickWhatsAppLink(href);
            }
            catch (err)
            {
                console.error('Send WA link: ', err);
            }
        }
    }

    getFingerprint(function (fingerprint) {
        function ifJQuery() {
            requestInit(fingerprint);
            initForm();
        }
        // проверяем наличие jquery
        if(typeof jQuery === 'undefined')
            loadScript('https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js', ifJQuery);
        else
            ifJQuery();
    });

    /**
     * @param {{id: {int}, ww_phone: {int}, ww_text: {string}, ww_d: {boolean}, ww_m: {boolean}, ww_s: {boolean}}} opt
     */
    function buildWhatsAppWidget(opt) {
        var cssStyle = '.ua-wa-wb{background-color:#4dc247;-webkit-box-shadow:1px 1px 5px #4dc247;box-shadow:1px 1px 5px #4dc247;border-radius:50%;bottom:20px;right:20px;cursor:pointer;outline:none;position:fixed;width:50px;height:50px;text-align:center;text-decoration:none;z-index:999999;-webkit-tap-highlight-color:transparent!important}.ua-wa-wb svg{height:50px;display:inline;fill:white;width:41px}.ua-wa-wb-left{right:auto;left:20px}.ua-wa-wb-pulse{animation:animation-ua-pulse 2s infinite;-webkit-animation:animation-ua-pulse 2s ease-out;-webkit-animation-iteration-count:infinite}.ua-wa-wb-pulse:hover{-webkit-animation:linear;animation:linear}.ua-wa-wb:active,.ua-wa-wb:hover{-webkit-box-shadow:1px 1px 15px rgba(0,0,0,0.5)!important;box-shadow:1px 1px 15px rgba(0,0,0,0.5)!important}@-webkit-keyframes animation-ua-pulse{0%{-webkit-box-shadow:0 0 0 0 #4dc247}70%{-webkit-box-shadow:0 0 0 15px rgba(0,0,0,0)}100%{-webkit-box-shadow:0 0 0 0 rgba(0,0,0,0)}}@keyframes animation-ua-pulse{0%{-webkit-transform:scale(1.0,1.0);transform:scale(1.0,1.0);-webkit-box-shadow:0 0 0 0 #4dc247;box-shadow:0 0 0 0 #4dc247}10%{-webkit-transform:scale(1.1,1.1);transform:scale(1.1,1.1)}15%{-webkit-transform:scale(1.0,1.0);transform:scale(1.0,1.0)}70%{-webkit-box-shadow:0 0 0 15px rgba(0,0,0,0);box-shadow:0 0 0 15px rgba(0,0,0,0)}100%{-webkit-box-shadow:0 0 0 0 rgba(0,0,0,0);box-shadow:0 0 0 0 rgba(0,0,0,0)}}@media(max-width:991px){.ua-wa-wb-hm{display:none;visibility:hidden}}@media(min-width:992px){.ua-wa-wb-hd{display:none;visibility:hidden}}',
            head = document.head || document.getElementsByTagName('head')[0],
            body = document.body,
            styleEl = document.createElement('style');
        styleEl.type = 'text/css';
        if (head)
            head.appendChild(styleEl);
        else
            body.appendChild(styleEl);
        if (styleEl.styleSheet){
            // This is required for IE8 and below.
            styleEl.styleSheet.cssText = cssStyle;
        } else {
            styleEl.appendChild(document.createTextNode(cssStyle));
        }

        var waUri = 'https://api.whatsapp.com/send?phone=' + opt.ww_phone,
            clsName = 'ua-wa-wb ua-wa-wb-pulse',
            aEl = document.createElement('a');
        if (opt.hasOwnProperty('ww_text') && opt.ww_text.length > 0)
        {
            waUri += '&text=' + encodeURIComponent(opt.ww_text + ((opt.hasOwnProperty('wid') && opt.wid > 0) ? ' #' + opt.id : ''));
        }

        if (opt.hasOwnProperty('ww_s') && !opt.ww_s)
            clsName += ' ua-wa-wb-left';
        if (opt.hasOwnProperty('ww_d') && !opt.ww_d)
            clsName += ' ua-wa-wb-hd';
        if (opt.hasOwnProperty('ww_m') && !opt.ww_m)
            clsName += ' ua-wa-wb-hm';

        aEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d=" M19.11 17.205c-.372 0-1.088 1.39-1.518 1.39a.63.63 0 0 1-.315-.1c-.802-.402-1.504-.817-2.163-1.447-.545-.516-1.146-1.29-1.46-1.963a.426.426 0 0 1-.073-.215c0-.33.99-.945.99-1.49 0-.143-.73-2.09-.832-2.335-.143-.372-.214-.487-.6-.487-.187 0-.36-.043-.53-.043-.302 0-.53.115-.746.315-.688.645-1.032 1.318-1.06 2.264v.114c-.015.99.472 1.977 1.017 2.78 1.23 1.82 2.506 3.41 4.554 4.34.616.287 2.035.888 2.722.888.817 0 2.15-.515 2.478-1.318.13-.33.244-.73.244-1.088 0-.058 0-.144-.03-.215-.1-.172-2.434-1.39-2.678-1.39zm-2.908 7.593c-1.747 0-3.48-.53-4.942-1.49L7.793 24.41l1.132-3.337a8.955 8.955 0 0 1-1.72-5.272c0-4.955 4.04-8.995 8.997-8.995S25.2 10.845 25.2 15.8c0 4.958-4.04 8.998-8.998 8.998zm0-19.798c-5.96 0-10.8 4.842-10.8 10.8 0 1.964.53 3.898 1.546 5.574L5 27.176l5.974-1.92a10.807 10.807 0 0 0 16.03-9.455c0-5.958-4.842-10.8-10.802-10.8z" fill-rule="evenodd"></path></svg>';
        aEl.setAttribute('rel', 'noopener nofollow');
        aEl.setAttribute('target', '_blank');
        aEl.setAttribute('href', waUri);
        aEl.className = clsName;``
        aEl.onclick = onClickWhatsAppLink;
        body.appendChild(aEl);
    }
};
