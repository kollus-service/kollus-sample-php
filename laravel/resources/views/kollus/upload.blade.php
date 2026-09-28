{{-- Kollus 콘텐츠 업로드 페이지. 서버(create-url)에서 일회용 업로드 URL을 발급받은 뒤 브라우저가 Kollus 업로드 서버로 파일을 직접 전송(XHR, 진행률 표시)한다. --}}
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kollus 콘텐츠 업로드</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 40px 16px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            padding: 32px;
            width: 100%;
            max-width: 560px;
        }

        h1 { font-size: 1.25rem; font-weight: 600; margin-bottom: 24px; color: #111; }

        .field { margin-bottom: 16px; }
        label { display: block; font-size: .85rem; font-weight: 500; color: #444; margin-bottom: 6px; }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: .95rem;
            outline: none;
            transition: border-color .15s;
        }
        input:focus { border-color: #4f46e5; }

        .file-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .file-area:hover, .file-area.drag-over { border-color: #4f46e5; background: #f5f3ff; }
        .file-area input { display: none; }
        .file-area .icon { font-size: 2rem; margin-bottom: 8px; }
        .file-area .hint { font-size: .85rem; color: #888; margin-top: 4px; }
        .file-name { font-size: .9rem; color: #4f46e5; font-weight: 500; margin-top: 8px; word-break: break-all; }

        .btn {
            display: block;
            width: 100%;
            padding: 11px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 24px;
            transition: background .15s;
        }
        .btn:hover:not(:disabled) { background: #4338ca; }
        .btn:disabled { background: #a5b4fc; cursor: not-allowed; }

        /* 진행률 영역 */
        #progress-section { display: none; margin-top: 24px; }
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: .85rem;
            color: #555;
            margin-bottom: 6px;
        }
        .progress-bar-track {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 99px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: #4f46e5;
            border-radius: 99px;
            width: 0%;
            transition: width .3s ease;
        }

        /* 상태 메시지 */
        #status-msg {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: .9rem;
            display: none;
        }
        #status-msg.success { background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; }
        #status-msg.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        #status-msg.info    { background: #eff6ff; color: #1e40af; border: 1px solid #93c5fd; }
    </style>
</head>
<body>
<div class="card">
    <h1>콘텐츠 업로드</h1>

    <form id="upload-form">
        {{-- 패스스루 체크박스 --}}
        <div class="field" style="display:flex;align-items:center;gap:10px;">
            <input type="checkbox" id="is-passthrough" style="width:16px;height:16px;cursor:pointer;accent-color:#4f46e5;">
            <label for="is-passthrough" style="margin:0;cursor:pointer;">패스스루 업로드</label>
        </div>

        {{-- 제목 (패스스루 시 숨김) --}}
        <div class="field" id="field-title">
            <label for="title">제목 <span style="color:#aaa;font-weight:400">(선택 — 미입력 시 파일명 사용)</span></label>
            <input type="text" id="title" placeholder="미입력 시 파일명으로 저장됩니다">
        </div>

        {{-- 인코딩 프로파일 키 (패스스루 시만 노출) --}}
        <div class="field" id="field-profile-key" style="display:none;">
            <label for="profile-key">인코딩 프로파일 키 <span style="color:#e11d48">*</span></label>
            <input type="text" id="profile-key" placeholder="패스스루 업로드에 사용할 프로파일 키를 입력하세요">
        </div>

        <div class="field">
            <label for="category-key">카테고리 키 <span style="color:#aaa;font-weight:400">(선택)</span></label>
            <input type="text" id="category-key" placeholder="미입력 시 기본 카테고리에 저장됩니다">
        </div>

        <div class="field">
            <label for="expire-time">업로드 URL 유효 시간 (초)</label>
            <input type="number" id="expire-time"
                   value="{{ \App\Services\KollusUploadService::DEFAULT_EXPIRE_TIME }}"
                   min="{{ \App\Services\KollusUploadService::MIN_EXPIRE_TIME }}"
                   max="{{ \App\Services\KollusUploadService::MAX_EXPIRE_TIME }}">
        </div>

        <div class="field">
            <label>파일 <span style="color:#e11d48">*</span></label>
            <div class="file-area" id="file-area">
                <input type="file" id="file-input" accept="video/*,audio/*">
                <div class="icon">📁</div>
                <div>클릭하거나 파일을 여기에 드래그하세요</div>
                <div class="hint">video/*, audio/* 형식 지원</div>
                <div class="file-name" id="file-name" style="display:none"></div>
            </div>
        </div>

        <button type="submit" class="btn" id="submit-btn">업로드 시작</button>
    </form>

    <div id="progress-section">
        <div class="progress-label">
            <span id="progress-status">업로드 중...</span>
            <span id="progress-pct">0%</span>
        </div>
        <div class="progress-bar-track">
            <div class="progress-bar-fill" id="progress-bar"></div>
        </div>
    </div>

    <div id="status-msg"></div>
</div>

<script>
(function () {
    'use strict';

    /** Laravel CSRF 토큰 (create-url AJAX 요청 헤더에 사용) */
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
    /** 업로드 URL 발급 엔드포인트 (POST /kollus/upload/create-url) */
    const CREATE_URL_ENDPOINT = '{{ route("kollus.upload.create-url") }}';
    /** 용량 단위 환산 기준 (1 KB = 1024 B) */
    const BYTES_PER_UNIT = 1024;
    /** 진행률 최대값 (%) */
    const PERCENT_MAX = 100;
    /** HTTP 200 OK — Kollus 업로드 서버 응답 상태 판단용 */
    const HTTP_OK = 200;

    /** 진행 중인 업로드의 파일 키 — XHR load 핸들러가 완료 메시지에 표시 */
    let pendingUploadFileKey = null;

    /* --- DOM 참조 --- */
    const passthroughCheckbox = document.getElementById('is-passthrough');
    const fieldTitle          = document.getElementById('field-title');
    const fieldProfileKey     = document.getElementById('field-profile-key');
    const titleInput          = document.getElementById('title');
    const profileKeyInput     = document.getElementById('profile-key');
    const categoryKeyInput    = document.getElementById('category-key');
    const expireTimeInput     = document.getElementById('expire-time');
    const form                = document.getElementById('upload-form');
    const fileArea            = document.getElementById('file-area');
    const fileInput           = document.getElementById('file-input');
    const fileNameEl          = document.getElementById('file-name');
    const submitBtn           = document.getElementById('submit-btn');
    const progressSec         = document.getElementById('progress-section');
    const progressBar         = document.getElementById('progress-bar');
    const progressPct         = document.getElementById('progress-pct');
    const progressSts         = document.getElementById('progress-status');
    const statusMsg           = document.getElementById('status-msg');

    /* --- 유틸리티 --- */

    /**
     * 바이트 수를 B/KB/MB/GB 단위 문자열로 변환한다.
     * @param {number} bytes 바이트 수
     * @returns {string} 사람이 읽기 쉬운 용량 문자열 (예: "12.3 MB")
     */
    function formatBytes(bytes) {
        if (bytes < BYTES_PER_UNIT)      return bytes + ' B';
        if (bytes < BYTES_PER_UNIT ** 2) return (bytes / BYTES_PER_UNIT).toFixed(1) + ' KB';
        if (bytes < BYTES_PER_UNIT ** 3) return (bytes / BYTES_PER_UNIT ** 2).toFixed(1) + ' MB';
        return (bytes / BYTES_PER_UNIT ** 3).toFixed(2) + ' GB';
    }

    /**
     * 상태 메시지 영역에 메시지를 표시한다.
     * @param {string} msg 표시할 메시지
     * @param {'success'|'error'|'info'} type 메시지 종류 (동일 이름의 CSS 클래스 적용)
     * @returns {void}
     */
    function showStatus(msg, type) {
        statusMsg.textContent   = msg;
        statusMsg.className     = type;
        statusMsg.style.display = 'block';
    }

    /**
     * 진행률 바와 퍼센트 텍스트를 갱신한다.
     * @param {number} pct 진행률 (0~PERCENT_MAX, 범위 밖 값은 경계값으로 보정)
     * @param {string} [statusText] 진행 상태 문구 (생략 시 기존 문구 유지)
     * @returns {void}
     */
    function setProgress(pct, statusText) {
        const clamped = Math.min(Math.max(pct, 0), PERCENT_MAX);
        progressBar.style.width = clamped + '%';
        progressPct.textContent = clamped + '%';
        if (statusText) progressSts.textContent = statusText;
    }

    /**
     * 선택된 파일을 file input에 동기화하고 파일명/용량을 표시한다.
     * @param {File} file 클릭 선택 또는 드롭된 파일
     * @returns {void}
     */
    function setFile(file) {
        // 드래그앤드롭으로 받은 파일은 input.files에 직접 대입할 수 없으므로 DataTransfer로 동기화
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;

        fileNameEl.textContent   = `${file.name} (${formatBytes(file.size)})`;
        fileNameEl.style.display = 'block';
    }

    /**
     * fetch 응답 본문을 JSON으로 안전하게 파싱한다.
     * @param {Response} res fetch 응답
     * @returns {Promise<Object>} 파싱된 JSON 객체. 파싱 불가 시 빈 객체
     */
    async function parseJsonSafely(res) {
        try {
            return await res.json();
        } catch {
            // 오류 응답이 JSON이 아닐 수 있음(예: 프레임워크 HTML 에러 페이지) —
            // 빈 객체를 돌려 호출부가 HTTP 상태 코드 기반 메시지로 대체하도록 한다
            return {};
        }
    }

    /**
     * 서버(create-url)에 일회용 업로드 URL 발급을 요청한다.
     * @param {Object} params create-url 요청 파라미터 (title, file_name, category_key, expire_time, is_passthrough, profile_key)
     * @returns {Promise<Object>} { upload_url, progress_url, upload_file_key, expired_at }
     * @throws {Error} HTTP 오류 시 서버가 준 error 메시지 또는 상태 코드를 담은 Error
     */
    async function requestUploadUrl(params) {
        const res = await fetch(CREATE_URL_ENDPOINT, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept':       'application/json',
            },
            body: JSON.stringify(params),
        });

        if (!res.ok) {
            const err = await parseJsonSafely(res);
            throw new Error(err.error || `서버 오류 (HTTP ${res.status})`);
        }

        return res.json();
    }

    /**
     * 발급된 업로드 URL로 파일을 전송한다.
     * 업로드 진행률 이벤트가 필요하므로 fetch 대신 XMLHttpRequest를 사용한다.
     * @param {string} uploadUrl Kollus 일회용 업로드 URL
     * @param {File} file 전송할 파일
     * @param {string} uploadFileKey 완료 메시지에 표시할 업로드 파일 키
     * @returns {void}
     */
    function uploadFile(uploadUrl, file, uploadFileKey) {
        const formData = new FormData();
        formData.append('upload-file',       file);           // Kollus 규격: 파일 필드명 고정
        formData.append('accept',            'application/json');
        formData.append('disable_alert',     '1');
        formData.append('redirection_scope', 'no');

        pendingUploadFileKey = uploadFileKey;

        // [Kollus 업로드 서버 호출] 발급받은 upload_url로 브라우저가 파일을 직접 전송
        const xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', handleUploadProgress);
        xhr.addEventListener('load', handleUploadLoad);
        xhr.addEventListener('error', handleUploadError);
        xhr.addEventListener('abort', handleUploadAbort);
        xhr.open('POST', uploadUrl);
        xhr.send(formData);
    }

    /* --- 이벤트 핸들러 --- */

    /**
     * 패스스루 체크박스 토글: 제목 필드를 숨기고 인코딩 프로파일 키 필드를 노출한다 (해제 시 반대).
     * @returns {void}
     */
    function handlePassthroughToggle() {
        const checked = passthroughCheckbox.checked;
        fieldTitle.style.display      = checked ? 'none' : '';
        fieldProfileKey.style.display = checked ? '' : 'none';
        if (!checked) {
            profileKeyInput.value = '';
        }
    }

    /**
     * 파일 영역 클릭: 숨겨진 file input의 파일 선택 대화상자를 연다.
     * @returns {void}
     */
    function handleFileAreaClick() {
        fileInput.click();
    }

    /**
     * 파일 드래그 진입: 브라우저 기본 동작(파일 열기)을 막고 강조 스타일을 적용한다.
     * @param {DragEvent} e dragover 이벤트
     * @returns {void}
     */
    function handleDragOver(e) {
        e.preventDefault();
        fileArea.classList.add('drag-over');
    }

    /**
     * 파일 드래그 이탈: 강조 스타일을 제거한다.
     * @returns {void}
     */
    function handleDragLeave() {
        fileArea.classList.remove('drag-over');
    }

    /**
     * 파일 드롭: 첫 번째 파일을 선택 파일로 설정한다.
     * @param {DragEvent} e drop 이벤트
     * @returns {void}
     */
    function handleDrop(e) {
        e.preventDefault();
        fileArea.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) setFile(file);
    }

    /**
     * file input 변경: 선택된 파일명/용량을 표시한다.
     * @returns {void}
     */
    function handleFileChange() {
        if (fileInput.files[0]) setFile(fileInput.files[0]);
    }

    /**
     * XHR 업로드 진행률 이벤트: 진행률 바와 전송량 문구를 갱신한다.
     * @param {ProgressEvent} e upload progress 이벤트
     * @returns {void}
     */
    function handleUploadProgress(e) {
        if (!e.lengthComputable) return;
        const pct = Math.round((e.loaded / e.total) * PERCENT_MAX);
        setProgress(pct, `파일 전송 중... (${formatBytes(e.loaded)} / ${formatBytes(e.total)})`);
    }

    /**
     * XHR 완료: Kollus 응답(JSON result 또는 HTTP 상태)으로 성공/실패를 판단해 표시한다.
     * @param {ProgressEvent} e load 이벤트 (e.target이 완료된 XMLHttpRequest)
     * @returns {void}
     */
    function handleUploadLoad(e) {
        const xhr = e.target;
        const uploadFileKey = pendingUploadFileKey;
        submitBtn.disabled = false;
        setProgress(PERCENT_MAX, '완료');

        let result = null;
        try {
            result = JSON.parse(xhr.responseText);
        } catch {
            // Kollus 업로드 서버 응답이 JSON이 아닐 수 있음(리다이렉트 HTML 등) — 이 경우 HTTP 상태 코드만으로 판단
        }

        if ((result && result.result === 'S') || xhr.status === HTTP_OK) {
            showStatus('업로드가 완료되었습니다. 파일 키: ' + uploadFileKey, 'success');
            return;
        }

        const reason = result ? (result.message || '알 수 없는 오류') : 'HTTP ' + xhr.status;
        showStatus('업로드 실패: ' + reason, 'error');
    }

    /**
     * XHR 네트워크 오류: 오류 메시지를 표시하고 재시도할 수 있도록 버튼을 복구한다.
     * @returns {void}
     */
    function handleUploadError() {
        submitBtn.disabled = false;
        // CORS 오류 시 직접 업로드가 차단될 수 있음 — 서버 프록시 방식 고려 필요
        showStatus('네트워크 오류 또는 CORS 정책으로 업로드에 실패했습니다.', 'error');
    }

    /**
     * XHR 취소: 안내 메시지를 표시하고 버튼을 복구한다.
     * @returns {void}
     */
    function handleUploadAbort() {
        submitBtn.disabled = false;
        showStatus('업로드가 취소되었습니다.', 'info');
    }

    /**
     * 폼 제출: 입력 검증 → 서버에서 업로드 URL 발급 → Kollus 업로드 서버로 파일 직접 전송.
     * @param {SubmitEvent} e submit 이벤트
     * @returns {Promise<void>}
     */
    async function handleSubmit(e) {
        e.preventDefault();

        const isPassthrough = passthroughCheckbox.checked;
        const title         = titleInput.value.trim();
        const profileKey    = profileKeyInput.value.trim();
        const categoryKey   = categoryKeyInput.value.trim();
        const expireTime    = parseInt(expireTimeInput.value, 10);
        const file          = fileInput.files[0];

        if (!file) {
            showStatus('파일을 선택해 주세요.', 'error');
            return;
        }
        if (isPassthrough && !profileKey) {
            showStatus('인코딩 프로파일 키를 입력해 주세요.', 'error');
            return;
        }

        // UI 초기화
        statusMsg.style.display   = 'none';
        progressSec.style.display = 'block';
        setProgress(0, '업로드 URL 발급 중...');
        submitBtn.disabled = true;

        /* 1단계: 서버에서 업로드 URL 발급 */
        let uploadData;
        try {
            uploadData = await requestUploadUrl({
                title:          isPassthrough ? null : (title || null),
                file_name:      file.name,
                category_key:   categoryKey || null,
                expire_time:    expireTime,
                is_passthrough: isPassthrough ? 1 : 0,
                profile_key:    isPassthrough ? profileKey : null,
            });
        } catch (err) {
            // 네트워크 오류 또는 서버의 422(입력 오류)/503(Kollus API 오류) 응답 —
            // 사유를 보여주고 재시도할 수 있도록 버튼을 복구한 뒤 중단
            showStatus('업로드 URL 발급 실패: ' + err.message, 'error');
            submitBtn.disabled = false;
            return;
        }

        /* 2단계: Kollus 업로드 서버로 파일 직접 전송 */
        setProgress(0, '파일 전송 중...');
        uploadFile(uploadData.upload_url, file, uploadData.upload_file_key);
    }

    /* --- 이벤트 바인딩 --- */
    passthroughCheckbox.addEventListener('change', handlePassthroughToggle);
    fileArea.addEventListener('click', handleFileAreaClick);
    fileArea.addEventListener('dragover', handleDragOver);
    fileArea.addEventListener('dragleave', handleDragLeave);
    fileArea.addEventListener('drop', handleDrop);
    fileInput.addEventListener('change', handleFileChange);
    form.addEventListener('submit', handleSubmit);
}());
</script>
</body>
</html>
