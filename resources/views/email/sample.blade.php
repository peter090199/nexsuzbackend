@extends('email.layouts.branded')

@section('title', 'Email preview')

@section('content')
    @php $c = $brand['theme']['colors']; @endphp
    <h3 style="margin:0 0 10px;font-size:18px;color:{{ $c['text'] }};">Hi Alex,</h3>
    <p style="margin:0 0 12px;">This is how a {{ $brand['name'] }} notification looks with your email theme. Your application for <strong>Senior Designer</strong> has been updated.</p>

    <p style="margin:0 0 16px;">
        Current Status:
        <span style="display:inline-block;padding:6px 14px;border-radius:4px;font-weight:bold;color:#ffffff;font-size:13px;background-color:#28a745;">Approved</span>
    </p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 18px;border:1px solid {{ $c['divider'] }};border-radius:6px;">
        <tr><td style="padding:10px 14px;">Interview<br><strong style="color:{{ $c['primary'] }};">Tuesday, 10:00 AM</strong></td>
            <td style="padding:10px 14px;border-left:1px solid {{ $c['divider'] }};">Location<br><strong style="color:{{ $c['secondary'] }};">Online (Google Meet)</strong></td></tr>
    </table>

    @include('email.partials.button', ['url' => 'https://example.com/action', 'label' => 'View application'])

    <hr style="border:0;border-top:1px solid {{ $c['divider'] }};margin:0 0 14px;">
    <p style="margin:0;font-size:13px;">Questions? Visit <a href="https://example.com/help" style="color:{{ $c['link'] }};">our help center</a> or reply to this email.</p>
@endsection
