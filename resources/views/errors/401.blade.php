@php
    $status    = 401;
    $retryable = false;
@endphp

@extends('errors.layout')

@section('title', 'Please sign in to continue')

@section('summary', 'You need to be signed in to view this page')

@section('detail', 'Your session may have ended or you opened a page that requires signing in first Sign in again to continue and you will not lose any of your work')

@section('reasons')
    <li>You were signed out after a period of inactivity</li>
    <li>You opened a saved link without signing in first</li>
    <li>Your account was signed in on another device</li>
@endsection
