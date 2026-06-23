@php
    use App\Exceptions\InfrastructureException;

    $infra     = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $code      = $infra?->errorCode;
    $status    = 500;
    $retryable = true;
@endphp

@extends('errors.layout')

@section('title', 'Something went wrong')

{{-- Only surface our own (user-safe) message; never leak details from a real bug. --}}
@section('summary', $infra?->getMessage()
    ?: 'The application ran into an unexpected problem while handling your request.')

@section('detail', 'Our team has been notified automatically and is looking into it. Your data is safe. In most cases, retrying the action resolves it — if it keeps failing, reload the page or contact support with the reference below.')
