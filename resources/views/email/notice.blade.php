@extends('email.layouts.branded')

@section('title', $subject ?? 'Notification')

@section('content')
    {{-- plain-text notifications (application status, interview invitations...) keep their exact wording and line breaks --}}
    <div>{!! nl2br(e($body)) !!}</div>
@endsection
