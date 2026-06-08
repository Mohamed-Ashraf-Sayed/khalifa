<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة {{ $invoice->invoice_number }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right;">
    <div style="max-width:600px;margin:24px auto;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e5e5e5;">
        <div style="background:#2b4c80;color:#fff;padding:20px 24px;">
            <h2 style="margin:0;font-size:20px;">{{ $company }}</h2>
        </div>

        <div style="padding:24px;color:#333;line-height:1.8;">
            <p style="margin:0 0 12px;">السلام عليكم ورحمة الله وبركاته،</p>
            <p style="margin:0 0 16px;">مرفق طيّه فاتورتكم بالتفاصيل التالية:</p>

            <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
                <tr>
                    <td style="padding:8px 0;color:#777;">رقم الفاتورة</td>
                    <td style="padding:8px 0;font-weight:bold;text-align:left;">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#777;">التاريخ</td>
                    <td style="padding:8px 0;font-weight:bold;text-align:left;">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#777;">الإجمالي</td>
                    <td style="padding:8px 0;font-weight:bold;text-align:left;">{{ number_format($invoice->total_amount, 2) }} ج.م</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#777;">المتبقّي</td>
                    <td style="padding:8px 0;font-weight:bold;text-align:left;color:#c97a00;">{{ number_format($invoice->remaining(), 2) }} ج.م</td>
                </tr>
            </table>

            <p style="margin:0 0 8px;color:#555;">تجدون نسخة PDF من الفاتورة مرفقة مع هذه الرسالة.</p>
            <p style="margin:16px 0 0;">شكراً لتعاملكم معنا.</p>
        </div>

        <div style="background:#f9f9f9;padding:16px 24px;color:#999;font-size:12px;text-align:center;border-top:1px solid #eee;">
            {{ $company }}
        </div>
    </div>
</body>
</html>
