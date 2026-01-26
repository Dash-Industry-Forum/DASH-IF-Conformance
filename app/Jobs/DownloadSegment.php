<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DownloadSegment implements ShouldQueue
{
    use Queueable;

    protected string $url;
    protected string $targetPath;

    /**
     * Create a new job instance.
     */
    public function __construct(string $url, string $targetPath)
    {
        $this->url = $url;
        $this->targetPath = $targetPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fp = fopen($this->targetPath, "w+");
        if (!$fp) {
            Log::error("Unable to open targetPath $this->targetPath");
            return;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url,
            CURLOPT_FAILONERROR => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_MAXFILESIZE => 100000000, //100mb
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
            CURLOPT_FILE => $fp
        ));

        $result = curl_exec($curl);
        fclose($fp);

        file_put_contents($this->targetPath . ".source", $this->url);

        if (curl_errno($curl) == CURLE_FILESIZE_EXCEEDED) {
            touch($this->targetPath . ".exceeded");
            $fp = fopen($this->targetPath, "w");
            fclose($fp);
        }
        if (curl_errno($curl) != CURLE_OK) {
            touch($this->targetPath . ".failed");
        }
        unlink($this->targetPath . ".queued");
    }
}
