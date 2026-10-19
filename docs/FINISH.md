# Final submission steps

**The technical work is complete:** code is published, the API is deployed, all four endpoints pass live checks, and the private S3 PDF is verified. Screenshots and a submission draft are prepared. Allow **5-10 minutes** to review, personalize the reflection, and submit through your course page.

## 1. Review the submission text

Open `docs/SUBMISSION.md` in this project or on GitHub:

https://github.com/gab26g/serverless-payroll-api/blob/main/docs/SUBMISSION.md

It contains the real repository URL, deployed API URL, successful tests, and the actual issue/resolution. Review the reflection draft and edit it so it describes what you personally learned.

## 2. Find your prepared files

Open File Explorer and paste this into its address bar:

```text
C:\Users\Gab\OneDrive - auf.edu.ph\Desktop\Documents\ChatGPT\AWS\output
```

- `payroll-submission.zip`: submission text, six required screenshots, successful deployment screenshot, raw live responses, and the actual downloaded PDF. It contains no AWS credentials.
- `evidence`: individual screenshots and JSON response files, if your course page wants separate uploads.
- `pdf/payslip-1-live.pdf`: the actual PDF downloaded from S3.

The packet is a starting point; if your course page requires a single PDF/Word document rather than a ZIP, combine the reviewed submission text and six screenshots into its required format. Do not upload the CloudShell deployment ZIP as your assignment evidence.

## 3. Submit on the assignment page

1. Open your course and this **Serverless Payroll Management API Using PHP and AWS Lambda** assignment.
2. Click **Submit Assignment**.
3. Paste the repository URL and deployed API base URL from SUBMISSION.md into the text/URL fields offered by the course page.
4. Upload `payroll-submission.zip` if ZIP uploads are accepted. Otherwise upload the six screenshots individually and paste the reviewed issue explanation and personalized reflection into the submission text area.
5. Click the final **Submit Assignment** or **Submit** button.
6. Confirm the page shows **Submitted** or a submission receipt.

Repository: https://github.com/gab26g/serverless-payroll-api

API base URL: https://51fjkdjyef.execute-api.ap-southeast-2.amazonaws.com

## 4. Keep the deployment available for grading

Keep the Lambda/API Gateway stack and S3 bucket intact until grading is complete. The ordinary S3 object URL is private; verify the PDF through authenticated S3 access. Follow the professor's cleanup instructions after grading. Removing the stack does not remove the separately created payslip bucket.
