@php
    $status  = 401;
    $retry   = false;
    $heading = 'Please sign in to continue';
    $body    = 'You need to be <b>signed in</b> to view this page Your session may have ended or you opened a page that requires signing in first';
    $more    = 'Sign in again to pick up where you left off You will <u>not lose any of your work</u>';
@endphp

@extends('errors.layout')
