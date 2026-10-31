# Test Cases: Submit a Support Ticket

| | |
|---|---|
| **Document** | Manual test plan and test cases, with execution results |
| **Application** | AI Ticket Triage (Support Desk) |
| **Author** | Gaurang Sisodiya |
| **Executed** | 31 Oct 2026 |
| **Build** | `main` @ `fc20b93` |

## 1. Test plan

### Feature under test

The **Submit a support ticket** form (`GET /tickets/create`, `POST /tickets`) and what happens after a ticket is submitted:

1. Server-side validation (`StoreTicketRequest`).
2. The ticket is saved with status `new` and the user is redirected to the ticket page with a confirmation message.
3. A queued job (`AnalyzeTicketJob`) sends the ticket to the LLM (`TicketAnalyzer`).
4. Routing rules (`TicketRouter`) set the team and decide whether the ticket is auto-resolved or sent to human review.

### Scope

| In scope | Out of scope |
|---|---|
| Field validation: required, email format, min/max length | Dashboard and Evaluation pages |
| Client-side checks in the browser (`required`, `type="email"`, `maxlength`) | Human review actions (correct category, mark resolved, re-run analysis) |
| Confirmation message, case reference, ticket detail page | Accuracy of the AI's category on a large data set (covered by the Evaluation page) |
| Output escaping of user input (stored XSS) | Performance, load and rate-limit stress testing |
| Auto-resolve vs. needs-review flow | Accessibility audit, mobile layouts, cross-browser matrix |
| LLM failure handling (invalid API key) | Authentication (the app has no login) |
| Ticket appears in the ticket list | Prompt-injection robustness |

### Test environment

| Item | Value |
|---|---|
| OS | Windows 11 |
| Browser | Google Chrome 156 (headless, driven through the Chrome DevTools Protocol) |
| Application | Laravel 13.38.0 on PHP 8.4.26, `php artisan serve` on `http://127.0.0.1:8001` |
| Database | MariaDB 10.4.27 (XAMPP, MySQL-compatible), separate schema `ai_ticket_triage_qa`, seeded with `GovTechDemoSeeder` |
| Queue | `database` driver, worker started with `php artisan queue:work --queue=default` |
| LLM | Groq, model `openai/gpt-oss-120b`, `AI_AUTO_RESOLVE_THRESHOLD=0.8`, `LLM_REQUESTS_PER_MINUTE=2` |
| Test data | Created fresh for each case (see the Test Data column) |

### Entry criteria

- The application starts and `/tickets/create` loads without errors.
- Migrations and the GovTech seed data load (13 categories, 10 KB articles).
- The automated suite passes (`php artisan test`: **49 passed, 155 assertions**).
- A queue worker is running, and a valid LLM API key is configured (except for TC-12).

### Exit criteria

- All 12 test cases have been executed and have a recorded status.
- No open Critical or Major defects in the feature under test.
- Every defect found is logged with steps to reproduce and evidence (see [bug-report.md](bug-report.md)).

### Notes on approach

- **Two layers of validation.** For negative and boundary cases, the browser's own checks were tested first. They were then removed in the browser (the `required` and `maxlength` attributes, and `type="email"`), the way a tester would with DevTools, so the server rules could be tested directly.
- **LLM output.** TC-10 and TC-11 depend on the model's answer. Requests use `temperature: 0`, but results can still vary between model versions. Their expected results follow the routing rules in `TicketRouter`, which are deterministic once the AI's answer is known.

## 2. Test cases and results

All 12 cases were executed. **Result: 12 Passed, 0 Failed.** One defect was found during exploratory testing next to TC-08 (see section 3).

| Test Case ID | Title | Preconditions | Test Steps | Test Data | Expected Result | Priority | Type | Actual Result | Status |
|---|---|---|---|---|---|---|---|---|---|
| TC-01 | Submit a valid ticket and get a case reference | App running; form page open | 1. Open `/tickets/create`<br>2. Fill all three fields with valid data<br>3. Click **Submit ticket** | Email: `clerk.adams@cityofexample.gov`<br>Subject: `Agenda packet will not compile`<br>Description: 160-char description of the compile spinner never finishing | Redirect to `/tickets/{id}`. Green "Recorded" banner: "Ticket #{id} received. Our AI assistant is analyzing it now." Page shows `CASE-{id padded to 4}`, status stamp **New**, decision "Waiting for AI analysis.", and the page refreshes itself every 5 s | High | Functional | Redirected to `/tickets/35`. Banner: "Ticket #35 received. Our AI assistant is analyzing it now." Breadcrumb shows `CASE-0035`, stamp **New**, decision "Waiting for AI analysis.", `<meta http-equiv="refresh">` present | Pass |
| TC-02 | Submitted ticket is analyzed and appears in the ticket list | TC-01 done; queue worker running with a valid API key | 1. Let the worker process the job<br>2. Reload the ticket page<br>3. Open `/tickets`<br>4. Search for the subject | Ticket `CASE-0035` from TC-01 | Status changes from New to **Auto-resolved** or **Needs review**. Category, priority, team and confidence are filled in. Audit trail shows "1 LLM call" marked "Valid response". The page stops auto-refreshing. The ticket is the first row in `/tickets` with the same values, and the search finds it | High | Integration | Worker finished the job in 2 s. Stamp **Auto-resolved**, category "Agendas & meetings", priority Medium, team L1 Support, confidence 85%, audit trail "1 LLM call / VALID RESPONSE", no refresh tag. First row in the list: `CASE-0035 … Auto-resolved, Agendas & meetings, Medium, L1 Support, 85%`. Search by subject found it (plus a seeded ticket with the same subject). Evidence: [tc-02-ticket-list.png](evidence/tc-02-ticket-list.png) | Pass |
| TC-03 | Empty required fields are rejected | Form page open | 1. Leave all fields empty, click **Submit ticket**<br>2. Remove the `required` attributes in DevTools and submit again | All fields empty | (1) The browser blocks the submit with "Please fill out this field."<br>(2) The server re-shows the form with a red message under each field: "The email field is required.", "The subject field is required.", "The problem description field is required." No ticket is created | High | Negative | (1) No request was sent; all 3 fields invalid, message "Please fill out this field."<br>(2) Back on `/tickets/create` with the 3 expected messages. Ticket count unchanged | Pass |
| TC-04 | Invalid email format is rejected | Form page open | 1. Enter an email without `@`, valid subject and description<br>2. Submit<br>3. Bypass `type="email"` in DevTools and submit again | Email: `qa.tester.cityofexample.gov` | (1) Browser message about the missing "@"<br>(2) Server message "The email field must be a valid email address." Subject and description are kept (old input). No ticket created | High | Negative | (1) "Please include an '@' in the email address. 'qa.tester.cityofexample.gov' is missing an '@'."<br>(2) "The email field must be a valid email address." Subject and the 73-char description were still filled in | Pass |
| TC-05 | Subject longer than the maximum | Form page open | 1. Type 300 characters into Subject<br>2. Check how many are kept<br>3. Bypass `maxlength`, set 300 chars, submit | Subject: `S` × 300 | (1) The browser keeps only 255 characters (`maxlength="255"`)<br>(2) Server: "The subject field must not be greater than 255 characters." No ticket created | Medium | Negative | (1) The field held 255 characters<br>(2) "The subject field must not be greater than 255 characters." | Pass |
| TC-06 | Description longer than the maximum | Form page open | 1. Type 6,000 characters into Description<br>2. Check how many are kept<br>3. Bypass `maxlength`, set 6,000 chars, submit | Description: `D` × 6000 | (1) The browser keeps only 5,000 characters<br>(2) Server: "The problem description field must not be greater than 5000 characters." No ticket created | Medium | Negative | (1) The field held 5,000 characters<br>(2) "The problem description field must not be greater than 5000 characters." | Pass |
| TC-07 | Subject length boundaries (min 3, max 255) | Form page open; client checks bypassed for the invalid values | Submit four times with valid email and description, changing only the subject length | Subject: `B` × 2, 3, 255, 256 | 2 chars: "The subject field must be at least 3 characters."<br>3 chars: accepted<br>255 chars: accepted<br>256 chars: "…must not be greater than 255 characters." | Medium | Boundary | 2: rejected with the min message<br>3: accepted (ticket #31)<br>255: accepted (ticket #32)<br>256: rejected with the max message | Pass |
| TC-08 | Description length boundaries (min 10, max 5000) | Form page open; client checks bypassed for the invalid values | Submit four times with valid email and subject, changing only the description length | Description: `x` × 9, 10, 5000, 5001 (no line breaks) | 9 chars: "The problem description field must be at least 10 characters."<br>10: accepted<br>5000: accepted<br>5001: "…must not be greater than 5000 characters." | Medium | Boundary | 9: rejected with the min message<br>10: accepted (ticket #33)<br>5000: accepted (ticket #34)<br>5001: rejected with the max message. **Note:** a 5,000-character description *with line breaks* is rejected. See BUG-001 | Pass |
| TC-09 | HTML and script tags are shown as text, not executed | Form page open; worker running | 1. Submit a ticket whose subject and description contain HTML/JS<br>2. Open the ticket page and the list<br>3. Watch for JavaScript dialogs and inspect the DOM | Subject: `<b>Bold subject</b> test`<br>Description: `<script>alert('xss')</script>`, `<img src=x onerror="alert('img')">`, `<a href="javascript:alert(1)">click</a>` | Tags are shown literally as text on the ticket page, in the list and in the page title. No alert dialog. No `<script>`, `<img>` or `<a>` element is created from user input (Blade `{{ }}` escapes output) | High | Security | Heading shows `<b>Bold subject</b> test` as text, with no `<b>` element. Description HTML is escaped (`&lt;script&gt;…`), and 0 script/img/a elements are inside the message. 0 dialogs fired across all pages. List row shows the raw tags as text. Evidence: [tc-09-html-shown-as-text.png](evidence/tc-09-html-shown-as-text.png) | Pass |
| TC-10 | Routine how-to ticket is auto-resolved | Worker running with a valid key; KB article `password-reset` seeded | 1. Submit a simple password-reset question<br>2. Wait for the worker<br>3. Open the ticket page | Email: `j.ortiz@cityofexample.gov`<br>Subject: `How do I reset my password?`<br>Description: forgot password, agency does **not** use SSO | The AI matches KB article "How to reset your CivicLink password" with confidence ≥ 80%, low/medium urgency, not angry. Status **Auto-resolved**, `resolved_by = ai`, panel "Reply sent to customer" tagged "Sent automatically", and a decision line naming the article, confidence and threshold. No "Mark as resolved" button | High | Integration | Stamp **Auto-resolved**. Decision: "Auto-resolved: matched "How to reset your CivicLink password" with 96% confidence (threshold 80%)." Priority Low, team L1 Support, "Reply sent to customer / SENT AUTOMATICALLY", `resolved_by = ai`, no resolve button | Pass |
| TC-11 | Urgent outage goes to human review | Worker running with a valid key | 1. Submit a ticket about an outage that blocks many users<br>2. Wait for the worker<br>3. Open the ticket page | Email: `it.director@cityofexample.gov`<br>Subject: `URGENT: no one in the county can log in`<br>Description: 40 staff get a 500 error, legal deadline at 5pm | Urgency **High**; no auto-reply (outages must not get a KB match, and high urgency is never auto-resolved). Status **Needs review**, routed to the Bug / outage team (Dev Team). No "Reply sent" panel. "Mark as resolved" button visible | High | Integration | Stamp **Needs review**. Category "Bug / outage", priority High, sentiment Angry, team Dev Team, confidence 96%, no KB match, no reply panel. Decision: "No knowledge base article fully answers this ticket, so it needs a human." Resolve button shown | Pass |
| TC-12 | LLM call fails (invalid API key) | Worker started with `LLM_API_KEY=invalid-key-for-qa` (process environment only; `.env` unchanged) | 1. Submit a valid ticket<br>2. Process it with the invalid-key worker<br>3. Open the ticket page and check `ai_logs` / `failed_jobs` | Email: `qa.failure@cityofexample.gov`<br>Subject: `Website page will not publish` | No crash, and the ticket does not stay stuck in "Analyzing". Exactly 1 LLM call, because a 401 is not retried. Status **Needs review**, team **L1 Support**, category/priority/confidence shown as "—". Decision: "The AI analysis failed, so a human needs to triage this ticket…". Audit trail shows **Failed** with "HTTP 401 from LLM provider". Job is not in `failed_jobs` | High | Integration | Job finished in 470 ms. Stamp **Needs review**, team L1 Support, priority and confidence "—", expected decision text. Audit trail: "1 LLM call", "FAILED HTTP 401 from LLM provider". Raw response logged: `{"error":{"message":"Invalid API Key",…"code":"invalid_api_key"}}`. `failed_jobs` = 0. Evidence: [tc-12-llm-401-needs-review.png](evidence/tc-12-llm-401-needs-review.png) | Pass |

## 3. Exploratory testing and observations

| Ref | What was tried | Observed | Outcome |
|---|---|---|---|
| EXP-01 | Description of exactly 5,000 characters as counted by the browser, made of 50 lines (49 line breaks) | The browser accepts it (field valid, `maxlength` not exceeded). The server rejects it with "must not be greater than 5000 characters", because browsers submit each line break as CRLF (2 characters): 5,000 + 49 = 5,049 | **Defect logged: [BUG-001](bug-report.md)** |
| OBS-01 | Ticket reference wording after submit | The confirmation banner says "Ticket #35" while the page shows "CASE-0035". `Ticket::reference()` builds a third format, "TKT-0035", which no page uses | Cosmetic inconsistency, not logged as a bug |
| OBS-02 | TC-01 ticket ("spinner runs, then nothing happens") was auto-resolved | The AI matched the agenda article at 85%. Its reply only used a documented cause (a password-protected PDF stops compiling), so the decision is defensible | Not a defect. Worth adding to the evaluation set as a borderline "bug vs. how-to" example |

## 4. Traceability

Requirements come from the code: validation rules in `app/Http/Requests/StoreTicketRequest.php`, the flow in `TicketController@store`, `AnalyzeTicketJob`, `TicketAnalyzer`, `TicketRouter` and the Blade views.

| Req ID | Requirement | Source | Test cases |
|---|---|---|---|
| REQ-01 | A valid ticket is saved with status `new`, and the user sees a confirmation with a case reference straight away | `TicketController@store`, `layout.blade.php`, `tickets/show.blade.php` | TC-01, TC-07, TC-08 |
| REQ-02 | The ticket is analyzed in the background, and the result is shown on the ticket page and in the ticket list | `AnalyzeTicketJob`, `TicketRouter::apply`, `TicketController@index` | TC-02 |
| REQ-03 | Email is required and must be a valid email address | `StoreTicketRequest` (`required, email, max:255`), `type="email"` | TC-03, TC-04 |
| REQ-04 | Subject is required, 3–255 characters | `StoreTicketRequest` (`required, string, min:3, max:255`), `maxlength="255"` | TC-03, TC-05, TC-07 |
| REQ-05 | Description is required, 10–5,000 characters | `StoreTicketRequest` (`required, string, min:10, max:5000`), `maxlength="5000"` | TC-03, TC-06, TC-08, EXP-01 |
| REQ-06 | An invalid submission creates no ticket and queues no job. The form shows a message under each invalid field and keeps the user's input | `StoreTicketRequest::attributes()`, `@error` blocks and `old()` in `tickets/create.blade.php` | TC-03, TC-04, TC-05, TC-06 |
| REQ-07 | User input is shown as plain text and never executed (no stored XSS) | Blade `{{ }}` escaping in `show` and `index` views | TC-09 |
| REQ-08 | A ticket is auto-resolved only if it matches a KB article, confidence ≥ threshold (80%), urgency is not high and sentiment is not angry | `TicketRouter::canAutoResolve` | TC-10, TC-11 |
| REQ-09 | Tickets the AI cannot resolve go to human review and are routed to the category's team (high-urgency first-line tickets escalate to Dev Team) | `TicketRouter::apply`, `TicketRouter::route` | TC-11 |
| REQ-10 | If the LLM call fails, the ticket goes to review with L1 Support, the error is logged in the audit trail, and nothing crashes | `TicketAnalyzer::analyze` (never throws, no retry on 401), `TicketRouter::apply(null)` | TC-12 |
