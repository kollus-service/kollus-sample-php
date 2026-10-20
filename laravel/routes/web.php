<?php
/**
 * Kollus 샘플 라우트 정의. 플레이어/멀티 DRM 플레이어 페이지, 업로드 페이지·URL 발급,
 * 재생·DRM·다음 회차·LMS 콜백 엔드포인트를 등록한다.
 */

use App\Http\Controllers\KollusDrmCallbackController;
use App\Http\Controllers\KollusLmsCallbackController;
use App\Http\Controllers\KollusNextEpisodeCallbackController;
use App\Http\Controllers\KollusPlayCallbackController;
use App\Http\Controllers\KollusPlayerController;
use App\Http\Controllers\KollusUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Kollus 플레이어
|--------------------------------------------------------------------------
| GET /kollus/player?media_content_key=...
|
| 실제 서비스에서는 auth 미들웨어를 추가해 인증된 사용자만 접근하도록 제한할 것.
| 예) Route::middleware('auth')->get(...)
*/
Route::get('/kollus/player', [KollusPlayerController::class, 'show'])
    ->name('kollus.player');

/*
|--------------------------------------------------------------------------
| Kollus 멀티 DRM 플레이어
|--------------------------------------------------------------------------
| GET /kollus/player/multi-drm?media_content_key=...&content_id=...
|
| Widevine/PlayReady/FairPlay 등 멀티 DRM(DoveRunner) 재생용.
| 실제 서비스에서는 auth 미들웨어를 추가해 인증된 사용자만 접근하도록 제한할 것.
*/
Route::get('/kollus/player/multi-drm', [KollusPlayerController::class, 'showMultiDrm'])
    ->name('kollus.player.multi-drm');

/*
|--------------------------------------------------------------------------
| Kollus 플레이 콜백
|--------------------------------------------------------------------------
| POST /kollus/play-callback
| CSRF 제외는 bootstrap/app.php의 validateCsrfTokens(except)에서 처리
*/
Route::post('/kollus/play-callback', [KollusPlayCallbackController::class, 'handle'])
    ->name('kollus.play-callback');

/*
|--------------------------------------------------------------------------
| Kollus DRM 다운로드 콜백
|--------------------------------------------------------------------------
| POST /kollus/drm-callback
| CSRF 제외는 bootstrap/app.php의 validateCsrfTokens(except)에서 처리
*/
Route::post('/kollus/drm-callback', [KollusDrmCallbackController::class, 'handle'])
    ->name('kollus.drm-callback');

/*
|--------------------------------------------------------------------------
| Kollus 다음 회차 연속 재생 콜백
|--------------------------------------------------------------------------
| POST /kollus/next-episode-callback
| CSRF 제외는 bootstrap/app.php의 validateCsrfTokens(except)에서 처리
*/
Route::post('/kollus/next-episode-callback', [KollusNextEpisodeCallbackController::class, 'handle'])
    ->name('kollus.next-episode-callback');

/*
|--------------------------------------------------------------------------
| Kollus LMS 콜백
|--------------------------------------------------------------------------
| POST /kollus/lms-callback
| CSRF 제외는 bootstrap/app.php의 validateCsrfTokens(except)에서 처리
*/
Route::post('/kollus/lms-callback', [KollusLmsCallbackController::class, 'handle'])
    ->name('kollus.lms-callback');

/*
|--------------------------------------------------------------------------
| Kollus 콘텐츠 업로드
|--------------------------------------------------------------------------
| GET  /kollus/upload           — 업로드 페이지
| POST /kollus/upload/create-url — 일회용 업로드 URL 발급 (AJAX, CSRF 보호)
*/
Route::get('/kollus/upload', [KollusUploadController::class, 'index'])
    ->name('kollus.upload');

Route::post('/kollus/upload/create-url', [KollusUploadController::class, 'createUrl'])
    ->name('kollus.upload.create-url');
