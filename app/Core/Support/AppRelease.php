<?php

namespace App\Core\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * The Android app offered for download under the sign-in pages.
 *
 * Releases are plain .apk files in storage/app/releases - uploaded from
 * the super admin's "Mobile App" page, or copied there with the hosting
 * File Manager. The newest file is always the one served, so publishing
 * an update is just adding the new APK. The version comes from the file
 * name (e.g. HouseManagers-v1.0.1.apk).
 */
class AppRelease
{
    /** Older releases kept for rollback; anything beyond is deleted. */
    public const KEEP = 3;

    public static function directory(): string
    {
        return storage_path('app/releases');
    }

    /** @return array{path: string, file: string, version: ?string, size_mb: float, uploaded_at: Carbon}|null */
    public static function latest(): ?array
    {
        return self::all()[0] ?? null;
    }

    /** @return list<array{path: string, file: string, version: ?string, size_mb: float, uploaded_at: Carbon}> newest first */
    public static function all(): array
    {
        if (! File::isDirectory(self::directory())) {
            return [];
        }

        return collect(File::files(self::directory()))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'apk')
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'path' => $file->getPathname(),
                'file' => $file->getFilename(),
                'version' => self::versionFrom($file->getFilename()),
                'size_mb' => round($file->getSize() / 1048576, 1),
                'uploaded_at' => Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->values()
            ->all();
    }

    public static function versionFrom(string $fileName): ?string
    {
        return preg_match('/v?(\d+\.\d+(?:\.\d+)?)/i', $fileName, $m) ? $m[1] : null;
    }

    /** Store an uploaded APK as the newest release; returns its file name. */
    public static function store(UploadedFile $apk, string $version): string
    {
        File::ensureDirectoryExists(self::directory());
        $newestBefore = collect(self::all())->max(fn (array $release) => $release['uploaded_at']->getTimestamp()) ?? 0;
        $name = 'HouseManagers-v'.preg_replace('/[^0-9.]/', '', $version).'.apk';
        $apk->move(self::directory(), $name);
        // Strictly newer than anything already there, even within the same second.
        touch(self::directory().DIRECTORY_SEPARATOR.$name, max(time(), $newestBefore + 1));
        clearstatcache();

        foreach (array_slice(self::all(), self::KEEP) as $old) {
            File::delete($old['path']);
        }

        return $name;
    }

    /** The name the download is saved as on the phone. */
    public static function downloadName(array $release): string
    {
        return 'HouseManagers'.($release['version'] ? '-v'.$release['version'] : '').'.apk';
    }
}
