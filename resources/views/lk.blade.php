@extends('layouts.site')


@section('content')

   @include('nav')
   <div class="container">
       <div class="row">
           <div class="col">
               <!--
                <a href="/site/add" class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Добавить сайт</a>
                -->
               <table class="table">
                   <thead>
                   <tr>
                       <th scope="col">#</th>
                       <th scope="col">URL</th>
                       <th scope="col">Статус</th>
                       <th scope="col">Код для сайта</th>
                       <th scope="col">Action</th>
                   </tr>
                   </thead>
                   <tbody>
                   @foreach(Auth::user()->sites as $site)
                   <tr>
                       <th scope="row">{{ $site->id }}</th>
                       <td>{{ $site->url }}</td>
                       <td>{{ $status[$site->id] }}</td>
                       <td><textarea readonly><script src="https://user-agent.cc/cdn/fpinit.js"></script><script> FpInit('{{ $site->id }}_{{ $site->code }}') </script></textarea></td>
                       <td>
                           <a href="/deletesite/{{ $site->id }}"><i class="far fa-times-circle"></i></a>
                           <a href="/showactions/{{ $site->id }}"><i class="far fa-eye"></i></a>
                       </td>

                   </tr>
                   @endforeach

                   </tbody>
               </table>
               <p>Код для сайта необходимо вставить перед закрывающим тегом body</p>
           </div>
       </div>
   </div>

    @include('footer')
@endsection