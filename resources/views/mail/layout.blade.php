<!doctype html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Flower Flow</title>
</head>
<body style="margin:0;padding:0;background:#f3f7f4;color:#17352f;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">@yield('preheader')</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f7f4;">
        <tr>
            <td align="center" style="padding:28px 12px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 30px rgba(23,53,47,.08);">
                    <tr>
                        <td style="padding:24px 28px;background:#fffdf5;border-bottom:4px solid #d9ed55;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="50%" align="left" valign="middle">
                                        <img src="{{ rtrim(config('flowerflow.canonical_url'), '/') }}/assets/flowerflow/logo_flowerflow_transparente.png" width="118" alt="Flower Flow" style="display:block;max-width:118px;height:auto;border:0;">
                                    </td>
                                    <td width="50%" align="right" valign="middle">
                                        <img src="{{ rtrim(config('flowerflow.canonical_url'), '/') }}/assets/flowerflow/logo_florecehermosillo_transparente.png" width="112" alt="Florece Hermosillo" style="display:inline-block;max-width:112px;height:auto;border:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 30px;line-height:1.6;font-size:16px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;background:#17352f;color:#dcebe5;font-size:13px;line-height:1.6;">
                            <strong style="color:#ffffff;">Hermosillo Florece 2026</strong><br>
                            Este es un correo transaccional de Flower Flow.<br>
                            Para aclaraciones escribe a <a href="mailto:{{ config('flowerflow.mail.reply_to') }}" style="color:#d9ed55;">{{ config('flowerflow.mail.reply_to') }}</a>.<br>
                            No envíes documentos o información sensible como respuesta a este mensaje.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
