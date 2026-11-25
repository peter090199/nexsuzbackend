@extends('email.layouts.branded')

@section('title', 'Password Reset Request')

@section('content')
    <!-- Greeting -->
    <h4 style="font-size:22px;margin:0 0 20px;color:{{ $brand['theme']['colors']['text'] }};">Hi {{ $data['fname'] }},</h4>

    <!-- Message -->
    <p style="margin:0 0 20px;">
        We’ve received a request to reset the password for your Nexsuz account associated with <strong>{{ $data['email'] }}</strong>.
    </p>
    <p style="margin:0 0 20px;">No changes have been made to your account yet.</p>
    <p style="margin:0 0 20px;">You can reset your password by clicking the button below:</p>

    <!-- Reset Password Button -->
    @include('email.partials.button', ['url' => rtrim(config('app.frontend_url'), '/') . '/reset-password/' . urlencode($data['email']) . '/' . $data['token'], 'label' => 'Reset Password'])

    <!-- Support Message -->
    <p style="margin:0 0 20px;">
        You can find answers to most questions and get in touch with us at <a href="https://support.nexsuz.com" style="color:{{ $brand['theme']['colors']['link'] }};text-decoration:none;">support.nexsuz.com</a>, we're happy to assist you.
    </p>

    <!-- Nexsuz Team -->
    <p style="margin:0;">-The Nexsuz team.</p>
@endsection
