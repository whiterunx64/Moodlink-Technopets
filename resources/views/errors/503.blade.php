@php
    use App\Exceptions\InfrastructureException;

    $infra          = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $status         = 503;
    $retry          = true;
    $code           = $infra?->errorCode ?? 'DB_UNAVAILABLE';
    $lostConnection = $code === InfrastructureException::CODE_DB_CONNECTION_LOST;

    $heading = $lostConnection ? 'The database connection was interrupted' : 'The database is temporarily unavailable';

    // Name the database subsystem clearly so the maintainer starts there.
    $body = $lostConnection
        ? 'A live <b>database connection dropped mid request</b> This usually means the Supabase connection pooler recycled the link or a brief network hiccup occurred Nothing was lost and a fresh connection is opened on retry'
        : 'The app <b>could not reach the Supabase Postgres database</b> This commonly happens when the database is waking from idle the connection pool is saturated or the database credentials or host are wrong';

    $more = $infra !== null
        ? e($infra->getMessage())
        : 'This is not automatically reported so tell the system maintainer with the code below The maintainer should check that the Supabase project is running the DB connection settings are correct and the pooler is healthy around the time shown';
@endphp

@extends('errors.layout')
