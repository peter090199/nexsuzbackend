@extends('email.layouts.branded')

@section('title', $subjectLine)

@section('content')
    {{-- the reply body was cleaned by HtmlSanitizer before it was stored --}}
    <div>{!! $bodyHtml !!}</div>
@endsection
