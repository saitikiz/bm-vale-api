<?php

namespace App\Support;

/**
 * İş katmanı için tek tip sonuç sözleşmesi.
 *
 * Tüm servis/işlemci metodları başarı/başarısızlığı bu nesneyle döner.
 * Hata mesajı her zaman `reason`'da, ek bağlam `detail`'de toplanır;
 * böylece `last_error` tek bir yerden (`lastError()`) üretilir.
 */
class Result
{
    private function __construct(
        public readonly bool $ok,
        public readonly ?string $reason,
        public readonly array $detail,
        public readonly array $data,
    ) {}

    public static function ok(?string $reason = null, array $data = []): self
    {
        return new self(true, $reason, [], $data);
    }

    public static function fail(string $reason, array $detail = []): self
    {
        return new self(false, $reason, $detail, []);
    }

    /**
     * last_error kolonuna/loga yazılacak mesaj. Başarılıysa null.
     */
    public function lastError(): ?string
    {
        if ($this->ok) {
            return null;
        }

        $message = $this->reason;

        if (!empty($this->detail)) {
            $message .= ' | ' . json_encode($this->detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $message;
    }
}
