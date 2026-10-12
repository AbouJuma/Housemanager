<?php

namespace App\Http\Controllers;

use App\Core\Support\AppRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Publishing the Android app: the super admin uploads a new APK here and
 * the sign-in pages' download card starts offering it straight away.
 */
class AppReleaseController extends Controller
{
    /** Public - the newest APK, for the download card under sign-in. */
    public function download(): BinaryFileResponse
    {
        $release = AppRelease::latest();
        abort_unless($release, 404);

        return response()->download($release['path'], AppRelease::downloadName($release), [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->isSaasOwner(), 403);

        return view('app-releases.index', [
            'releases' => AppRelease::all(),
            'maxUploadMb' => $this->maxUploadMb(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSaasOwner(), 403);

        $data = $request->validate([
            'apk' => ['required', 'file', 'extensions:apk', 'max:204800'],
            'version' => ['required', 'string', 'max:20', 'regex:/^\d+\.\d+(\.\d+)?$/'],
        ], [
            'version.regex' => __('Use a version like 1.0.2'),
            'apk.extensions' => __('Choose the .apk file of the app.'),
        ]);

        $name = AppRelease::store($data['apk'], $data['version']);

        return redirect()->route('app-releases.index')->with('success', __('Version :version is now available to download (:file).', ['version' => $data['version'], 'file' => $name]));
    }

    /** What this server accepts per upload (the host's PHP limits). */
    protected function maxUploadMb(): int
    {
        $toMb = function (string $value): int {
            $number = (int) $value;

            return match (strtolower(substr(trim($value), -1))) {
                'g' => $number * 1024,
                'k' => (int) ceil($number / 1024),
                'm' => $number,
                default => (int) ceil($number / 1048576),
            };
        };

        return min($toMb((string) ini_get('upload_max_filesize')), $toMb((string) ini_get('post_max_size')));
    }
}
