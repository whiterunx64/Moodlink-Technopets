@php
    $status  = 405;
    $retry   = false;
    $heading = 'That action is not available here';
    $body    = 'This page <b>cannot handle</b> the action you tried to perform This often happens after using the back or refresh button on a form you already submitted';
    $more    = 'Return to your dashboard and <u>start the action again</u> from the beginning';
@endphp

@extends('errors.layout')
