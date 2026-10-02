<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Reset your LSPDMS password</title>
    <style>
        @media only screen and (max-width: 520px) {
            .container { width: 100% !important; }
            .padding { padding: 1.5rem 1.25rem !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:3rem 1rem;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" class="container" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;background:#fff;border:1px solid #e6e9ef;border-radius:16px;">
                    <tr>
                        <td class="padding" style="padding:2rem;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="44" height="44" align="center" valign="middle" style="width:44px;height:44px;background:#e8f0ff;border-radius:22px;color:#0866ff;font-size:20px;font-weight:700;line-height:44px;text-align:center;">&#128273;</td>
                                </tr>
                            </table>

                            <h1 style="margin:1.25rem 0 .45rem;color:#0f172a;font-size:1.35rem;line-height:1.4;font-weight:700;letter-spacing:-.02em;">Reset your password</h1>
                            <p style="margin:0 0 1.5rem;color:#5b6472;font-size:.9rem;line-height:1.7;">
                                We received a request to reset the password for your LSPDMS account. Use the button below to choose a new password.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 1.5rem;">
                                <tr>
                                    <td align="center" style="border-radius:9px;background:#0866ff;">
                                        <a href="{{ $url }}" style="display:inline-block;padding:.85rem 1.3rem;border:1px solid #0866ff;border-radius:9px;color:#fff;font-size:.95rem;font-weight:700;line-height:1.2;text-decoration:none;">Choose a new password</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;color:#5b6472;font-size:.82rem;line-height:1.7;">
                                This secure link expires in <strong style="color:#0f172a;">{{ $expiresIn }} minutes</strong> and can only be used once.
                            </p>
                            <p style="margin:1.1rem 0 0;color:#5b6472;font-size:.82rem;line-height:1.7;">
                                If the button does not work, copy and paste this link into your browser:
                            </p>
                            <p style="margin:.45rem 0 0;overflow-wrap:anywhere;color:#0866ff;font-size:.78rem;line-height:1.6;">
                                <a href="{{ $url }}" style="color:#0866ff;overflow-wrap:anywhere;">{{ $url }}</a>
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:1.4rem;">
                                <tr><td style="border-top:1px solid #e6e9ef;padding-top:1rem;color:#5b6472;font-size:.8rem;line-height:1.65;">
                                    If you did not request a password reset, you can safely ignore this email. Your password will not change unless you use the link.
                                </td></tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <p style="max-width:480px;margin:1rem 0 0;color:#9aa3b2;font-size:.75rem;line-height:1.6;text-align:center;">
                    Automated message from LSPDMS. Please do not reply to this email.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
