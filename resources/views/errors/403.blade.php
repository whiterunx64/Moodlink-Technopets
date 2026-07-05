@php
    $status  = 403;
    $retry   = false;
    $heading = 'You do not have access to this page';
    $body    = 'Your account is <b>not allowed</b> to open this page This part of the system is restricted to specific staff roles and yours does not include it';
    $more    = 'If you believe this is a mistake ask an <u>administrator</u> to review your account permissions Otherwise you can head back to your dashboard and continue your work there';
@endphp

@extends('errors.layout')
