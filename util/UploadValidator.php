<?php

namespace saso\util;

use saso\util\monad\Either;

/**
 * Validate an entry from $_FILES against a small allow-list of image types.
 *
 * Pre-M1 the upload code only checked $_FILES[..]['type'], a value taken
 * verbatim from the client and trivially forgeable. This helper rebuilds the
 * MIME type from the file's actual contents via finfo_file(), confirms the
 * upload arrived through the SAPI (is_uploaded_file), enforces a byte ceiling,
 * and runs getimagesize() so we reject content that has the right magic bytes
 * but is not actually decodable as an image.
 *
 * The result is an Either<array{tmp_name,mimeType,size,extension}> — callers
 * propagate the failure via the framework's existing Either chain.
 */
final class UploadValidator
{
    /**
     * @param array<string,mixed> $file A single $_FILES[...] entry.
     * @param list<string> $allowedMimes e.g. ['image/png','image/jpeg','image/gif']
     * @param int $maxBytes reject anything strictly larger
     *
     * On success the returned Either wraps an array shaped as
     * {tmp_name: string, mimeType: string, size: int, extension: string}.
     * The Either class is not generic in this codebase, so the shape is
     * documented in prose rather than a generic type parameter.
     */
    public static function validateImageUpload(
        array $file,
        array $allowedMimes,
        int $maxBytes = 5 * 1024 * 1024,
    ): Either {
        // 1. Required keys present and the upload completed without an error.
        if (!isset($file['tmp_name'], $file['size'], $file['error'])) {
            return Either::left('upload payload missing');
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return Either::left('upload error code '.(int) $file['error']);
        }

        $tmpName = (string) $file['tmp_name'];
        $size    = (int) $file['size'];

        // 2. The path must come from the SAPI's upload handling — defends
        //    against caller-supplied paths to arbitrary files on disk.
        if (!is_uploaded_file($tmpName)) {
            return Either::left('not an uploaded file');
        }

        // 3. Size ceiling. PHP also enforces upload_max_filesize / post_max_size,
        //    but those produce different error codes; an explicit application
        //    limit gives consistent rejection.
        if ($size <= 0 || $size > $maxBytes) {
            return Either::left('upload size out of range');
        }

        // 4. MIME from the file's actual bytes — never trust $file['type'].
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($tmpName);
        if ($detectedMime === false || !in_array($detectedMime, $allowedMimes, true)) {
            return Either::left('disallowed mime '.($detectedMime ?: 'unknown'));
        }

        // 5. Confirm it actually decodes as an image — this catches polyglots
        //    and HTML/SVG payloads that pass an image MIME sniff.
        $info = @getimagesize($tmpName);
        if ($info === false || empty($info[0]) || empty($info[1])) {
            return Either::left('not a decodable image');
        }
        if (!in_array($info['mime'], $allowedMimes, true)) {
            return Either::left('image type mismatch');
        }

        // 6. Derive a safe extension from the detected MIME so downstream
        //    consumers never echo an attacker-controlled filename.
        $extension = self::extensionForMime($detectedMime);

        return Either::of([
            'tmp_name'  => $tmpName,
            'mimeType'  => $detectedMime,
            'size'      => $size,
            'extension' => $extension,
        ]);
    }

    private static function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'bin',
        };
    }
}
