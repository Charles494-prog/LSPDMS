<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <title>Your verification code</title>
        <style>
            @media only screen and (max-width: 520px) {
                .container { width: 100% !important; }
                .padding   { padding: 1.5rem 1.25rem !important; }
                .code      { font-size: 1.5rem !important; letter-spacing: 0.28em !important; }
            }
        </style>
    </head>
    <body style="margin:0; padding:0; background:#f4f6f9; font-family: 'Inter', -apple-system, 'Segoe UI', 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9; padding: 3rem 1rem;">
            <tr>
                <td align="center">

                    <table role="presentation" width="480" class="container" cellpadding="0" cellspacing="0"
                        style="max-width:480px; width:100%; background:#ffffff; border:1px solid #e6e9ef; border-radius:16px; box-shadow:0 1px 3px rgba(16,24,40,0.06);">
                        <tr>
                            <td class="padding" style="padding: 2rem 2rem 2.1rem;">

                                <!-- Icon -->
                                <table role="presentation" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td width="44" height="44" align="center" valign="middle"
                                            style="width:44px; height:44px; background:#e8f0ff; border-radius:22px; color:#0866FF; font-size:20px; font-weight:700; line-height:44px; text-align:center;">
                                            &#10003;
                                        </td>
                                    </tr>
                                </table>

                                <h1 style="color:#0f172a; font-size:1.25rem; line-height:1.4; margin:1.25rem 0 0.4rem; font-weight:600; letter-spacing:-0.01em;">
                                    Confirm your login
                                </h1>

                                <p style="color:#5b6472; font-size:0.875rem; line-height:1.6; margin:0 0 1.25rem;">
                                    You requested to log in to <strong style="color:#0f172a; font-weight:600;">LSPDMS</strong>. Your one-time code is:
                                </p>

                                <!-- Code -->
                                <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td align="center" style="background:#f3f5f9; border-radius:10px; padding:1rem 0.5rem;">
                                            <div class="code" style="display:block; font-size:1.625rem; font-weight:600; letter-spacing:0.4em; text-indent:0.4em; color:#0866FF; font-family: 'SF Mono', 'Roboto Mono', Consolas, 'Courier New', monospace; -webkit-user-select:all; user-select:all;">{{ $code }}</div>
                                        </td>
                                    </tr>
                                </table>

                                <p style="color:#0f172a; font-size:0.8125rem; font-weight:600; line-height:1.6; margin:1.25rem 0 0;">
                                    This code expires in 5 minutes.
                                </p>

                                <p style="color:#5b6472; font-size:0.8125rem; line-height:1.65; margin:1.1rem 0 0;">
                                    If you didn't request this, you can safely ignore this email. Someone else might have typed your email address by mistake.
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p style="max-width:480px; color:#9aa3b2; font-size:0.75rem; line-height:1.6; margin:1.25rem 0 0; text-align:center;">
                        Automated message from LSPDMS. Please don't reply to this email.
                    </p>

                </td>
            </tr>
        </table>
    </body>
</html>