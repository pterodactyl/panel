<?php

declare(strict_types=1);

namespace App\Services {
    final class ReportBuilder
    {
        public static function make(): self
        {
            return new self; // own static constructor: fine
        }
    }
}

namespace App\Http\Controllers {
    use App\Services\ReportBuilder;

    final class ReportController
    {
        public function __construct(private readonly ReportBuilder $injected) {}

        public function direct(): ReportBuilder
        {
            return new ReportBuilder; // error: line 24
        }

        public function viaApp(): ReportBuilder
        {
            return app(ReportBuilder::class); // error: line 29
        }

        public function viaResolve(): ReportBuilder
        {
            return resolve(ReportBuilder::class); // error: line 34
        }

        public function injected(): ReportBuilder
        {
            return $this->injected;
        }

        public function otherClass(): \DateTimeImmutable
        {
            return new \DateTimeImmutable; // not a service namespace: fine
        }
    }
}
