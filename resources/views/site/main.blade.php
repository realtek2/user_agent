@extends('layouts.new')

@section('content')
    <div class="navbar-container ">
        <nav class="navbar navbar-expand-lg justify-content-between navbar-light border-bottom-0 bg-white" data-sticky="top">
            <div class="container">
                <div class="col flex-fill px-0 d-flex justify-content-between">
                    <a class="navbar-brand mr-0 fade-page" href="{!! route('main') !!}">
                        <img src="assets/img/logo.svg" alt="Leap">
                    </a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".navbar-collapse" aria-expanded="false" aria-label="Toggle navigation">
                        <svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 17C3 17.5523 3.44772 18 4 18H20C20.5523 18 21 17.5523 21 17V17C21 16.4477 20.5523 16 20 16H4C3.44772 16 3 16.4477 3 17V17ZM3 12C3 12.5523 3.44772 13 4 13H20C20.5523 13 21 12.5523 21 12V12C21 11.4477 20.5523 11 20 11H4C3.44772 11 3 11.4477 3 12V12ZM4 6C3.44772 6 3 6.44772 3 7V7C3 7.55228 3.44772 8 4 8H20C20.5523 8 21 7.55228 21 7V7C21 6.44772 20.5523 6 20 6H4Z"
                                  fill="#212529" />
                        </svg>

                    </button>
                </div>
                <div class="collapse navbar-collapse justify-content-end col flex-fill px-0"><a href="tg://resolve?domain=uaidbot{{ $referralPath }}"  class="btn btn-primary ml-lg-3">Получить пароль</a>
                </div>
            </div>
        </nav>
    </div>
    <section class="has-divider">
        <div class="container">
            <div class="row align-items-center justify-content-between o-hidden">
                <div class="col-md-6 order-sm-2 mb-5 mb-sm-0" data-aos="fade-left">
                    <img src="assets/img/saas-3.svg" alt="Image">
                </div>
                <div class="col-md-6 pr-xl-5 order-sm-1">
                    <h1 class="display-4">User-Agent.cc</h1>
                    <p class="lead"><span data-typed-text="" data-loop="true" data-type-speed="65" data-strings="[ &quot;&quot;,&quot;Оповещает обо всех посещениях на сайте в телеграмм&quot;,&quot;Оповещает о начале ввода данных в формы захавата&quot;, &quot;Присылает заявки с форм захвата в телеграмм&quot;,&quot;Объединяет посетителей всех сайтов подключенных к сервису в единое облако клиентов&quot;]"></span></p>
                    <span class="text-small text-muted">
                        Для получения логин-пароля напишите в телеграмм-бот <a href="tg://resolve?domain=uaidbot{{ $referralPath }}" target="_blank">uaidbot</a>
                    </span>
                    <form class="d-sm-flex mb-2 mt-4" action="/tglogin" method="post">
                        @csrf
                        <input type="text" class="form-control form-control-lg mr-sm-2 mb-2 mb-sm-0" placeholder="Введите ваш пароль от бота"  name="code" required="required">
                        <button class="btn btn-lg btn-primary" type="submit">Войти</button>
                    </form>
                    <span class="text-small text-muted">
                        В следующий раз заходите используя этот же ключ-пароль. <br> Для смены пароля зайдите в бот и снова нажмите /start.
                    </span>
                </div>
            </div>
        </div>
    </section>
    <section class="p-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7 col-md-8 mb-lg-n7 layer-2">
                    <img src="assets/img/saas-1.svg" alt="Image" data-aos="fade-up">
                </div>
            </div>
        </div>
    </section>
    <section class="bg-primary text-light has-divider">
        <div class="divider flip-y">
            <svg width="100%" height="100%" version="1.1" viewbox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="none">
                <path d="M0,0 C6.83050094,50 15.1638343,75 25,75 C41.4957514,75 62.4956597,0 81.2456597,0 C93.7456597,0 99.9971065,0 100,0 L100,100 L0,100" fill="#ffffff"></path>
            </svg>
        </div>
        <div class="container">
            <div class="row justify-content-center mb-0 mb-md-3">
                <div class="col-xl-6 col-lg-8 col-md-10 text-center">
                    <h3 class="h3">Напишите в telegramm bot = @uaidbot</h3>
                </div>
            </div>
            <div class="row justify-content-center text-center">
                <div class="col-xl-6 col-lg-7 col-md-9">
                    <form class="d-md-flex mb-3 justify-content-center" action="/tglogin" method="post">
                        @csrf
                        <input type="text" class="mx-1 mb-2 mb-md-0 form-control form-control-lg" placeholder="dskIUhhhuk5" name="code" required="required">
                        <button class="mx-1 btn btn-primary-3 btn-lg" type="submit">Войти</button>
                    </form>
                    <div class="text-small text-muted mx-xl-6">
                        Для получения уникального ключа авторизации!
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
