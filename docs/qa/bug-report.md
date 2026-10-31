# Bug Report

| Field | Value |
|---|---|
| **Bug ID** | BUG-001 |
| **Title** | A description the form accepts as 5,000 characters is rejected by the server as too long when it contains line breaks |
| **Reported by** | Gaurang Sisodiya |
| **Date** | 31 Oct 2026 |
| **Status** | Open |
| **Component** | Submit a support ticket form: Description field (`tickets/create.blade.php`, `StoreTicketRequest`) |
| **Found by** | Exploratory testing next to TC-08 (description boundary values). See [test-cases.md](test-cases.md), EXP-01 |
| **Severity** | Minor. The user can still submit by shortening the text, and no data is lost |
| **Priority** | Low. It only happens near the 5,000-character limit with multi-line text, but the user gets no clue how to fix it |

## Environment

- Windows 11, Google Chrome 156 (headless)
- Laravel 13.38.0, PHP 8.4.26, `php artisan serve`
- MariaDB 10.4.27 (XAMPP), schema `ai_ticket_triage_qa`, GovTech seed data
- Build: `main` @ `fc20b93`

## Steps to Reproduce

1. Open `http://127.0.0.1:8001/tickets/create`.
2. Email: `qa.tester@cityofexample.gov`. Subject: `Description with line breaks`.
3. In **Description**, type 50 lines of text separated by Enter, for 5,000 characters in total as counted by the browser. Test data: 50 lines of 99 `y` characters, with one extra `y` at the end (49 line breaks + 4,951 letters = 5,000).
4. Note that the field takes all of it. The browser's `maxlength="5000"` does not block it, and `checkValidity()` returns `true`.
5. Click **Submit ticket**.

## Expected Result

The ticket is accepted, the same as a 5,000-character description without line breaks (TC-08). What the browser lets the user type and what the server accepts should be the same limit. The help text promises "Up to 5,000 characters."

## Actual Result

The form comes back with the error **"The problem description field must not be greater than 5000 characters."** and no ticket is created.

The user is now stuck in a confusing state. The text box is visibly full at exactly 5,000 characters, so it looks within the limit, and the message doesn't say by how much the text is over or why. The user has to delete text by trial and error.

**Root cause (confirmed):** the browser counts each line break as 1 character for `maxlength`, but sends it as CRLF (`\r\n`, 2 characters) when the form is submitted. That is standard HTML form behavior. Laravel's `max:5000` rule counts the submitted string, so the 49 line breaks add 49 characters: 5,000 in the browser becomes 5,049 on the server.

## Evidence

- Screenshot: [evidence/bug-001-description-line-breaks.png](evidence/bug-001-description-line-breaks.png). The description box is full and shows the "must not be greater than 5000 characters" error.
- Browser check before submit (Chrome DevTools Protocol):
  ```
  document.getElementById('body').value.length  -> 5000
  document.getElementById('body').checkValidity() -> true
  ```
- Server-side check with the same text, run through the app's own validation rule (`StoreTicketRequest::rules()['body']`):
  ```
  LF version:   mb_strlen=5000 -> passes
  CRLF version: mb_strlen=5049 -> The body field must not be greater than 5000 characters.
  ```
- Control case: 5,000 characters **without** line breaks are accepted (TC-08, ticket #34).

## Suggested Fix

Normalize line breaks before validating, so the server counts characters the same way the browser does. In `StoreTicketRequest`:

```php
protected function prepareForValidation(): void
{
    if (is_string($this->body)) {
        // Browsers count a line break as 1 character for maxlength but submit it as "\r\n".
        $this->merge(['body' => str_replace("\r\n", "\n", $this->body)]);
    }
}
```

This also stores descriptions with consistent `\n` line endings. Add a regression test to `tests/Feature/TicketSubmissionTest.php` that posts a 5,000-character body containing `\r\n` line breaks and asserts the ticket is saved.

Optional UX improvement: a live character counter under the Description field ("4,870 / 5,000") would help users see how close they are to the limit.
