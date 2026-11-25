@extends('email.layouts.branded')

@section('title', 'New website inquiry')

@section('content')
    @php $c = $brand['theme']['colors']; @endphp
    <p style="margin:0 0 16px;">New message from the <strong>{{ $business }}</strong> website contact form.</p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 16px;border:1px solid {{ $c['divider'] }};border-radius:6px;">
        <tr><td style="padding:8px 12px;width:80px;color:{{ $c['text'] }};opacity:.7;">From</td><td style="padding:8px 12px;">{{ $visitorName }} &lt;<a href="mailto:{{ $visitorEmail }}" style="color:{{ $c['link'] }};">{{ $visitorEmail }}</a>&gt;</td></tr>
        <tr><td style="padding:8px 12px;border-top:1px solid {{ $c['divider'] }};opacity:.7;">Phone</td><td style="padding:8px 12px;border-top:1px solid {{ $c['divider'] }};">{{ $visitorPhone ?: '-' }}</td></tr>
        <tr><td style="padding:8px 12px;border-top:1px solid {{ $c['divider'] }};opacity:.7;">Subject</td><td style="padding:8px 12px;border-top:1px solid {{ $c['divider'] }};">{{ $subjectLine }}</td></tr>
    </table>

    <div style="white-space:pre-wrap;margin:0 0 20px;padding:14px;border-left:3px solid {{ $c['primary'] }};border-top:0;">{{ $body }}</div>

    <p style="margin:0;font-size:13px;opacity:.85;">Reply to this email to answer the visitor directly.</p>
@endsection
