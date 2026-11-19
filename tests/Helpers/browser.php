<?php

declare(strict_types=1);

use App\Modules\Users\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The plugin's in-process server keeps a multipart part named `files[]`
 * flat where PHP would nest it (see its LaravelHttpServer::parseMultipartBody),
 * so Livewire's upload endpoint reads no files and every upload fails. This
 * nests them back for the browser suite only; the app never sees it.
 */
final class NestBrowserUploads
{
    public function handle(Request $request, Closure $next): mixed
    {
        foreach ($request->files->all() as $key => $file) {
            if (is_string($key) && str_ends_with($key, '[]')) {
                $request->files->remove($key);
                $request->files->set(substr($key, 0, -2), [$file]);
            }
        }

        return $next($request);
    }
}

/**
 * Call once in a browser test before uploadThroughFilePond(). Livewire parks
 * uploads on `tmp-for-tests` while tests run, so that disk is faked too; fake
 * whichever disk the component finally stores on in the test itself.
 */
function allowBrowserUploads(): void
{
    app(Kernel::class)->prependMiddleware(NestBrowserUploads::class);
    Storage::fake('tmp-for-tests');
}

/**
 * Logs in through the real panel form rather than actingAs(), so every flow
 * also proves the login screen works for that role.
 *
 * `form.email` is an id with a dot, which the plugin would read as a CSS
 * class selector, and a bare press('Sign in') matches the page heading
 * before the button.
 */
function submitPanelLogin(string $panel, string $email, string $password = 'password')
{
    return visit("/{$panel}/login")
        ->fill('[id="form.email"]', $email)
        ->fill('[id="form.password"]', $password)
        ->click('button[type="submit"]');
}

function loginThroughPanel(User $user, string $panel = 'admin', string $password = 'password')
{
    return waitForTextIn(submitPanelLogin($panel, $user->email, $password), 'h1', 'Dashboard');
}

/**
 * Polls until an element matching the selector shows the text. The plugin's
 * waitForText() does not poll — it passes only if the text is already there
 * — and it matches any case-insensitive substring, so it can pass on a stale
 * toast or a modal heading before the step's effect has landed.
 */
function waitForTextIn($page, string $selector, string $text, int $timeoutMs = 9000)
{
    $found = $page->script(sprintf(<<<'JS'
        new Promise(resolve => {
            const deadline = Date.now() + %d;
            const check = () => {
                const hit = [...document.querySelectorAll(%s)].some(el => el.innerText.includes(%s));
                if (hit || Date.now() > deadline) return resolve(hit);
                setTimeout(check, 50);
            };
            check();
        })
        JS, $timeoutMs, json_encode($selector), json_encode($text)));

    if ($found !== true) {
        $page->screenshot(filename: 'wait-failed-'.md5($selector.$text));
    }

    expect($found)->toBeTrue("No [{$selector}] showed [{$text}] within {$timeoutMs}ms.");

    return $page;
}

/**
 * Attaches a file through FilePond and waits until it has been sent. A file
 * set on the input before FilePond has built its root is silently dropped,
 * which is how a slow parallel run loses the upload. The browser can only
 * attach a real path on disk, so pass a file you wrote, not an UploadedFile.
 */
function uploadThroughFilePond($page, string $scope, string $path)
{
    waitForTextIn($page, $scope.' .filepond--root', 'Browse');
    $page->attach($scope.' input[type="file"]', $path);

    return waitForTextIn($page, $scope.' .filepond--root', 'Upload complete');
}
