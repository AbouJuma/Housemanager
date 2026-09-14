<?php

namespace App\Core\Tenancy;

final class CompanyContext
{
    private ?int $companyId = null;

    public function set(?int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function get(): ?int
    {
        return $this->companyId;
    }

    public function clear(): void
    {
        $this->companyId = null;
    }

    public static function id(): ?int
    {
        return app(self::class)->get();
    }
}
