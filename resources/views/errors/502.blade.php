@php
    use App\Exceptions\InfrastructureException;

    $infra     = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $code      = $infra?->errorCode;
    $status    = 502;
    $retryable = $infra?->isRetryable() ?? true;
@endphp

@extends('errors.layout')

@section('title', 'We could not reach a needed service')

@section('summary', $infra?->getMessage()
    ?: 'A service the application depends on did not respond')

@section('detail', 'A request to an upstream service such as Supabase Auth or Storage did not complete in time or was refused This is usually a short lived connection issue between our servers and that service Check that your connection is stable and try again in a few seconds')

@section('reasons')
    <li>A brief network interruption between our servers and the service</li>
    <li>The upstream service was busy or restarting</li>
    <li>A short timeout while waiting for a response</li>
@endsection
