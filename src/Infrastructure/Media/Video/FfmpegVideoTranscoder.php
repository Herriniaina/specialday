<?php

namespace App\Infrastructure\Media\Video;

use App\Application\Media\VideoTranscodeResult;
use App\Application\Media\VideoTranscoderInterface;
use Symfony\Component\Process\Process;

class FfmpegVideoTranscoder implements VideoTranscoderInterface
{
    #[\Override]
    public function transcode(string $localSourcePath, string $localOutputPath): VideoTranscodeResult
    {
        $encode = new Process([
            'ffmpeg', '-y', '-i', $localSourcePath,
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23',
            '-c:a', 'aac', '-b:a', '128k',
            // Moov atom en tête de fichier : lecture progressive dans le navigateur sans serveur de streaming dédié.
            '-movflags', '+faststart',
            $localOutputPath,
        ]);
        $encode->setTimeout(3600);
        $encode->mustRun();

        $probe = new Process(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', $localOutputPath]);
        $probe->mustRun();

        return new VideoTranscodeResult((int) ceil((float) trim($probe->getOutput())));
    }
}
