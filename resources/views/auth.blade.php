@extends('layouts.site')


@section('content')

    <div class="position-relative overflow-hidden p-3 p-md-5 m-md-3 text-center bg-light">
        <div class="col-md-5 p-lg-5 mx-auto my-5">
            <h1 class="display-4 font-weight-normal">Идентифицируйте всех своиих клиентов</h1>
            <h1 class="display-4 font-weight-normal">Detect Your Clients on all Your Sites</h1>
            <p class="lead font-weight-normal">Write on telegram @useragentidbot.</p>
            <form action="/tglogin" method="post">
                @csrf
                <input type="text" name="code">
                <button class="btn btn-outline-secondary" href="#">Paste code</button>
            </form>

        </div>
        <div class="product-device shadow-sm d-none d-md-block"></div>
        <div class="product-device product-device-2 shadow-sm d-none d-md-block"></div>
    </div>



    @include('footer')
@endsection