@php
    /*
     * The ONE layout for every Nexsuz email. Colors, logo and business name come from Nexsuz Information
     * (Email theme) through the "brand" variable (see AppServiceProvider). Table based, inline CSS, a solid
     * color under every gradient, so it renders the same in Gmail, Outlook and mobile apps.
     */
    use App\Services\EmailBrandingService as Brand;
    $t = $brand['theme']; $c = $t['colors']; $hg = $t['header_gradient']; $fg = $t['footer_gradient'];
    $font = "font-family:Arial,Helvetica,sans-serif;";
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', $brand['name'])</title>
    <style>
        a { color: {{ $c['link'] }}; }
        @media only screen and (max-width: 480px) {
            .nx-pad { padding-left: 18px !important; padding-right: 18px !important; }
            .nx-card { padding: 22px 18px !important; }
            .nx-h1 { font-size: 18px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:{{ $c['body_bg'] }};{{ $font }}">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="{{ $c['body_bg'] }}" style="background-color:{{ $c['body_bg'] }};">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;">

                <!-- header -->
                <tr>
                    <td class="nx-pad" bgcolor="{{ Brand::solid($c['header_bg'], $hg) }}" style="{{ Brand::bg($c['header_bg'], $hg) }}padding:20px 28px;border:1px solid {{ $c['border'] }};border-bottom:0;border-radius:10px 10px 0 0;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                @if($brand['logo_url'])
                                    <td width="1" style="padding-right:14px;vertical-align:middle;"><img src="{{ $brand['logo_url'] }}" alt="{{ $brand['name'] }}" style="{{ $brand['logo_style'] }}"></td>
                                @endif
                                <td style="vertical-align:middle;{{ $font }}">
                                    <div class="nx-h1" style="font-size:20px;font-weight:bold;color:{{ $c['header_text'] }};line-height:1.2;">{{ $brand['name'] }}</div>
                                    @if($brand['tagline'])<div style="font-size:12px;color:{{ $c['header_text'] }};opacity:.8;margin-top:2px;">{{ $brand['tagline'] }}</div>@endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- message -->
                <tr>
                    <td class="nx-card" bgcolor="{{ $c['card_bg'] }}" style="background-color:{{ $c['card_bg'] }};color:{{ $c['text'] }};{{ $font }}font-size:15px;line-height:1.6;padding:28px;border-left:1px solid {{ $c['border'] }};border-right:1px solid {{ $c['border'] }};border-top:3px solid {{ $c['primary'] }};">
                        @yield('content')
                    </td>
                </tr>

                <!-- footer -->
                <tr>
                    <td class="nx-pad" bgcolor="{{ Brand::solid($c['footer_bg'], $fg) }}" style="{{ Brand::bg($c['footer_bg'], $fg) }}padding:18px 28px;border:1px solid {{ $c['border'] }};border-top:1px solid {{ $c['divider'] }};border-radius:0 0 10px 10px;text-align:center;{{ $font }}font-size:12px;line-height:1.6;color:{{ $c['footer_text'] }};">
                        &copy; {{ date('Y') }} {{ $brand['name'] }} &ndash; All rights reserved.
                        @if($brand['website'] || $brand['support_email'])
                            <br>
                            @if($brand['website'])<a href="{{ $brand['website'] }}" style="color:{{ $c['footer_text'] }};text-decoration:underline;">{{ preg_replace('#^https?://#', '', rtrim($brand['website'], '/')) }}</a>@endif
                            @if($brand['website'] && $brand['support_email']) &middot; @endif
                            @if($brand['support_email'])<a href="mailto:{{ $brand['support_email'] }}" style="color:{{ $c['footer_text'] }};text-decoration:underline;">{{ $brand['support_email'] }}</a>@endif
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
