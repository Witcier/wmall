<?php

use Illuminate\Support\Facades\Route;

function test_helper()
{
    return 'ok';
}

function route_class()
{
    return str_replace('.','-',Route::currentRouteName());
}

function ngrok_url($routeName)
{

}