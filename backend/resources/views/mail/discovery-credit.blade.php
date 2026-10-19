@extends('mail.layout')

@section('title', 'رصيد مجموعة الاكتشاف')

@section('body')
  <p style="margin:0 0 6px;font-size:22px;font-family:Georgia,serif">{{ $order->customer_name }}،</p>
  <p style="margin:0 0 18px">
    وصلتك مجموعة الاكتشاف. حين تختار عطرك، يعود إليك ثمن العلبة رصيداً على أول زجاجة.
  </p>
  <p style="margin:0 0 6px;font-size:13px;color:#5F564B">كود الرصيد</p>
  <p style="margin:0 0 6px;font-size:26px;letter-spacing:3px;font-family:Georgia,serif;direction:ltr;text-align:right">{{ $credit->code }}</p>
  <p style="margin:0 0 20px;font-size:14px">
    قيمته {{ \App\Support\Money::egp($credit->value) }} ج.م، صالح حتى {{ $credit->ends_at?->format('Y/m/d') }}،
    ويُستخدم مرة واحدة برقم الموبايل {{ $credit->phone }}.
  </p>
  <p style="margin:0"><a href="{{ $url }}" style="display:inline-block;background:#0E0D0C;color:#F2EADB;text-decoration:none;padding:12px 26px">اختر زجاجتك</a></p>
@endsection
