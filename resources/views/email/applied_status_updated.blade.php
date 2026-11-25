@extends('email.layouts.branded')

@section('title', 'Application Status Update')

@section('content')
    <h3 style="margin:0 0 10px;font-size:18px;color:{{ $brand['theme']['colors']['text'] }};">Dear {{ $fullname }},</h3>

    <p style="margin:0 0 12px;">Your application for the position <strong>{{ $jobName }}</strong> has been updated.</p>

    @php
        $s = strtolower($status);
        // status colors stay semantic (amber / green / red) in every theme
        $badge = $s == 'review' ? '#f0ad4e' : ($s == 'approved' ? '#28a745' : ($s == 'reject' ? '#dc3545' : $brand['theme']['colors']['primary']));
    @endphp
    <p style="margin:0 0 12px;">
        Current Status:
        <span style="display:inline-block;padding:8px 15px;border-radius:4px;font-weight:bold;color:#ffffff;font-size:14px;text-align:center;background-color:{{ $badge }};">
            {{ ucfirst($status) }}
        </span>
    </p>

    <p style="margin:0 0 12px;">
        @if($s == 'review')
            Your application is currently under review. We will notify you once the evaluation is complete.
        @elseif($s == 'approved')
            Congratulations! Your application has been approved. Our team will contact you with the next steps.
        @elseif($s == 'reject')
            We regret to inform you that your application has not been successful. We encourage you to apply for future opportunities.
        @else
            Your application status has been updated to <strong>{{ $status }}</strong>.
        @endif
    </p>
@endsection
