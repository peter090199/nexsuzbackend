@extends('email.layouts.branded')

@section('title', 'Activate Your Account')

@section('content')
    @php
        // Built from FRONTEND_URL so it works on every environment (the link used to be hard-coded to nexsuz.com).
        // The email is URL-encoded (an address like a+b@x.com would otherwise break the link) and the code is
        // added so the activation page opens with the code already filled in.
        $activationUrl = rtrim(config('app.frontend_url'), '/') . '/activation/' . rawurlencode($data['email']) . '?code=' . rawurlencode($data['code']);
        $link = $brand['theme']['colors']['link'];
    @endphp

    <!-- Greeting -->
    <h4 style="font-size:22px;margin:0 0 20px;color:{{ $brand['theme']['colors']['text'] }};">Hi {{ $data['fname'] }},</h4>

    <!-- Introduction Message -->
    <p style="margin:0 0 20px;">Thank you for registering with Nexsuz. To activate your account, please click the button below:</p>

    <p style="margin:0 0 20px;">Your activation code: <strong>{{ $data['code'] }}</strong></p>

    <!-- Activation Button (table based so it renders as a real button in Outlook / Gmail / mobile) -->
    @include('email.partials.button', ['url' => $activationUrl, 'label' => 'Activate My Account'])

    <!-- Some mail apps block or rewrite buttons, so the plain link is always there too -->
    <p style="font-size:13px;line-height:1.5;margin:0 0 20px;word-break:break-all;opacity:.85;">
        If the button does not work, copy this link into your browser:<br>
        <a href="{{ $activationUrl }}" style="color:{{ $link }};">{{ $activationUrl }}</a><br>
        or open the activation page and type the code above.
    </p>

    <!-- Support Message -->
    <p style="margin:0;">
        You can find answers to most questions and get in touch with us at <a href="https://support.nexsuz.com" style="color:{{ $link }};text-decoration:none;">support.nexsuz.com</a>, we're happy to assist you.
        -The Nexsuz team.
    </p>
@endsection
