@php
    use App\Exceptions\InfrastructureException;

    $infra     = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $code      = $infra?->errorCode;
    $status    = 500;
    $retryable = true;
@endphp

@extends('errors.layout')

@section('title', 'Something went wrong on our end')

{{-- Only surface our own user safe message never leak details from a real bug --}}
@section('summary', $infra?->getMessage()
    ?: 'The application ran into an unexpected problem while handling your request')

@section('detail', 'Our team is notified automatically and is looking into it Your data is safe In most cases trying again resolves it and if it keeps failing you can reload the page or contact support with the reference below')

@section('reasons')
    <li>A temporary glitch happened while processing your request</li>
    <li>A background service returned an unexpected result</li>
    <li>The issue is on our side and not caused by anything you did</li>
@endsection
