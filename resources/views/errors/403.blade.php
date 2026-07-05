@php
    $status    = 403;
    $retryable = false;
@endphp

@extends('errors.layout')

@section('title', 'You do not have access to this')

@section('summary', 'Your account is not allowed to open this page')

@section('detail', 'This area is limited to certain staff roles If you believe you should have access ask an administrator to review your account permissions')

@section('reasons')
    <li>Your role does not include this part of the system</li>
    <li>Your access was recently changed by an administrator</li>
    <li>You opened a page meant for a different type of account</li>
@endsection
