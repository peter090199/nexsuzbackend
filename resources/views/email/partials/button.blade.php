{{-- Bulletproof button. Usage: @include('email.partials.button', ['url' => $url, 'label' => 'Do it']) --}}
@php $bc = $brand['theme']['colors']; @endphp
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 20px;">
    <tr>
        <td align="center" bgcolor="{{ $bc['button_bg'] }}" style="border-radius:6px;">
            <a href="{{ $url }}" target="_blank" rel="noopener"
               style="display:inline-block;padding:12px 26px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;color:{{ $bc['button_text'] }};text-decoration:none;border-radius:6px;background-color:{{ $bc['button_bg'] }};border:1px solid {{ $bc['button_bg'] }};">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
