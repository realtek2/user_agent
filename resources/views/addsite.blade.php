@extends('layouts.site')


@section('content')

    @include('nav')
    <div class="container">
        <div class="row">
            <div class="col">
                <!--
                 <a href="/site/add" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Добавить сайт</a>
                 -->
                <form method="post" action="/savesite">
                    @csrf
                    <div class="form-group">
                        <label for="exampleInputEmail1">Site URL</label>
                        <input type="text" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Enter URL" name="url">
                        <small id="emailHelp" class="form-text text-muted">Введите URL сайта.</small>
                    </div>


                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>

    @include('footer')
@endsection