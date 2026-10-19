<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title')</title>
</head>
<body style="margin:0;padding:0;background:#F2EADB;font-family:Tahoma,'Segoe UI',Arial,sans-serif;color:#0E0D0C;direction:rtl">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F2EADB">
  <tr><td align="center" style="padding:32px 12px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#F8F3E8;border:1px solid #D8CAB0">
      <tr><td style="background:#0E0D0C;padding:22px 28px;text-align:right">
        <span style="font-family:Georgia,serif;letter-spacing:6px;color:#D6B77E;font-size:15px">MIDAD</span>
        <span style="color:#D8CAB0;font-size:14px;margin-right:10px">مداد</span>
      </td></tr>
      <tr><td style="padding:30px 28px 10px;text-align:right;line-height:1.9;font-size:15px">
        @yield('body')
      </td></tr>
      <tr><td style="padding:18px 28px 28px;text-align:right;font-size:12px;color:#5F564B;border-top:1px solid #D8CAB0">
        عطرٌ يُكتَب، فيبقى · هذه رسالة تلقائية عن طلبك من مداد.
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
