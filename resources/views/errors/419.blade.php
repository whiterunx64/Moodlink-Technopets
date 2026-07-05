@php
    $status    = 419;
    $retryable = true;
@endphp

@extends('errors.layout')

@section('title', 'Your session timed out')

@section('summary', 'The page sat idle too long so we paused it to keep your account safe')

@section('detail', 'For your security forms expire after a period of inactivity Nothing is wrong Just try again and if you were filling something in you may need to enter it once more')

@section('reasons')
    <li>The page was left open longer than the security limit allows</li>
    <li>You signed in again in another tab or window</li>
    <li>The saved security token expired before the form was sent</li>
@endsection
