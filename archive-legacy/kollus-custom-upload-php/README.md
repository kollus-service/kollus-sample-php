# Kollus Custom Upload By PHP

Upload media by Kollus Http-endpoint API : Sample Source

## Requirement

* [php](http://php.net) : 5.5 above
   * module
      * [slimphp](https://www.slimframework.com/) : for sample code's web framework
      * [slim php-view](https://github.com/slimphp/PHP-View)
      * [guzzle php http client](http://docs.guzzlephp.org/)
* [jQuery](https://jquery.com) : 3.2.1
   * [Kollus Custom Upload By jQuery](https://github.com/kollus-service/kollus-custom-upload-jquery) library
* [Boostrap 3](https://getbootstrap.com/docs/3.3/) : for sample code
      
## Installation

```bash
git clone https://github.com/kollus-service/kollus-custom-upload-php
cd kollus-custom-upload-php

composer install
```
Copy .config.yml to config.yml and Edit this.

```yaml
kollus:
  domain: [kollus domain]
  version: 0
  service_account:
    key : [service account key]
    api_access_token: [api access token]
    custom_key: [custom key]
    security_key: [security key]
```

## How to use

```bash
composer start

...
> php -S 0.0.0.0:8080 -t public public/index.php
```

Open browser '[http://localhost:8080](http://localhost:8080)'

## You must use modern browser

* IE 10 above and other latest browser is best
* Don't use 'iframe upload' and 'kollus progress api'

## Development flow

1. Request local server api for create 'upload url' on browser
   * '/api/upload/create_url' in public/index.php 
2. Local server call kollus api and create 'kollus upload url'
   * use get_upload_url_response in src/Client/ApiClient.php
3. Upload file to 'kollus upload url'
   * use upload-file event in public/js/default.js

### Important code

public/index.php

```php
$app->post('/api/upload/create_url', function (Request $request, Response $response) use ($container) {
    $kollusApiClient = $container->get('kollusApiClient');
    /** @var \Kollus\Component\Client\ApiClient $kollusApiClient */

    $postParams = $request->getParsedBody();

    $categoryKey = empty($postParams['category_key']) ? null : $postParams['category_key'];
    $isEncryptionUpload = empty($postParams['use_encryption']) ? null : $postParams['use_encryption'];
    $isAudioUpload = empty($postParams['is_audio_upload']) ? null : $postParams['is_audio_upload'];
    $title = empty($postParams['title']) ? null : $postParams['title'];

    $apiResponse = $kollusApiClient->getUploadURLResponse(
        $categoryKey,
        $isEncryptionUpload,
        $isAudioUpload,
        $title
    );

    return $response->withJson(['result' => $apiResponse], 200);
})->setName('api-upload-create-url');
```

src/Client/ApiClient.php

```php
    /**
     * @param string|null $categoryKey
     * @param bool|false $useEncryption
     * @param bool|false $isAudioUpload
     * @param string $title
     * @param int $expireTime
     * @return object
     * @throws ClientException
     */
    public function getUploadURLResponse(
        $categoryKey = null,
        $useEncryption = false,
        $isAudioUpload = false,
        $title = '',
        $expireTime = 600
    ) {
        $postParams = [
            'access_token' => $this->serviceAccount->getApiAccessToken(),
            'category_key' => $categoryKey,
            'expire_time' => $expireTime,
            'is_encryption_upload' => (bool)$useEncryption,
            'is_audio_upload' => (bool)$isAudioUpload,
            'title' => (empty($title) ? null : $title)
        ];

        $response = $this->getResponseJSON('POST', 'media_auth/upload/create_url.json', [], $postParams);
        if (!isset($response->result)) {
            throw new ClientException('Response is invalid.');
        }

        return (object)$response->result;
    }
```

public/js/default.js

```javascript
/**
 * Kollus Upload JS by JQuery
 *
 * Upload event handler
 */
$(document).on('click', 'button[data-action=upload-file]', function (e) {
        ...
        $.post(
            createUploadApiUrl,
            apiData,
            function (data) {
                var formData = new FormData(),
                    progress = $('<div class="progress" />'),
                    progressBar,
                    repeator;

                if (('error' in data && data.error) ||
                    !('result' in data) ||
                    !('upload_url' in data.result) ||
                    !('progress_url' in data.result)) {
                    showAlert('danger', ('message' in data ? data.message : 'Api response error.'));
                }

                uploadUrl = data.result.upload_url;
                progressUrl = data.result.progress_url;
                uploadFileKey = data.result.upload_file_key;

                progress.addClass('progress-' + uploadFileKey);
                progressBar = $('<div class="progress-bar" />').attr('aria-valuenow', 0);
                progressBar.attr('role', 'progressbar')
                    .attr('aria-valuenow', 0).attr('aria-valuemin', 0).css('min-width', '2em').text('0%');
                progress.append(progressBar);
                progress.insertBefore(uploadFileInput);

                uploadFileInput.val('').clone(true);
                formData.append('upload-file', uploadFile);

                $.ajax({
                    url: uploadUrl,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    xhr: function () {
                        var xhr = new XMLHttpRequest();

                        if (!forceProgressApi && supportAjaxUploadProgress()) {
                            xhr.upload.addEventListener('progress', function (e) {

                                if (e.lengthComputable) {
                                    progressValue = Math.ceil((e.loaded / e.total) * 100);

                                    if (progressValue > 0) {
                                        progressBar.attr('arial-valuenow', progressValue);
                                        progressBar.width(progressValue + '%');

                                        if (progressValue > 10) {
                                            progressBar.text(progressValue + '% - ' + uploadFile.name);
                                        } else {
                                            progressBar.text(progressValue + '%');
                                        }
                                    }
                                }
                            }, false);
                        } else {
                            ... // only modern browser
                        }

                        return xhr;
                    }, // xhr
                    success: function (data) {
                        progressBar.attr('aria-valuenow', 100);
                        progressBar.width('100%');
                        progressBar.text(uploadFile.name + ' - 100%');
                        if ('error' in data && data.error) {
                            showAlert('danger', ('message' in data ? data.message : 'Api response error.'));
                        } else {

                            if ('message' in data) {
                                showAlert('success', data.message + ' - ' + uploadFile.name);
                            }
                        }
                    },
                    error: function (jqXHR) {
                        try {
                            data = jqXHR.length === 0 ? {} : $.parseJSON(jqXHR.responseText);
                        } catch (err) {
                            data = {};
                        }

                        showAlert('danger', ('message' in data ? data.message : 'Ajax response error.') + ' - ' + uploadFile.name);
                    },
                    complete: function () {
                        clearInterval(repeator);
                        $(self).attr('disabled', false);

                        // after complate
                        AfterComplateUpload(5000, 10000);

                        progress.delay(2000).fadeOut(500);
                    }
                }); // $.ajax
            }, // function(data)
            'json'
        ); // $.post
        ...
});
```

## License
See `LICENSE` for more information
