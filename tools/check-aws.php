<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Prevent long metadata-server probes on a student's local computer.
putenv('AWS_EC2_METADATA_DISABLED=true');
try {
    $client = new Aws\Sts\StsClient([
        'version'=>'2011-06-15',
        'region'=>getenv('AWS_REGION') ?: 'us-east-1',
        'retries'=>0,
        'http'=>['connect_timeout'=>5, 'timeout'=>15],
    ]);
    $identity = $client->getCallerIdentity();
    if ($identity['Account'] !== '408852558600') {
        fwrite(STDERR, "Credentials belong to a different AWS account; deployment stopped.\n");
        exit(1);
    }
    echo "AWS identity verified for the school account.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "AWS credentials could not be verified. Configure the payroll-lab profile, check temporary credential expiry and network access. No credential values were printed.\n");
    exit(1);
}
