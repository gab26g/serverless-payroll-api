<?php
declare(strict_types=1);

namespace Payroll;

use Closure;
use RuntimeException;
use Throwable;

final class Api
{
    public function __construct(private PayrollRepository $repository, private ?Closure $generate = null) {}

    public function handle(string $method, string $uri): array
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';
        $isPayslip = preg_match('#^/api/payslips/([^/]+)(/generate)?$#D', $path, $matches) === 1;
        $readRoute = in_array($path, ['/api/employees', '/api/reports/payroll'], true);
        if (!$isPayslip && !$readRoute) { return self::error(404, 'Endpoint not found.'); }
        $expectedMethod = $isPayslip && isset($matches[2]) ? 'POST' : 'GET';
        if ($method !== $expectedMethod) {
            $response = self::error(405, 'Method not allowed.');
            $response['headers']['Allow'] = $expectedMethod;
            return $response;
        }
        try {
            if ($path === '/api/employees') { return self::success($this->repository->employees()); }
            if ($path === '/api/reports/payroll') { return self::success($this->repository->report()); }
            $rawId = $matches[1];
            if (!preg_match('/^[1-9][0-9]*$/D', $rawId) || filter_var($rawId, FILTER_VALIDATE_INT) === false) {
                return self::error(400, 'Payroll ID must be a positive integer without leading zeros.');
            }
            $slip = $this->repository->payslip((int) $rawId);
            if ($slip === null) { return self::error(404, 'Payroll record not found.'); }
            if ($expectedMethod === 'GET') { return self::success($slip); }
            if ($this->generate === null) { throw new RuntimeException('PDF generation is not configured.'); }
            $response = self::success(($this->generate)($slip));
            $response['body']['message'] = 'Payslip generated and uploaded.';
            return $response;
        } catch (Throwable $error) {
            // Log only the exception type; SDK diagnostics can contain credentials or signed URLs.
            error_log('Payroll request failed: ' . get_class($error));
            return self::error(500, 'Unable to process request. Check application configuration and permissions.');
        }
    }

    private static function success(array $data): array
    {
        return ['status'=>200, 'headers'=>['Content-Type'=>'application/json; charset=utf-8'], 'body'=>['success'=>true, 'data'=>$data]];
    }

    public static function error(int $status, string $message): array
    {
        return ['status'=>$status, 'headers'=>['Content-Type'=>'application/json; charset=utf-8'], 'body'=>['success'=>false, 'message'=>$message]];
    }
}
