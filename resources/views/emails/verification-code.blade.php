<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <title>Your verification code</title>
        <style>
            @media only screen and (max-width: 480px) {
                .container { width: 100% !important; }
                .padding { padding: 2rem 1.5rem !important; }
                .code-box { font-size: 1.6rem !important; letter-spacing: 0.3em !important; }
            }
        </style>
    </head>
    <body style="margin:0; padding:0; background:#f0f2f5; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5; padding: 2.5rem 0;">
            <tr>
                <td align="center">
                    <table role="presentation" width="480" class="container" cellpadding="0" cellspacing="0"
                        style="max-width:480px; width:100%; background:#ffffff; border-radius:8px; overflow:hidden;">

                        <!-- Logo mark -->
                        <tr>
                            <td style="padding: 2.2rem 2.5rem 0;">
                                <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="18" cy="18" r="18" fill="#0866FF"/>
                                    <path d="M12 18.5L16 22.5L24 13.5" stroke="white" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </td>
                        </tr>

                        <tr>
                            <td class="padding" style="padding: 1.4rem 2.5rem 2.4rem;">

                                <h1 style="color:#050505; font-size:1.375rem; line-height:1.35; margin:0 0 0.5rem; font-weight:700; letter-spacing:-0.01em;">
                                    Confirm it's you
                                </h1>

                                <p style="color:#65676b; font-size:0.9375rem; line-height:1.55; margin:0 0 1.75rem;">
                                    Enter this code to finish logging in to LSPDMS. It's only valid for the next 5 minutes.
                                </p>

                                <!-- Code -->
                                <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td style="background:#f0f2f5; border-radius:8px; padding: 1.1rem 0; text-align:center;">
                                            <span class="code-box" style="font-size:1.875rem; font-weight:700; letter-spacing:0.4em; color:#050505; font-family: 'Helvetica Neue', Arial, sans-serif;">
                                                {{ $code }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>

                                <p style="color:#8a8d91; font-size:0.8125rem; line-height:1.55; margin:1.75rem 0 0;">
                                    If you didn't request this, you can safely ignore this email — no changes will be made to your account.
                                </p>
                            </td>
                        </tr>

                        <!-- Divider -->
                        <tr>
                            <td style="padding: 0 2.5rem;">
                                <div style="height:1px; background:#e4e6eb;"></div>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 1.5rem 2.5rem 2rem;">
                                <p style="color:#8a8d91; font-size:0.75rem; line-height:1.6; margin:0;">
                                    This is an automated message from LSPDMS. Please don't reply to this email.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>