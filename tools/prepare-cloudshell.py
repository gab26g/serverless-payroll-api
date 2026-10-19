"""Bundle an already-built Serverless package for a PHP-free CloudShell deploy."""
from __future__ import annotations

import hashlib
import json
from pathlib import Path
import zipfile


root = Path(__file__).resolve().parent.parent
package = root / '.serverless'
artifact = package / 'gab-payroll-api.zip'
if not artifact.is_file():
    raise SystemExit('Build the Sydney package first: npm.cmd run package -- --region ap-southeast-2')

state = json.loads((package / 'serverless-state.json').read_text(encoding='utf-8'))
service = state['service']
if service['service'] != 'gab-payroll-api' or service['provider']['region'] != 'ap-southeast-2':
    raise SystemExit('Package must target gab-payroll-api in ap-southeast-2.')
if service['provider']['environment']['PAYSLIP_BUCKET'] != 'sam10-payroll-payslips-tabing':
    raise SystemExit('Package points to the wrong payslip bucket.')

# The package was built on Windows. The deployer must resolve Linux-relative paths.
service['artifact'] = '.serverless/gab-payroll-api.zip'
service.setdefault('package', {})['artifact'] = '.serverless/gab-payroll-api.zip'
service['initialServerlessConfig']['package']['artifact'] = '.serverless/gab-payroll-api.zip'
state['package']['artifact'] = 'gab-payroll-api.zip'

prefix = 'gab-payroll-cloudshell-01a15376'
output = root / 'output' / f'{prefix}.zip'
output.parent.mkdir(parents=True, exist_ok=True)

with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED) as archive:
    for name in ['serverless.yml', 'package.json', 'package-lock.json', 'composer.json', 'composer.lock']:
        archive.write(root / name, f'{prefix}/{name}')
    # Only the Bref Node deployment plugin is needed outside the PHP runtime ZIP.
    for path in sorted((root / 'vendor/bref/bref').rglob('*')):
        if path.is_file():
            archive.write(path, f'{prefix}/{path.relative_to(root).as_posix()}')
    for name in ['cloudformation-template-create-stack.json', 'cloudformation-template-update-stack.json', 'gab-payroll-api.zip']:
        archive.write(package / name, f'{prefix}/.serverless/{name}')
    archive.writestr(f'{prefix}/.serverless/serverless-state.json', json.dumps(state, indent=2))

print(output)
print('SHA256:', hashlib.sha256(output.read_bytes()).hexdigest())
print('Includes the prepared PHP runtime ZIP; excludes credentials, local outputs and node_modules.')
