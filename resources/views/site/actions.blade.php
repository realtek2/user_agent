@extends('layouts.new')

@section('content')
    @include('site.navbar')

    @include('site.header')
    <section>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <h2>Действия на сайте {{ $site->url }}</h2>
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Действие</th>
                            <th scope="col">Клиент</th>
                            <th scope="col">Реферер</th>
                            <th scope="col">Данные</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($actions as $action)
                            <tr>
                                <th scope="row">{{ $action->created_at->format('d.m.Y H:j') }}</th>
                                <td>{{ $action->action }}</td>
                                <td><a href="/client/{{ $action->client->id }}">{{ $action->client->id }}</a></td>
                                <td>{{ $action->referer }}</td>
                                <td>
                                    @php $data = json_decode($action->data); @endphp
                                    @foreach($data as $key=>$d)
                                        {{ $key }} : {{ $d }}<br>
                                    @endforeach
                                </td>

                            </tr>
                        @endforeach

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </section>
@endsection