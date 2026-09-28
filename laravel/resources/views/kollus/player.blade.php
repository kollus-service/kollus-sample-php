{{-- Kollus 플레이어 iframe 뷰. 컨트롤러가 생성한 JWT 서명 플레이어 URL($playerUrl)을 iframe src로 렌더링한다. --}}
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1.0">
    <title>Kollus Player</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .player-wrapper { width: 100%; max-width: 960px; aspect-ratio: 16 / 9; }
        .player-wrapper iframe { width: 100%; height: 100%; border: none; display: block; }
    </style>
</head>
<body>
    <div class="player-wrapper">
        {{-- playerUrl은 컨트롤러에서 생성한 JWT 서명 URL이므로 {!! !!}로 출력 --}}
        <iframe
            id="kollus-player"
            src="{!! $playerUrl !!}"
            allowfullscreen
            webkitallowfullscreen
            mozallowfullscreen
            allow="encrypted-media; local-network-access"
        ></iframe>
    </div>
</body>
</html>
