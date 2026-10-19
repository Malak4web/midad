@extends('mail.layout')

@section('title', $label.' · '.$order->number)

@section('body')
  <p style="margin:0 0 6px;font-size:22px;font-family:Georgia,serif">{{ $order->customer_name }}،</p>
  <p style="margin:0 0 18px">
    @switch($status)
      @case('placed') وصلنا طلبك رقم <strong>{{ $order->number }}</strong>، وسنتواصل معك على {{ $order->phone }} لتأكيده. @break
      @case('confirmed') أكّدنا طلبك رقم <strong>{{ $order->number }}</strong> وبدأنا تجهيزه. @break
      @case('shipped') طلبك رقم <strong>{{ $order->number }}</strong> خرج مع شركة الشحن، ويصلك خلال أيام قليلة. @break
      @case('delivered') وصل طلبك رقم <strong>{{ $order->number }}</strong>. نتمنى أن يكون أثره جميلاً. @break
      @case('cancelled') تم إلغاء طلبك رقم <strong>{{ $order->number }}</strong>. إن حدث هذا بالخطأ، فردّ على هذه الرسالة. @break
      @default حالة طلبك رقم <strong>{{ $order->number }}</strong> الآن: {{ $label }}.
    @endswitch
  </p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #D8CAB0;font-size:14px">
    @foreach ($order->items as $item)
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #EDE3D1">{{ $item->product_name }}@if($item->size_ml) · {{ $item->size_ml }} مل@endif × {{ $item->quantity }}</td>
        <td style="padding:10px 0;border-bottom:1px solid #EDE3D1;text-align:left;white-space:nowrap">{{ \App\Support\Money::egp($item->line_total_piasters) }} ج.م</td>
      </tr>
    @endforeach
    <tr><td style="padding:12px 0 0;font-weight:bold">الإجمالي</td><td style="padding:12px 0 0;text-align:left;font-weight:bold">{{ \App\Support\Money::egp($order->total_piasters) }} ج.م</td></tr>
  </table>
  <p style="margin:24px 0 0"><a href="{{ $url }}" style="display:inline-block;background:#0E0D0C;color:#F2EADB;text-decoration:none;padding:12px 26px">تابع طلبك</a></p>
@endsection
