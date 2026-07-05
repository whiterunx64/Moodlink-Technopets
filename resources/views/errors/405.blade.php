@php
    $status    = 405;
    $retryable = false;
@endphp

@extends('errors.layout')

@section('title', 'That action is not available here')

@section('summary', 'This page cannot handle the action you tried to perform')

@section('detail', 'This usually happens after using the browser back or refresh button on a form Return to the dashboard and start the action again from the beginning')

@section('reasons')
    <li>The back or refresh button was used on a submitted form</li>
    <li>An out of date link tried to send data the wrong way</li>
    <li>The page was left open too long before the action ran</li>
@endsection
