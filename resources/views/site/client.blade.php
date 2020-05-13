@extends('layouts.new')

@section('content')
    @include('site.navbar')

    @include('site.header')
    <section>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col">
                    <h2>Клиент {{ $client->id }}</h2>
                    @if($client->name)
                    <p>{{ $client->name }}</p>
                    @endif
                    @if($client->phone)
                        <p>{{ $client->phone }}</p>
                    @endif
                    @if($client->email)
                        <p>{{ $client->email }}</p>
                    @endif
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">Сайт</th>
                            <th scope="col">Количество действий</th>

                        </tr>
                        </thead>
                        <tbody>
                        @foreach($sites as $site)
                            <tr>
                                <th scope="row">{{ $site->url }}</th>
                                <td>{{ $counts[$site->id] }}</td>


                            </tr>
                        @endforeach

                        </tbody>
                    </table>

                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Действие</th>
                            <th scope="col">Сайт</th>

                            <th scope="col">Данные</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($actions as $action)
                            <tr>
                                <th scope="row">{{ $action->created_at->format('d.m.Y H:j') }}</th>
                                <td>{{ $action->action }}</td>

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