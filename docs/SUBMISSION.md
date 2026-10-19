# Submission draft - complete pending fields before submitting

Repository URL: https://github.com/gab26g/serverless-payroll-api

Deployed API base URL: **PENDING - credentials and the Sydney bucket are verified; professor-approved deployment permissions are still required.**

## Evidence

- [ ] Successful GET `/api/employees` screenshot
- [ ] Successful GET `/api/reports/payroll` screenshot
- [ ] Successful GET `/api/payslips/1` screenshot
- [ ] Successful POST `/api/payslips/1/generate` screenshot
- [ ] S3 console showing `payslips/1.pdf`
- [ ] Readable downloaded PDF

## Actual issue encountered and resolution

During deployment packaging, Serverless could not resolve `PAYSLIP_BUCKET` because the environment variable had not been set. The same value is needed by the Lambda environment and the bucket-scoped upload policy. Setting `PAYSLIP_BUCKET` before running the packaging command resolved the configuration error and the deployment ZIP built successfully. The placeholder used for offline verification must be replaced by the actual bucket name before live deployment. Live deployment and upload verification remain pending; this paragraph does not claim that they have already succeeded.

After deploying, update this explanation with any actual AWS issue and its verified resolution, or retain this packaging issue while replacing the pending-status sentence with the real outcome.

## Reflection - write in your own words

Use 4-6 sentences covering:

1. How CSV records become JSON API responses.
2. How payroll IDs join to employee details and why integer cents avoid rounding errors.
3. How Bref lets PHP run on Lambda through API Gateway.
4. How Dompdf and S3 work together to produce and store a payslip.
5. The difference between deployment credentials and the Lambda execution role.
6. What you personally found difficult or would improve.

Do not submit these prompts as your reflection. Describe what you actually learned and tested.
