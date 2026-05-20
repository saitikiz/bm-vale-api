<!doctype html>
<html>
<head><meta charset="utf-8"><title>PGBO OTP</title></head>
<body>
<h3>OTP Girişi</h3>

@if ($errors->any())
    <div style="color:red;">
        {{ $errors->first() }}
    </div>
@endif

<form method="post" action="/admin/pgbo/otp">
    @csrf
    <div>
        <label>Tek kullanımlık şifre (OTP)</label><br>
        <input name="otp" inputmode="numeric" autocomplete="one-time-code" required>
    </div>
    <br>
    <button type="submit">OTP Gönder</button>
</form>

<p style="margin-top:10px;">
    OTP ekranı düşerse <a href="/admin/pgbo/login">tekrar login</a>.
</p>
</body>
</html>
