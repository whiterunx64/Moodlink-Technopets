@php
    use App\Exceptions\InfrastructureException;

    $infra   = isset($exception) && $exception instanceof InfrastructureException ? $exception : null;
    $status  = 500;
    $retry   = true;
    $code    = $infra?->errorCode ?? 'APP_ERROR';
    $heading = 'Something went wrong on our end';

    // Describe the failure so the maintainer knows where to look when reported.
    $body = match ($code) {
        InfrastructureException::CODE_UNEXPECTED_STATE =>
            'The application reached an <b>unexpected internal state</b> and could not finish your request This points to a bug or bad data in the app logic rather than the database or network',
        default =>
            'The application hit an <b>unexpected error</b> while building this page This is a problem in the app itself not something you did wrong',
    };

    $more = $infra !== null
        ? e($infra->getMessage())
        : 'This is not automatically reported so please tell the system maintainer using the code below The maintainer should check the application logs on the server for the full stack trace around the time shown';
@endphp

@extends('errors.layout')
