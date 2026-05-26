<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kayıp Bonusu</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: #151020;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, Helvetica, sans-serif;
        }
        .page-blocker {
            position: fixed;
            inset: 0;
            background: rgba(21, 16, 32, 0.88);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-align: center;
        }

        .page-blocker.active {
            display: flex;
        }

        .page-blocker-box {
            background: #241d35;
            padding: 28px 32px;
            border-radius: 10px;
            max-width: 340px;
            width: calc(100% - 30px);
        }

        .page-blocker-text {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .page-blocker-sub {
            font-size: 14px;
            opacity: .8;
        }
        .main-wrapper{
            width:100%;
            max-width:440px;
        }
        .bonus-card {
            width: 100%;
            max-width: 440px;
            background: #241d35;
            border-radius: 6px;
            overflow: hidden;
            color: #fff;
        }

        .bonus-image {
            width: 100%;
            background: #2e2542;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, .35);
            font-size: 15px;
        }

        .bonus-image img {
            object-fit: cover;
            display: block;
        }

        .bonus-content {
            padding: 28px 20px 22px;
        }

        .bonus-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .bonus-type {
            font-size: 14px;
            color: #ffffff;
            margin-bottom: 28px;
        }

        .bonus-btn {
            background: #6542a4;
            border: 0;
            border-radius: 8px;
            color: #fff;
            padding: 10px 14px;
            font-weight: 600;
            font-size: 14px;
        }

        .bonus-btn:hover {
            background: #7650bb;
            color: #fff;
        }

        @media (max-width: 576px) {
            body {
                padding: 12px;
            }

            .bonus-card {
                max-width: 100%;
            }

        }
    </style>
</head>
<body>
<div class="main-wrapper">
    <div class="text-center mb-4">
        <img src="{{asset('client/logo.png')}}" class="img-fluid" alt="Logo">
    </div>

    <div class="bonus-card">
        <div class="bonus-image">
            <img src="{{asset('client/30-Anlik-Kayip-BonusuPromo-min.png')}}" class="img-fluid" alt="Kayıp Bonusu">
        </div>

        <div class="bonus-content">
            <h1 class="bonus-title">%30'a Varan Anlık Kayıp Bonusu</h1>
            <div class="bonus-type">Discount</div>

            <div class="text-center">
                <button onclick="claimBonus('527d745f-269a-4e88-9677-3943a057093e')" class="bonus-btn">
                    Talep Et
                </button>
            </div>
        </div>
    </div>
</div>
<div id="pageBlocker" class="page-blocker">
    <div class="page-blocker-box">
        <div id="pageBlockerText" class="page-blocker-text">
            Talebiniz iletiliyor
        </div>
        <div id="pageBlockerSub" class="page-blocker-sub">
            Lütfen bekleyiniz
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    username = urlParams.get('username');
    isFtn = /_\d+_FTN$/.test(username);
    user_id = urlParams.get('user_id');
    let isRequesting = false;
    function claimBonus(bonus) {
        if (isRequesting) {
            return;
        }

        isRequesting = true;

        $('#pageBlockerText').text('Talebiniz iletiliyor');
        $('#pageBlocker').addClass('active');

        $.post("/api/bonus/request", {
            bonus: bonus,
            username: username,
            user_id: user_id
        }, function (data) {
            if (data.success) {
                $('#pageBlockerText').text('Talebiniz işlemde');
                $('#pageBlockerSub').text('Sonuç bekleniyor, lütfen sayfayı kapatmayın');
                listenResult(data.bonusRequest.uuid);
            } else {
                $('#pageBlocker').removeClass('active');
                alert("Bonus talep edilirken bir hata oluştu: " + data.message);
                isRequesting = false;
            }
        }).fail(function () {
            $('#pageBlocker').removeClass('active');
            alert("Sunucuya bağlanırken bir hata oluştu.");
            isRequesting = false;
        });
    }

    function listenResult(uuid) {
        const source = new EventSource('/api/bonus/stream/' + encodeURIComponent(uuid));

        source.onmessage = function (event) {
            source.close();
            handleResult(JSON.parse(event.data));
        };

        source.onerror = function () {
            // Sonuç henüz gelmeden bağlantı koparsa: tarayıcı otomatik tekrar dener.
            // Terminal sonuç geldiğinde onmessage içinde close() çağrıldığı için burası tetiklenmez.
        };
    }

    function handleResult(res) {
        isRequesting = false;

        if (res.error) {
            $('#pageBlocker').removeClass('active');
            alert(res.message || 'Bir hata oluştu.');
            return;
        }

        if (res.status === 'approved' || res.status === 'approved_assigned') {
            $('#pageBlockerText').text('Bonusunuz tanımlandı!');
            $('#pageBlockerSub').text(res.status_reason || 'İşlem başarıyla tamamlandı.');
        } else {
            $('#pageBlockerText').text('Talebiniz onaylanmadı');
            $('#pageBlockerSub').text(res.status_reason || 'Bonus talebiniz reddedildi.');
        }
    }

</script>
</body>
</html>
