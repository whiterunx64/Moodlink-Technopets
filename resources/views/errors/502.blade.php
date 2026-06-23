@php
    use App\Exceptions\InfrastructureException;

    $infra     = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $code      = $infra?->errorCode;
    $status    = 502;
    $retryable = $infra?->isRetryable() ?? true;
@endphp

@extends('errors.layout')

@section('title', 'Network problem')

@section('summary', $infra?->getMessage()
    ?: 'We couldn’t reach a service the application depends on.')

@section('detail', 'A request to an upstream service — such as Supabase Auth or Storage, or another external dependency — didn’t complete in time or was refused. This is typically a transient connectivity issue between our servers and that service. Check that your connection is stable and try again in a few seconds; if it persists, contact support with the reference below.')
