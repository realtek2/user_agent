@extends('layouts.site')


@section('content')

    @include('nav')
    <div class="container">
        <div class="row">
            <div class="col">
                <h3>Клиент {{ $client->id }}</h3>
                <h4>Сайты</h4>
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
                <!--
                 <a href="/site/add" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Добавить сайт</a>
                 -->
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

    @include('footer')
@endsection