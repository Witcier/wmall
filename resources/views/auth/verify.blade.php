@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('验证你的邮箱') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('验证链接已经发到你的邮箱了') }}
                        </div>
                    @endif

                    {{ __('在此之前, 请验证的你邮箱') }}
                    {{ __('如果你没有收到验证邮件') }},
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('点击发送') }}</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
