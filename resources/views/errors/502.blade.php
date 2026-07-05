@php
    use App\Exceptions\InfrastructureException;

    $infra   = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $status  = 502;
    $retry   = true;
    $code    = $infra?->errorCode ?? 'NETWORK_FAILURE';
    $heading = 'We could not reach a needed service';

    $body = 'A service the app depends on such as <b>Supabase Auth Storage or an external API</b> did not respond in time or refused the connection This is a network or upstream problem between our server and that service not a bug in the page itself';

    $more = $infra !== null
        ? e($infra->getMessage())
        : 'This is not automatically reported so tell the system maintainer with the code below The maintainer should check whether Supabase is up the service keys and URLs are correct and the server has outbound network access at the time shown';
@endphp

@extends('errors.layout')
