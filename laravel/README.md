# Kollus Laravel Player

Kollus 동영상 플레이어를 Laravel 프레임워크로 통합한 샘플 프로젝트입니다. 플레이어 재생, 멀티 DRM(inkaDRM/Pallycon) 재생, 콘텐츠 업로드, 재생/DRM/다음회차/LMS 콜백 처리까지 Kollus 연동에 필요한 서버 사이드 로직을 포함합니다.

## 사전 요구사항

- PHP 8.3 이상 / Composer
- Node.js (Vite 에셋 빌드용)
- Kollus 서비스 계정 및 API Key ([Kollus 관리자 콘솔](https://c-kr.kollus.com/service-account/custom-key) 로그인 필요)
- (선택) 멀티 DRM 재생을 사용하려면 별도의 멀티 DRM 전용 Kollus 계정 키와 Pallycon(inkaDRM) 계정 키

## 파일 구조

```
app/
  Http/Controllers/
    KollusPlayerController.php              # 플레이어/멀티 DRM 플레이어 페이지 컨트롤러
    KollusUploadController.php               # 업로드 페이지 및 업로드 URL 발급 API
    KollusPlayCallbackController.php         # Play 콜백 수신
    KollusDrmCallbackController.php          # DRM 콜백 수신
    KollusNextEpisodeCallbackController.php  # 다음 회차 콜백 수신
    KollusLmsCallbackController.php          # LMS 콜백 수신
  Http/Requests/
    KollusPlayCallbackRequest.php            # 재생 콜백 파라미터 검증
    KollusDrmCallbackRequest.php             # DRM 콜백 items 파싱/검증
    KollusNextEpisodeCallbackRequest.php     # 다음 회차 콜백 파라미터 검증
  Providers/
    AppServiceProvider.php                   # Kollus 관련 서비스 싱글턴 바인딩
  Services/
    KollusJwtService.php                     # JWT 생성 및 플레이어 URL 조립
    KollusMultiDrmService.php                # 멀티 DRM(inkaDRM/Pallycon) 재생 URL 조립
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
| `KOLLUS_SECURITY_KEY` | JWT 서명 키 | 서비스 계정 > 보안키 |
| `KOLLUS_CUSTOM_KEY` | 플레이어 URL 커스텀 키 | 서비스 계정 > 커스텀키 |
| `KOLLUS_ACCESS_TOKEN` | Upload API 액세스 토큰 | 서비스 계정 > API 토큰 |
| `KOLLUS_PLAYER_URL` | 플레이어 기본 URL (기본값: `https://v.kr.kollus.com/`) | - |
| `KOLLUS_TOKEN_EXPIRE_SECONDS` | JWT 만료 시간(초, 기본값: 300) | - |
| `KOLLUS_NEXT_EPISODE_MCKS` | 다음 회차 재생 샘플 미디어 콘텐츠 키 | - |
| `KOLLUS_MULTIDRM_SECURITY_KEY` | 멀티 DRM 전용 JWT 서명 키 (일반 재생과 다른 별도 계정) | 멀티 DRM 서비스 계정 > 보안키 |
| `KOLLUS_MULTIDRM_CUSTOM_KEY` | 멀티 DRM 전용 플레이어 URL 커스텀 키 | 멀티 DRM 서비스 계정 > 커스텀키 |
| `KOLLUS_INKA_ACCESS_KEY` | inkaDRM(Pallycon) Access Key | DoveRunner 콘솔 |
| `KOLLUS_INKA_SITE_KEY` | inkaDRM(Pallycon) Site Key (라이선스 룰 암호화용) | DoveRunner 콘솔 |
| `KOLLUS_INKA_SITE_ID` | inkaDRM(Pallycon) Site ID | DoveRunner 콘솔 |
| `KOLLUS_INKA_IV` | 라이선스 룰 AES-256-CBC 암호화 IV (기본값: `0123456789abcdef`) | - |
| `KOLLUS_INKA_LICENSE_URL` | DoveRunner 라이선스 서버 URL | - |
| `KOLLUS_INKA_CERTIFICATE_URL` | DoveRunner FairPlay 인증서 서버 URL | - |

### 3. 개발 서버 실행

```bash
php artisan serve
npm run dev
```

또는 `composer dev`로 서버/큐/로그/Vite를 한번에 실행할 수 있습니다.

### 4. 동작 확인

브라우저에서 아래 URL에 접근합니다 (`미디어콘텐츠키` 부분을 실제 미디어 콘텐츠 키로 교체):

```
http://localhost/kollus/player?media_content_key=미디어콘텐츠키
```

멀티 DRM 재생은 `content_id`(멀티 DRM 콘텐츠 ID, Kollus 업로드 파일 키)를 추가로 전달합니다:

```
http://localhost/kollus/player/multi-drm?media_content_key=미디어콘텐츠키&content_id=멀티DRM콘텐츠ID
```

플레이어가 정상적으로 로드되면 설정이 완료된 것입니다.

## 주요 기능

### 샘플 플레이어 `GET /kollus/player`

`media_content_key` 쿼리 파라미터로 JWT를 발급하고 플레이어 iframe(`{player_url}s?jwt=...&custom_key=...`)을 렌더링합니다. 샘플에서는 `client_user_id`를 `guest`로 고정하며, 실제 서비스에서는 사용자 ID를 사용하여 서비스에 활용합니다.

### 멀티 DRM 플레이어 `GET /kollus/player/multi-drm`

`media_content_key`(재생할 미디어 콘텐츠 키)와 `content_id`(멀티 DRM 콘텐츠 ID, Kollus 업로드 파일 키) 쿼리 파라미터를 받아 Widevine/PlayReady/FairPlay 멀티 DRM 재생용 플레이어를 렌더링합니다. 뷰는 샘플 플레이어와 동일한 `kollus.player` 템플릿을 재사용합니다.

- `KollusMultiDrmService`가 요청 User-Agent를 분석해 브라우저별 DRM 종류/스트리밍 방식을 자동 선택합니다 (Chrome·Firefox → Widevine/dash, Safari·CriOS → FairPlay/hls, 그 외 → PlayReady/dash).
- inkaDRM(Pallycon) 라이선스 룰을 AES-256-CBC로 암호화하고 SHA-256 해시를 계산해 `custom_header`(`pallycon-customdata-v2`) 페이로드를 생성합니다.
- 일반 재생과 별도의 Kollus 계정을 사용하므로 JWT 서명/커스텀 키는 `KOLLUS_SECURITY_KEY`/`KOLLUS_CUSTOM_KEY`가 아닌 `KOLLUS_MULTIDRM_SECURITY_KEY`/`KOLLUS_MULTIDRM_CUSTOM_KEY`를 사용합니다.

### 콘텐츠 업로드 `GET /kollus/upload`

드래그앤드롭 업로드 UI를 제공합니다.

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

> 별도의 `/jwt/create` API 엔드포인트는 없으며, JWT 생성 로직이 `GET /kollus/player` 컨트롤러에 내장되어 있습니다. 현재 payload는 `cuid`/`mc`/`expt`만 지원하며, `seek`/`intro`/`outro`/`play_section`/`playback_rates`/`subtitle_policy` 등 확장 옵션이 필요하다면 `KollusJwtService::buildPayload()`에 클레임을 추가해야 합니다. mc 항목별 `drm_policy`가 필요한 멀티 DRM 재생은 `KollusJwtService::buildMultiDrmPlayerUrl()`과 `KollusMultiDrmService`를 참고하세요.

### 콜백 처리

Kollus 플레이어/서버에서 발생하는 콜백을 수신합니다. 서버 간 통신이므로 CSRF 검증에서 제외되어 있습니다(`bootstrap/app.php`).

| 경로 | 설명 | 응답 |
|------|------|------|
| `POST /kollus/play-callback` | 재생 콜백 (kind1: 만료 설정, kind3: 재생 승인) | JWT |
| `POST /kollus/drm-callback` | DRM 콜백 (다운로드 승인/완료/재생 권한) | JWT |
| `POST /kollus/next-episode-callback` | 다음 회차 콜백 | JSON |
| `POST /kollus/lms-callback` | 시청 이력 및 학습 진도 수신 | 200 OK |

## API 흐름

```
클라이언트
  │
  ├── GET /kollus/player?media_content_key=...
  │     └── KollusJwtService → JWT + 플레이어 URL 반환 (iframe src)
  │
  ├── GET /kollus/player/multi-drm?media_content_key=...&content_id=...
  │     └── KollusMultiDrmService → inkaDRM drm_policy 조립 → KollusJwtService(멀티 DRM 계정) → JWT + 플레이어 URL 반환
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
- [Kollus 관리자 콘솔](https://c-kr.kollus.com/)

---
