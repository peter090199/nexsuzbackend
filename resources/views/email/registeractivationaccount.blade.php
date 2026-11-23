<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Activate Your Account</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; border-radius: 8px; border: 1px solid #ddd;">

        <!-- Nexsuz Logo -->
        <div style="margin-bottom: 5px;">
            <img src="https://exploredition.com/public/nexsuzlogo.jpg" alt="Nexsuz Logo" style="max-width: 50px; height: 25px;">
        </div>
    
        <!-- Greeting -->
        <h4 style="font-size: 24px; color: #333; margin-bottom: 20px;">
            Hi {{ $data['fname'] }},
        </h4>
        
        <!-- Introduction Message -->
        <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 20px;">
            Thank you for registering with Nexsuz. To activate your account, please click the button below:
        </p>
    
        <p style="font-size: 16px; color: #333; line-height: 1.6; margin-bottom: 20px;">
            Your activation code: <strong>{{ $data['code'] }}</strong>
        </p>
        
        @php
            // Built from FRONTEND_URL so it works on every environment (the link used to be hard-coded to nexsuz.com).
            // The email is URL-encoded (an address like a+b@x.com would otherwise break the link) and the code is
            // added so the activation page opens with the code already filled in.
            $activationUrl = rtrim(config('app.frontend_url'), '/') . '/activation/' . rawurlencode($data['email']) . '?code=' . rawurlencode($data['code']);
        @endphp

        <!-- Activation Button (table based so it renders as a real button in Outlook / Gmail / mobile) -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto 20px;">
            <tr>
                <td align="center" bgcolor="#28a745" style="border-radius: 5px;">
                    <a href="{{ $activationUrl }}" target="_blank" rel="noopener"
                       style="display: inline-block; padding: 12px 24px; font-family: Arial, sans-serif; font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 5px; background-color: #28a745; border: 1px solid #28a745;">
                        Activate My Account
                    </a>
                </td>
            </tr>
        </table>

        <!-- Some mail apps block or rewrite buttons, so the plain link is always there too -->
        <p style="font-size: 13px; color: #555; line-height: 1.5; margin-bottom: 20px; word-break: break-all;">
            If the button does not work, copy this link into your browser:<br>
            <a href="{{ $activationUrl }}" style="color: #007bff;">{{ $activationUrl }}</a><br>
            or open the activation page and type the code above.
        </p>
    
        <!-- Support Message -->
        <p style="font-size: 16px; color: #333; margin-bottom: 20px;">
            You can find answers to most questions and get in touch with us at <a href="https://support.nexsuz.com" style="color: #007bff; text-decoration: none;">support.nexsuz.com</a>, we're happy to assist you.
            -The Nexsuz team.
            
        </p>
    
    </div>
    
</body>
</html>
