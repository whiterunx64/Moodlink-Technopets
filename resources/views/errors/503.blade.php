@php
    use App\Exceptions\InfrastructureException;

    $infra     = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $errorCode = $infra?->errorCode;
    $code      = $errorCode;
    $status    = 503;
    $retryable = $infra?->isRetryable() ?? true;

    $lostConnection = $errorCode === InfrastructureException::CODE_DB_CONNECTION_LOST;
@endphp

@extends('errors.layout')

@section('title', $lostConnection ? 'Connection interrupted' : 'Database unavailable')

@section('summary', $infra?->getMessage()
    ?: ($lostConnection
        ? 'The connection to the database was interrupted while we were loading your data.'
        : 'We can’t reach the database right now.'))

@section('detail', $lostConnection
    ? 'A live connection to the database dropped mid-request — often because the Supabase connection pooler recycled the link or a brief network hiccup occurred. Nothing was lost; reloading establishes a fresh connection. If the problem continues, contact support with the reference below.'
    : 'The Supabase Postgres instance isn’t responding. This usually happens when the project is waking from idle, the connection pool is briefly saturated, or there’s a short-lived network interruption. It typically resolves on its own within a few seconds — wait a moment and try again.')
