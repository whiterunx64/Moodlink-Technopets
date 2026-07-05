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

@section('title', $lostConnection ? 'The connection was interrupted' : 'The database is unavailable')

@section('summary', $infra?->getMessage()
    ?: ($lostConnection
        ? 'The connection to the database was interrupted while loading your data'
        : 'We cannot reach the database right now'))

@section('detail', $lostConnection
    ? 'A live connection to the database dropped mid request often because the Supabase connection pooler recycled the link or a brief network hiccup occurred Nothing was lost and reloading opens a fresh connection'
    : 'The Supabase Postgres instance is not responding This usually happens when the project is waking from idle the connection pool is briefly saturated or there is a short network interruption It normally clears on its own within a few seconds')

@section('reasons')
    @if($lostConnection)
        <li>The database connection pooler recycled the open link</li>
        <li>A brief network hiccup dropped the live connection</li>
        <li>The request arrived just as the connection was refreshing</li>
    @else
        <li>The database project is waking up from idle</li>
        <li>The connection pool is briefly saturated with traffic</li>
        <li>A short network interruption reached the database</li>
    @endif
@endsection
