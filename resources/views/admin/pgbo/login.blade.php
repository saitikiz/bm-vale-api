<!doctype html>
<html>
<head><meta charset="utf-8"><title>PGBO Login</title></head>
<body>
<h3>PGBO Login</h3>

@if ($errors->any())
    <div style="color:red;">
        {{ $errors->first() }}
    </div>
@endif

<form method="post" action="/admin/pgbo/login">
    @csrf
    <div>
        <label>Trader Code</label><br>
        <input name="trader_code" value="1830" required>
    </div>
    <div>
        <label>Username</label><br>
        <input name="username" value="onurbonus" required>
    </div>
    <div>
        <label>Password</label><br>
        <input name="password" value="Cas23400!!*" type="password" required>
    </div>
    <br>
    <button type="submit">Login (OTP'ye geç)</button>
</form>
</body>
</html>
