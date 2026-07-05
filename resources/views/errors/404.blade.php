@php
    $status  = 404;
    $retry   = false;
    $heading = 'This page does not exist';
    $body    = 'The page you were looking for <b>could not be found</b> It may have been moved renamed or removed or the web address might have been typed incorrectly';
    $more    = 'Double check the link or head back to your <u>dashboard</u> and find what you need from there';
@endphp

@extends('errors.layout')
