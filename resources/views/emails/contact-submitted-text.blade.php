<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu liên hệ mới</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f3f4f6;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="background:linear-gradient(135deg,#1d4ed8,#0ea5e9);padding:20px 24px;color:#ffffff;">
                        <h1 style="margin:0;font-size:22px;line-height:1.3;">Yêu cầu liên hệ mới</h1>
                        <p style="margin:6px 0 0 0;font-size:14px;opacity:0.95;">Shining English Website</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 16px 0;font-size:14px;color:#334155;">Bạn vừa nhận được một yêu cầu liên hệ mới với thông tin như sau:</p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                            <tr>
                                <td style="padding:12px 14px;font-size:13px;font-weight:700;color:#475569;width:130px;border-bottom:1px solid #e2e8f0;">Họ tên</td>
                                <td style="padding:12px 14px;font-size:14px;color:#0f172a;border-bottom:1px solid #e2e8f0;">{{ $contact->name }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 14px;font-size:13px;font-weight:700;color:#475569;width:130px;border-bottom:1px solid #e2e8f0;">Email</td>
                                <td style="padding:12px 14px;font-size:14px;color:#0f172a;border-bottom:1px solid #e2e8f0;">{{ $contact->email }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 14px;font-size:13px;font-weight:700;color:#475569;width:130px;border-bottom:1px solid #e2e8f0;">IP</td>
                                <td style="padding:12px 14px;font-size:14px;color:#0f172a;border-bottom:1px solid #e2e8f0;">{{ $contact->ip_address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 14px;font-size:13px;font-weight:700;color:#475569;width:130px;">User Agent</td>
                                <td style="padding:12px 14px;font-size:14px;color:#0f172a;word-break:break-word;">{{ $contact->user_agent ?? 'N/A' }}</td>
                            </tr>
                        </table>

                        <div style="margin-top:16px;padding:14px;border-radius:10px;background:#fff7ed;border:1px solid #fed7aa;">
                            <p style="margin:0 0 8px 0;font-size:13px;font-weight:700;color:#9a3412;">Nội dung tin nhắn</p>
                            <p style="margin:0;font-size:14px;color:#7c2d12;line-height:1.6;white-space:pre-wrap;">{{ $contact->message }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:12px;color:#64748b;">Email tự động từ hệ thống Shining English.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
