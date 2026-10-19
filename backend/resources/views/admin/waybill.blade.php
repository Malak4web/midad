<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>بوليصة {{ $order->number }}</title>
<style>
  @page { size: A5; margin: 10mm; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: Tahoma, 'Segoe UI', Arial, sans-serif; color: #0E0D0C; font-size: 12.5px; }
  .sheet { border: 1.5px solid #0E0D0C; padding: 14px 16px; max-width: 148mm; margin: 0 auto; }
  .row { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
  h1 { margin: 0; font-size: 20px; letter-spacing: 4px; font-family: Georgia, serif; }
  .num { font-family: 'Courier New', monospace; font-size: 18px; direction: ltr; border: 1px dashed #0E0D0C; padding: 4px 10px; }
  .box { border-top: 1px solid #0E0D0C; margin-top: 10px; padding-top: 8px; }
  .label { color: #5F564B; font-size: 11px; }
  .big { font-size: 16px; font-weight: bold; }
  table { width: 100%; border-collapse: collapse; margin-top: 4px; }
  td { padding: 3px 0; border-bottom: 1px dotted #b8a888; }
  .cod { font-size: 22px; font-weight: bold; border: 2px solid #0E0D0C; padding: 6px 10px; text-align: center; margin-top: 10px; }
  .noprint { text-align: center; margin: 14px 0; }
  @media print { .noprint { display: none; } }
</style>
</head>
<body>
<div class="noprint"><button onclick="window.print()">طباعة</button></div>
<div class="sheet">
  <div class="row"><h1>MIDAD · مداد</h1><span class="num">{{ $order->number }}</span></div>
  <div class="row" style="margin-top:4px"><span class="label">{{ $order->created_at?->format('Y/m/d H:i') }}</span><span class="label">{{ $order->items->sum('quantity') }} قطعة</span></div>

  <div class="box">
    <div class="label">المستلم</div>
    <div class="big">{{ $order->customer_name }} · <span dir="ltr">{{ $order->phone }}</span></div>
    @php($a = $order->shipping_address)
    <div>{{ $a['governorate'] ?? '' }}، {{ $a['city'] ?? '' }}</div>
    <div>{{ $a['street'] ?? '' }}@if(!empty($a['building'])) · عمارة {{ $a['building'] }}@endif @if(!empty($a['floor'])) · دور {{ $a['floor'] }}@endif @if(!empty($a['apartment'])) · شقة {{ $a['apartment'] }}@endif</div>
    @if(!empty($a['landmark']))<div class="label">علامة مميزة: {{ $a['landmark'] }}</div>@endif
    @if($order->customer_note)<div class="label">ملاحظة: {{ $order->customer_note }}</div>@endif
  </div>

  <div class="box">
    <div class="label">المحتويات</div>
    <table>
      @foreach($order->items as $item)
        <tr><td>{{ $item->product_name }}@if($item->size_ml) · {{ $item->size_ml }} مل@endif</td><td style="text-align:left">× {{ $item->quantity }}</td></tr>
      @endforeach
    </table>
    @if($order->gift)<div style="margin-top:6px"><strong>هدية:</strong> تغليف هدية · إلى {{ $order->gift->recipient_name ?: '—' }} · لا تُرفق الفاتورة داخل العلبة</div>@endif
  </div>

  @if($order->payment_method === 'cod' && $order->payment_status !== 'paid')
    <div class="cod">المطلوب تحصيله: {{ \App\Support\Money::egp($order->total_piasters) }} ج.م</div>
  @else
    <div class="cod">مدفوع مسبقاً · لا يُحصَّل شيء</div>
  @endif
</div>
</body>
</html>
