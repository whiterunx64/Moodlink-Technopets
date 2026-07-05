@php
    $status    = 404;
    $retryable = false;
@endphp

@extends('errors.layout')

@section('title', 'We could not find that page')

@section('summary', 'The page you were looking for does not exist here')

@section('detail', 'The link may be out of date or the page may have been moved or removed You can head back to the dashboard and continue from there')

@section('reasons')
    <li>The web address was mistyped or copied incorrectly</li>
    <li>A bookmark or old link points to a page that no longer exists</li>
    <li>The item you opened was deleted or renamed by another admin</li>
@endsection
