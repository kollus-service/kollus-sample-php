# Kollus Laravel Player

Kollus 동영상 플레이어를 Laravel 프레임워크로 통합한 샘플 프로젝트입니다. 플레이어 재생, 콘텐츠 업로드, 재생/DRM/다음회차/LMS 콜백 처리까지 Kollus 연동에 필요한 서버 사이드 로직을 포함합니다.

## 사전 요구사항

- PHP 8.3 이상 / Composer
- Node.js (Vite 에셋 빌드용)
- Kollus 서비스 계정 및 API Key ([Kollus 관리자 콘솔](https://c-kr.kollus.com/service-account/custom-key) 로그인 필요)

## 파일 구조

```
app/
  Http/Controllers/
    KollusPlayerController.php              # 플레이어 페이지 컨트롤러
    KollusUploadController.php               # 업로드 페이지 및 업로드 URL 발급 API
    KollusPlayCallbackController.php         # 재생 콜백 수신
    KollusDrmCallbackController.php          # DRM 다운로드 콜백 수신
    KollusNextEpisodeCallbackController.php  # 다음 회차 연속 재생 콜백 수신
    KollusLmsCallbackController.php          # LMS 시청 이력 콜백 수신
  Http/Requests/
    KollusPlayCallbackRequest.php            # 재생 콜백 파라미터 검증
    KollusDrmCallbackRequest.php             # DRM 콜백 items 파싱/검증
    KollusNextEpisodeCallbackRequest.php     # 다음 회차 콜백 파라미터 검증
  Providers/
    AppServiceProvider.php                   # Kollus 관련 서비스 싱글턴 바인딩
  Services/
    KollusJwtService.php                     # JWT 생성 및 플레이어 URL 조립
    KollusUploadService.php                  # Kollus Upload API 연동
    KollusPlayCallbackService.php            # 재생 콜백 응답(JWT) 조립
    KollusDrmCallbackService.php             # DRM 콜백 응답(JWT) 조립
    KollusNextEpisodeCallbackService.php     # 다음 회차 URL 조립
    KollusLmsCallbackService.php             # LMS payload 요약(시청 진도/스킵 감지)
config/
  kollus.php                     # Kollus 설정 (환경변수 참조)
resources/views/kollus/
  player.blade.php               # 플레이어 iframe 뷰
  upload.blade.php               # 업로드 페이지 (드래그앤드롭 + 진행률 표시)
routes/
  web.php                        # Kollus 관련 라우트 전체
```

## 설치 및 실행

### 1. 의존성 설치

```bash
composer install
npm install
```

### 2. 환경변수 설정

```bash
cp .env.example .env
php artisan key:generate
```

`.env` 파일에서 Kollus 관련 값을 입력합니다.

| 키 | 설명 | 위치 |
|----|------|------|
| `KOLLUS_SECURITY_KEY` | JWT 서명 키 | 대시보드 > 채널 설정 |
| `KOLLUS_CUSTOM_KEY` | 플레이어 URL 커스텀 키 | 대시보드 > 채널 설정 |
| `KOLLUS_ACCESS_TOKEN` | Upload API 액세스 토큰 | VOD 콘솔 > 서비스 계정 > API |
| `KOLLUS_PLAYER_URL` | 플레이어 기본 URL (기본값: `https://v.kr.kollus.com/`) | - |
| `KOLLUS_TOKEN_EXPIRE_SECONDS` | JWT 만료 시간(초, 기본값: 300) | - |
| `KOLLUS_NEXT_EPISODE_MCKS` | 다음 회차 연속 재생 샘플 MCK 목록 (콤마로 구분) | - |

> Kollus 대시보드 > 채널 설정 및 VOD 콘솔 > 서비스 계정에서 발급받은 값을 사용하세요.

### 3. 개발 서버 실행

```bash
php artisan serve
npm run dev
```

또는 `composer dev`로 서버/큐/로그/Vite를 한번에 실행할 수 있습니다.

### 4. 동작 확인

브라우저에서 아래 URL에 접근합니다 (`YmJybQjF` 부분을 실제 미디어 콘텐츠 키로 교체):

```
http://localhost:8000/kollus/player?media_content_key=YmJybQjF
```

플레이어가 정상적으로 로드되면 설정이 완료된 것입니다.

## 주요 기능

### 샘플 플레이어 `GET /kollus/player`

`media_content_key` 쿼리 파라미터로 JWT를 발급하고 플레이어 iframe(`{player_url}s?jwt=...&custom_key=...`)을 렌더링합니다. 샘플에서는 `client_user_id`를 `guest`로 고정하며, 실제 서비스에서는 `auth` 미들웨어로 보호하고 인증된 사용자 ID를 사용해야 합니다.

### 콘텐츠 업로드 `GET /kollus/upload`

드래그앤드롭 업로드 UI를 제공합니다. `access_token`은 서버에서만 사용하며 프론트엔드에 노출되지 않습니다.

1. `POST /kollus/upload/create-url` — 서버가 Kollus Upload API를 호출해 일회용 업로드 URL 발급 (`{ upload_url, progress_url, upload_file_key, expired_at }`)
2. 브라우저가 발급된 URL로 파일을 직접 전송(XHR, 진행률 표시)
3. 완료 후 Upload File Key 확인

**요청 파라미터** (`POST /kollus/upload/create-url`)

| 파라미터 | 설명 |
|----------|------|
| `title` | 콘텐츠 제목 (미입력 시 파일명 사용) |
| `file_name` | 원본 파일명 |
| `category_key` | 저장할 카테고리 키 (미입력 시 기본 카테고리) |
| `expire_time` | 업로드 URL 유효 시간(초), 60~21600 |
| `is_audio` | 오디오 파일 여부 |
| `is_passthrough` | 패스스루 업로드 여부 — `1`인 경우 `profile_key` 필수 |
| `profile_key` | 인코딩 프로파일 키 (패스스루 전용) |

일반 업로드와 패스스루 업로드는 서로 다른 Kollus 엔드포인트를 사용하며, `KollusUploadService`가 두 응답 포맷을 공통 구조로 정규화합니다.

### JWT / 플레이어 URL 생성

`KollusJwtService`가 HS256 JWT(`cuid`, `mc`, `expt` 클레임)를 생성하고 플레이어(`/s`)와 다음 회차(`/si`) URL을 조립합니다. 콜백 응답에 필요한 JWT 인코딩도 이 서비스를 재사용합니다.

> 별도의 `/jwt/create` API 엔드포인트는 없으며, JWT 생성 로직이 `GET /kollus/player` 컨트롤러에 내장되어 있습니다. 현재 payload는 `cuid`/`mc`/`expt`만 지원하며, `seek`/`intro`/`outro`/`play_section`/`playback_rates`/`subtitle_policy`/`video_watermarking_code_policy` 등 확장 옵션이 필요하다면 `KollusJwtService::buildPayload()`에 클레임을 추가해야 합니다.

### 콜백 처리

Kollus 플레이어/서버에서 발생하는 콜백을 수신합니다. 서버 간 통신이므로 CSRF 검증에서 제외되어 있습니다(`bootstrap/app.php`).

| 경로 | 설명 | 응답 |
|------|------|------|
| `POST /kollus/play-callback` | 재생 콜백 | JWT (`text/plain`, `X-KOLLUS-USERKEY` 헤더 포함) |
| `POST /kollus/drm-callback` | DRM 다운로드/오프라인 재생 콜백 | JWT (`text/plain`, `X-KOLLUS-USERKEY` 헤더 포함) |
| `POST /kollus/next-episode-callback` | 다음 회차 연속 재생 콜백 | JSON `{ result, next_url }` |
| `POST /kollus/lms-callback` | 시청 이력 및 학습 진도 수신 | 204 No Content |

**재생 콜백 (`kind`)**

| kind | 설명 | 응답 payload |
|------|------|--------------|
| `1` | 재생 시작 전 만료 일시/보안 옵션 설정 | `{ result, expiration_date, disable_tvout, message? }` |
| `3` | 플레이어 준비 완료 직후 최종 재생 권한 확인 | `{ result, message? }` |

**DRM 콜백 (`items[].kind`)**

| kind | 설명 | 응답 payload |
|------|------|--------------|
| `1` | 다운로드 버튼 클릭 시 권한/DRM 정책 할당 | `{ kind, media_content_key, result, expiration_date }` |
| `2` | 파일 저장 100% 완료 통보 | `{ kind, media_content_key, result, content_delete }` |
| `3` | 오프라인 저장 콘텐츠 재생 시 권한 확인 | `{ kind, session_key, media_content_key, start_at, result, content_expired }` |

**다음 회차 콜백**: `mck`(현재 재생 MCK), `id`(클라이언트 사용자 ID)를 받아 `KOLLUS_NEXT_EPISODE_MCKS` 목록에서 샘플 MCK를 선택하고 SI 링크(`/si`)를 반환합니다. 실제 서비스에서는 `currentMck` 기준 DB/API 조회로 대체해야 합니다.

**LMS 콜백**: `json_data` 파라미터(JSON 문자열, `user_info`/`content_info`/`block_info`/`uservalues` 구조)를 파싱해 시청 시간, 진도율, 구간 스킵 여부를 계산하고 `storage/logs/kollus-lms.log`(채널: `kollus_lms`)에 기록합니다. fire-and-forget 방식으로 파싱 실패와 무관하게 항상 204를 반환합니다.

## API 흐름

```
클라이언트
  │
  ├── GET /kollus/player?media_content_key=...
  │     └── KollusJwtService → JWT + 플레이어 URL 반환 (iframe src)
  │
  ├── GET /kollus/upload
  │     ├── POST /kollus/upload/create-url → Kollus Upload API 프록시
  │     └── XHR → Kollus 업로드 서버 (직접 전송)
  │
  └── Kollus 플레이어 / 서버
        ├── POST /kollus/play-callback
        ├── POST /kollus/drm-callback
        ├── POST /kollus/next-episode-callback
        └── POST /kollus/lms-callback
```

## 관련 문서

- [Kollus API 레퍼런스](https://docs.kollus.com)
- [Kollus 관리자 콘솔](https://admin.kollus.com)

---
