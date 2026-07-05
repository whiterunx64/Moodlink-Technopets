@php
    $status  = 419;
    $retry   = true;
    $heading = 'Your session timed out';
    $body    = 'The page sat idle for too long so we <b>paused it to keep your account safe</b> For your security forms expire after a period of inactivity';
    $more    = 'Nothing is wrong Just <u>try again</u> and if you were filling something in you may need to enter it once more';
@endphp

@extends('errors.layout')
