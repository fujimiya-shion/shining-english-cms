<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phản hồi từ Shining English</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="background:linear-gradient(135deg,#0f766e,#0ea5a4);padding:20px 24px;color:#ffffff;">
                        <h1 style="margin:0;font-size:22px;line-height:1.3;">Phản hồi từ Shining English</h1>
                        <p style="margin:6px 0 0 0;font-size:14px;opacity:0.95;">Xin chào {{ $contact->name }}, cảm ơn bạn đã liên hệ.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 14px 0;font-size:14px;color:#334155;line-height:1.7;">
                            Chúng tôi đã nhận được yêu cầu của bạn và gửi phản hồi bên dưới.
                        </p>

                        <div style="padding:16px;border-radius:10px;background:#ecfeff;border:1px solid #a5f3fc;">
                            <p style="margin:0 0 8px 0;font-size:13px;font-weight:700;color:#155e75;">Nội dung phản hồi</p>
                            <p style="margin:0;font-size:14px;color:#0e7490;line-height:1.7;white-space:pre-wrap;">{{ $replyMessage }}</p>
                        </div>

                        <div style="margin-top:18px;padding:14px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;">
                            <p style="margin:0 0 8px 0;font-size:13px;font-weight:700;color:#334155;">Thông tin yêu cầu của bạn</p>
                            <p style="margin:0;font-size:14px;color:#0f172a;"><strong>Email:</strong> {{ $contact->email }}</p>
                            <p style="margin:8px 0 0 0;font-size:14px;color:#0f172a;white-space:pre-wrap;"><strong>Nội dung:</strong> {{ $contact->message }}</p>
                        </div>

                        <p style="margin:20px 0 0 0;font-size:14px;color:#334155;line-height:1.7;">
                            Trân trọng,<br>
                            <strong>Shining English Support Team</strong>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:12px;color:#64748b;">Đây là email tự động, vui lòng không trả lời trực tiếp email này.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
