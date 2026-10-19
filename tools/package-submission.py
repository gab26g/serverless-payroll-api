"""Package only verified live evidence and submission text; never credentials."""
from __future__ import annotations

import json
from pathlib import Path
import zipfile

root = Path(__file__).resolve().parent.parent
evidence = root / 'output/evidence'
files = {
    'SUBMISSION.md': root / 'docs/SUBMISSION.md',
    'FINISH.md': root / 'docs/FINISH.md',
    'DEPLOYMENT-STATUS.md': root / 'docs/DEPLOYMENT-STATUS.md',
    'payslip-1-live.pdf': root / 'output/pdf/payslip-1-live.pdf',
}
for name in ['00-deployment.jpg', '01-employees.jpg', '02-payroll-report.jpg', '03-payslip.jpg', '04-generate.jpg', '05-s3-object.jpg', '06-pdf.png']:
    files[f'screenshots/{name}'] = evidence / name
for name in ['employees.json', 'payroll-report.json', 'payslip.json', 'generate.json']:
    path = evidence / name
    data = json.loads(path.read_text(encoding='utf-8-sig'))
    if data.get('success') is not True:
        raise SystemExit(f'Not a successful live response: {name}')
    files[f'responses/{name}'] = path
for destination, path in files.items():
    if not path.is_file() or path.stat().st_size == 0:
        raise SystemExit(f'Missing evidence: {path}')
    if destination.endswith('.jpg') and not path.read_bytes().startswith(b'\xff\xd8\xff'):
        raise SystemExit(f'Not a JPEG screenshot: {path}')
    if destination.endswith('.pdf') and not path.read_bytes().startswith(b'%PDF-'):
        raise SystemExit(f'Not a PDF: {path}')

output = root / 'output/payroll-submission.zip'
with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED) as archive:
    for destination, path in files.items():
        archive.write(path, destination)
    archive.writestr('READ-ME-FIRST.txt', (
        'Repository: https://github.com/gab26g/serverless-payroll-api\n'
        'API: https://51fjkdjyef.execute-api.ap-southeast-2.amazonaws.com\n\n'
        'Screenshots and response JSON are from the deployed API and AWS console.\n'
        '01 = GET /api/employees (first records of the full response visible)\n'
        '02 = GET /api/reports/payroll\n03 = GET /api/payslips/1\n'
        '04 = POST /api/payslips/1/generate\n05 = S3 object overview\n'
        '06 = actual downloaded S3 PDF rendered as an image\n'
        '00 = successful CloudShell deployment\n\n'
        'Review and personalize the reflection in SUBMISSION.md before submitting.\n'
        'If you edit the source docs, rerun tools/package-submission.py to refresh this ZIP.\n'
        'Use the course page final Submit button and verify the submission receipt.\n'
    ))
with zipfile.ZipFile(output) as archive:
    if archive.testzip() is not None:
        raise SystemExit('ZIP integrity check failed.')
    print('Verified packet entries:', len(archive.namelist()))
print(output)
print('Packet includes no credentials, local-only error responses, or deployment bundle.')
