# Finish the activity

The application and local tests are ready. These steps complete the account-dependent work. Budget **45-75 minutes**, plus any wait for instructor permissions. Do not submit placeholder URLs or local-only results as deployed evidence.

## 1. Create the bucket (you, 5-10 minutes)

1. Sign in at https://408852558600.signin.aws.amazon.com/console using the assigned school login.
2. Choose your instructor's region in the upper-right Region menu; otherwise choose **US East (N. Virginia)**.
3. In the top search bar, type **S3**, then select **S3**.
4. Click **General purpose buckets** > **Create bucket**.
5. Use a globally unique lowercase name, for example `sam10-payroll-payslips-20261019`, adding digits if already taken.
6. Select the same region. Keep **ACLs disabled**, **Block all public access** enabled, and **SSE-S3** encryption.
7. Click **Create bucket**. Record the exact name and region; these are safe to share with the coding assistant.

## 2. Configure credentials privately (you, 10-15 minutes)

1. In AWS, click your username/account menu at the upper right > **Security credentials**.
2. Scroll to **Access keys** > **Create access key** and follow the command-line use-case prompts. If blocked, request the approved credentials or deployment method from your instructor; keep existing keys.
3. Open File Explorer and navigate to this project folder, `Documents\ChatGPT\AWS` under your OneDrive Desktop folder.
4. Right-click an empty area of the folder > **Open in Terminal**. If unavailable, click the File Explorer address bar, type `powershell`, and press Enter.
5. Run `.\tools\configure-aws.ps1`. Enter the key ID and secret at its hidden prompts. Enter a session token only for temporary credentials.
6. If PowerShell blocks scripts, use a temporary policy for this terminal only: `Set-ExecutionPolicy -Scope Process Bypass`, then rerun the helper. Close the terminal afterward to discard that policy setting.

Credentials are written outside the repository, under your user profile. Do not send them in chat, put them in the project, or photograph the AWS key-retrieval page.

## 3. Deploy (assistant or you, 10-20 minutes)

Tell the assistant the bucket name, region, and that the local profile is ready. It can continue deployment and diagnose errors. Or run:

```powershell
.\tools\deploy.ps1 -Bucket YOUR-ACTUAL-BUCKET -Region us-east-1
```

Wait for completion. Copy the printed `https://...execute-api...amazonaws.com` HTTP endpoint as your **API base URL**.

If AWS reports **AccessDenied**, copy only the action name and non-secret error text. Your instructor may need to grant the denied action or supply an execution role. Do not assume a Lambda upload policy also grants the IAM user permission to deploy.

## 4. Test and capture evidence (shared, 15-20 minutes)

Run:

```powershell
.\tools\smoke-test.ps1 -BaseUrl https://YOUR-ACTUAL-API-URL -GeneratePdf
```

For easy screenshots of the GET endpoints, paste each complete URL into a browser address bar:

- `BASE-URL/api/employees`
- `BASE-URL/api/reports/payroll`
- `BASE-URL/api/payslips/1`

For the POST endpoint, run this in the terminal and capture both the command and output:

```powershell
curl.exe -i -X POST "BASE-URL/api/payslips/1/generate"
```

Replace `BASE-URL` with the real base URL everywhere. Press **Windows + Shift + S** for each screenshot, select the relevant area, then click the screenshot notification and save it. Capture the browser URL or terminal command and successful JSON. Keep credential screens out of screenshots.

Next, open AWS > **S3** > your bucket > **Objects** > **payslips/** > **1.pdf**. Capture its existence. Click **Download**, open the PDF, and capture its readable contents. The ordinary object URL may return AccessDenied in an unsigned browser because the bucket is private; that is expected.

Expected payroll #1: employee **Deniz Fischer**, employee ID **319**, gross **14,131.54**, deductions **12,626.36**, net **1,505.18**. The source dates are **2023-10-14** and **2023-05-06**; they are preserved, not corrected.

Save these six screenshots under `output/evidence/`:

1. `01-employees.png`
2. `02-payroll-report.png`
3. `03-payslip.png`
4. `04-generate.png`
5. `05-s3-object.png`
6. `06-pdf.png`

If a deployed request returns 500: AWS search **Lambda** > **Functions** > function whose name starts with `sam10-payroll-api` > **Monitor** > **View CloudWatch logs** > latest stream. A missing bucket setting, denied PutObject, or incorrect region needs configuration/permission correction and a retry.

## 5. Publish to GitHub (shared, 5-10 minutes)

1. Sign in at https://github.com.
2. Click **+** at upper right > **New repository**.
3. Set the name to `serverless-payroll-api`. Choose the instructor-required visibility.
4. Leave **Add a README**, **Add .gitignore**, and **Choose a license** unchecked; the local project supplies its content.
5. Click **Create repository** and send the repository URL to the assistant. It can check the source for secrets and push once Git authentication is available.
6. Verify the repository shows `src/`, `index.php`, `serverless.yml`, both CSVs, README, and lockfiles. If private, ensure the instructor can access it.

## 6. Complete and submit (you, 5-10 minutes)

Open `docs/SUBMISSION.md`. Replace the pending URLs, review the actual issue explanation, and write your own reflection using its prompts.

On the course assignment page:

1. Click **Submit Assignment**.
2. Choose the text/URL or file-upload option offered by the course page.
3. Provide the repository URL, deployed API base URL, six screenshots, issue explanation and reflection. If only one upload is allowed, place the text and screenshots into one document.
4. Click the final **Submit Assignment** or **Submit** button.
5. Confirm the page shows **Submitted** or a submission receipt.

Keep AWS resources available until grading. After grading, follow the instructor's cleanup instructions; removing the Serverless stack does not remove the separately created payslip bucket or its objects.
