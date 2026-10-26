<?php

namespace Database\Seeders;

/**
 * Demo dataset: "NovaERP", a fictional B2B ERP product.
 * Run with: php artisan db:seed --class=ErpDemoSeeder
 *
 * The comment above each ticket explains the expected outcome in words (auto-resolve or
 * review, and why).
 *
 * The last two values of each ticket are its hand-labelled correct answer (evaluation set).
 * Labelling rules for the hard cases, so the labels are consistent and can be challenged:
 * - two issues in one ticket: labelled by the issue that blocks someone's work;
 * - vague, feedback or legal questions with no product issue: "other", L1 Support;
 * - platform-side failures (outages, data loss, wrong behaviour after an update): "bug",
 *   even when they happen inside a specific module;
 * - expected team = the team that should own it, including escalation of urgent tickets.
 */
class ErpDemoSeeder extends DemoSeeder
{
    protected function teams(): array
    {
        return [
            'L1 Support' => 'First-line support: how-to questions, account access, configuration help.',
            'Dev Team' => 'Engineers: bugs, errors, outages, data corruption, integration failures.',
            'Billing' => 'Subscription, licences, payments and refunds for the NovaERP account itself.',
        ];
    }

    protected function categories(): array
    {
        return [
            'login_access' => ['Login & access', 'Cannot log in, locked account, SSO or 2FA problems.', 'L1 Support'],
            'password_reset' => ['Password reset', 'Forgotten password or reset email problems.', 'L1 Support'],
            'reporting' => ['Reports & export', 'Running or exporting reports (Excel, CSV, PDF).', 'L1 Support'],
            'invoicing' => ['Invoicing', "Wrong amounts, tax or layout on invoices the customer sends to THEIR clients.", 'L1 Support'],
            'inventory' => ['Inventory & stock', 'Stock levels, warehouses, transfers, stock counts.', 'L1 Support'],
            'permissions' => ['Users & permissions', 'Roles, missing modules, access denied.', 'L1 Support'],
            'data_import' => ['Data import', 'Importing customers, products or stock from CSV files.', 'L1 Support'],
            'integration' => ['API & integrations', 'API errors, tokens, webhooks, third-party connections.', 'Dev Team'],
            'performance' => ['Performance', 'Slow pages, timeouts, system-wide slowness.', 'Dev Team'],
            'bug' => ['Bug / error', 'Something is broken: error messages, wrong data after an update, broken layout.', 'Dev Team'],
            'account_billing' => ['Account billing', 'The NovaERP subscription itself: licences, charges, refunds, cancellation.', 'Billing'],
            'other' => ['Other', 'Feedback, feature requests, or anything that fits no other category.', 'L1 Support'],
        ];
    }

    protected function kbArticles(): array
    {
        return [
            [
                'slug' => 'login-troubleshooting',
                'title' => 'Cannot log in to NovaERP',
                'content' => "If you cannot log in:\n"
                    ."1. Check you are using your company URL (https://yourcompany.novaerp.com), not the public website.\n"
                    ."2. Usernames are your work email address and are not case-sensitive. Passwords are case-sensitive; check Caps Lock.\n"
                    ."3. After 5 failed attempts your account is locked for 15 minutes. Wait and try again, or ask your company admin to unlock it under Settings > Users.\n"
                    ."4. Clear your browser cache and cookies, or try a private/incognito window.\n"
                    ."5. If your company uses Single Sign-On (SSO), click 'Sign in with SSO' instead of entering a password.",
            ],
            [
                'slug' => 'password-reset',
                'title' => 'How to reset your password',
                'content' => "To reset your password:\n"
                    ."1. On the login page click 'Forgot password?'.\n"
                    ."2. Enter your work email address. A reset link is sent within 2 minutes; check your spam folder.\n"
                    ."3. The link is valid for 60 minutes and can only be used once.\n"
                    ."4. New passwords must be at least 12 characters and include a number and a symbol. You cannot reuse your last 5 passwords.\n"
                    ."If no email arrives after 10 minutes, your company admin can send a reset link from Settings > Users > Reset password.",
            ],
            [
                'slug' => 'report-export',
                'title' => 'Exporting reports to Excel, CSV or PDF',
                'content' => "To export a report:\n"
                    ."1. Open the report under Reports and apply your filters.\n"
                    ."2. Click Export (top right) and choose Excel, CSV or PDF.\n"
                    ."3. Exports with more than 50,000 rows run in the background; you receive an email with a download link (valid for 7 days) when it is ready.\n"
                    ."4. If the download is blank or stuck, check your browser is not blocking pop-ups for novaerp.com.\n"
                    ."5. Excel showing strange characters? Choose 'CSV (UTF-8)' instead of plain CSV.",
            ],
            [
                'slug' => 'invoice-errors',
                'title' => 'Fixing incorrect amounts or tax on invoices',
                'content' => "If an invoice shows the wrong amount or tax:\n"
                    ."1. Check the customer's tax profile under Customers > [customer] > Tax settings. Invoices use the tax rate saved there.\n"
                    ."2. Check the price list assigned to the customer; special prices override the standard list.\n"
                    ."3. Draft invoices can be edited directly. Posted invoices cannot be edited; create a Credit Note (Invoices > [invoice] > Create credit note) and issue a new invoice.\n"
                    ."4. Rounding differences of 0.01 come from line-level rounding; switch to 'round on total' under Settings > Accounting if preferred.",
            ],
            [
                'slug' => 'inventory-stock-mismatch',
                'title' => 'Stock levels do not match what you expect',
                'content' => "NovaERP shows several stock figures. Common reasons they differ:\n"
                    ."1. 'Available' = On hand minus stock reserved for open sales orders. Reserved stock is released when the order is shipped or cancelled.\n"
                    ."2. Transfers between warehouses are 'In transit' until the receiving warehouse confirms them under Inventory > Transfers.\n"
                    ."3. Goods receipts that are saved but not posted do not increase stock. Post them under Purchasing > Goods receipts.\n"
                    ."4. Check the unit of measure: a product bought in boxes of 12 but stocked in pieces is converted on receipt.\n"
                    ."5. To correct real differences, run a stock count under Inventory > Stock counts; posting it creates adjustment entries.",
            ],
            [
                'slug' => 'subscription-and-billing',
                'title' => 'Your NovaERP subscription, licences and payments',
                'content' => "Managing your NovaERP subscription:\n"
                    ."1. Account owners can see the current plan, licence count and invoices under Settings > Subscription.\n"
                    ."2. Adding user licences takes effect immediately and is charged pro-rata on the next invoice.\n"
                    ."3. Removing licences takes effect at the next renewal date.\n"
                    ."4. To update your card or switch to bank transfer, go to Settings > Subscription > Payment method.\n"
                    ."5. Your NovaERP invoices can be downloaded as PDF from Settings > Subscription > Billing history.",
            ],
            [
                'slug' => 'slow-performance',
                'title' => 'NovaERP is slow',
                'content' => "If NovaERP feels slow:\n"
                    ."1. Check status.novaerp.com for any ongoing incidents.\n"
                    ."2. Large reports are slow when the date range is wide. Narrow the date range or use filters.\n"
                    ."3. Use a supported browser (latest Chrome, Edge or Firefox) and close unused NovaERP tabs.\n"
                    ."4. Clear your browser cache.\n"
                    ."5. If only one screen is slow for everyone in your company and the status page is green, contact support with the screen name and the time it happened.",
            ],
            [
                'slug' => 'user-permissions',
                'title' => 'Users cannot see a module or get "Access denied"',
                'content' => "NovaERP uses role-based permissions:\n"
                    ."1. A company admin assigns roles under Settings > Users > [user] > Roles.\n"
                    ."2. Each role grants access to modules (e.g. Sales, Inventory, Accounting). A user only sees modules their roles allow.\n"
                    ."3. After a role change, the user must log out and log back in.\n"
                    ."4. 'Access denied' on a specific record usually means the record belongs to a branch the user is not assigned to; check Settings > Users > [user] > Branches.\n"
                    ."Only company admins can change permissions. NovaERP support cannot grant access on your behalf.",
            ],
            [
                'slug' => 'data-import-csv',
                'title' => 'Importing customers, products or stock from CSV',
                'content' => "To import data:\n"
                    ."1. Go to Settings > Import and download the template for the data type.\n"
                    ."2. Keep the header row unchanged and save the file as CSV (UTF-8).\n"
                    ."3. Dates must be YYYY-MM-DD; decimals must use a dot (12.50).\n"
                    ."4. The import preview shows errors per row; fix them in the file and upload again.\n"
                    ."5. Imports are limited to 20,000 rows per file. Split larger files.",
            ],
            [
                'slug' => 'api-integration-errors',
                'title' => 'API and integration errors (401, 403, 429)',
                'content' => "Common NovaERP API errors:\n"
                    ."- 401 Unauthorized: the API token is missing, expired or revoked. Create a new token under Settings > API tokens.\n"
                    ."- 403 Forbidden: the token is valid but its scopes do not include this endpoint. Edit the token scopes.\n"
                    ."- 429 Too Many Requests: you exceeded 600 requests per minute. Retry after the number of seconds in the Retry-After header.\n"
                    ."Webhooks are retried 5 times over 24 hours; failed deliveries are listed under Settings > Webhooks > Delivery log.",
            ],
        ];
    }

    protected function tickets(): array
    {
        return [
            // --- Clear KB matches: expected auto_resolved ---

            // expect: password_reset, kb password-reset
            ['Forgot my password', "Hi, I forgot my password and can't get into NovaERP. How do I reset it?", 'anna.k@brightlogistics.com', 'password_reset', 'L1 Support'],
            // expect: password_reset, kb password-reset
            ['Password reset email not arriving', "I clicked 'Forgot password' twice but haven't received any email yet. It's been about 5 minutes.", 'mark.t@ferrotech.de', 'password_reset', 'L1 Support'],
            // expect: login_access, kb login-troubleshooting
            ['Account locked', "I typed my password wrong a few times and now it says my account is locked. What do I do?", 'j.ramos@greenfieldfoods.com', 'login_access', 'L1 Support'],
            // expect: reporting, kb report-export
            ['How to export sales report to Excel?', "Is there a way to get the monthly sales report into Excel? I need to send it to my manager.", 'priya.n@castlebuild.co.uk', 'reporting', 'L1 Support'],
            // expect: reporting, kb report-export
            ['Strange characters in exported CSV', "When I export the customer list to CSV and open it in Excel, all the German umlauts show up as weird symbols.", 'h.mueller@stahlwerk.de', 'reporting', 'L1 Support'],
            // expect: permissions, kb user-permissions
            ['New employee cannot see Inventory module', "We added a new warehouse employee yesterday but she can't see the Inventory menu at all. Can you help?", 'ops@harbourfreight.com.au', 'permissions', 'L1 Support'],
            // expect: permissions, kb user-permissions
            ['Access denied on purchase order', "I get 'Access denied' when opening PO-10234, but my colleague can open it fine.", 'tomasz.w@polmetal.pl', 'permissions', 'L1 Support'],
            // expect: invoicing, kb invoice-errors
            ['Wrong VAT rate on invoice', "Invoice INV-2291 was created with 19% VAT but this customer should be 7%. How do I fix it? It's already posted.", 'buchhaltung@kleinmoebel.de', 'invoicing', 'L1 Support'],
            // expect: inventory, kb inventory-stock-mismatch
            ['Available stock lower than on hand', "Product SKU-4410 shows 120 on hand but only 35 available. Where did the other 85 go?", 'stock@meridiantrading.com', 'inventory', 'L1 Support'],
            // expect: inventory, kb inventory-stock-mismatch
            ['Transferred stock not showing in Warehouse C', "We moved 200 units from Warehouse A to Warehouse C on Monday. A shows the decrease but C still shows zero.", 'logistics@kestrelfoods.com', 'inventory', 'L1 Support'],
            // expect: account_billing, kb subscription-and-billing
            ['Add 5 more user licences', "We're hiring and need 5 more user seats. How do we add them and how will it be billed?", 'finance@luminahealth.com', 'account_billing', 'Billing'],
            // expect: data_import, kb data-import-csv
            ['Product import failing on dates', "Our product CSV import shows errors on every row in the 'valid_from' column. We use dd/mm/yyyy.", 'it@coastalsupply.com', 'data_import', 'L1 Support'],
            // expect: integration, kb api-integration-errors
            ['API returning 429', "Our nightly sync script started getting HTTP 429 errors from the API halfway through. What's the limit?", 'dev@shopconnect.io', 'integration', 'Dev Team'],
            // expect: integration, kb api-integration-errors
            ['401 from API since this morning', "All our API calls return 401 Unauthorized since this morning. Nothing changed on our side.", 'integrations@vertexretail.com', 'integration', 'Dev Team'],

            // --- Real problems with no KB answer: expected needs_review ---

            // expect: bug, high, Dev Team
            ['URGENT: Invoices page shows error 500', "Since 9am the Invoices page returns 'Error 500' for our whole company. We cannot bill customers today. Please fix ASAP!!", 'cfo@maxwellindustries.com', 'bug', 'Dev Team'],
            // expect: bug/inventory, high, Dev Team (data corruption, not a how-to)
            ['Stock levels doubled after update', "After last night's update, stock quantities in Warehouse B are doubled for about 40 products. This is causing wrong orders.", 'warehouse@delta-dist.nl', 'bug', 'Dev Team'],
            // expect: performance, high, Dev Team (company-wide, status page green)
            ['System extremely slow for everyone', "NovaERP has been taking 30+ seconds per page for all 80 of our users since this morning. Status page says everything is fine.", 'it-lead@ridgewaymfg.com', 'performance', 'Dev Team'],
            // expect: account_billing, Billing (refund needs a human)
            ['Charged twice this month', "Our credit card was charged twice for the October subscription. We need a refund for the duplicate payment.", 'accounts@sunpeakhotels.com', 'account_billing', 'Billing'],
            // expect: account_billing, Billing (cancellation needs a human)
            ['Cancel our subscription', "We have decided to move to another system. Please cancel our contract and tell us how to export all our data.", 'director@oldmillbakery.co.uk', 'account_billing', 'Billing'],
            // expect: bug, medium, Dev Team
            ['PDF invoice layout broken', "The company logo on our PDF invoices is now overlapping the address block. It looked fine last week.", 'admin@bluepeakdesign.com', 'bug', 'Dev Team'],
            // expect: integration, medium, Dev Team
            ['Webhook not firing for new orders', "Our order.created webhook stopped sending anything 2 days ago. The delivery log is empty, not even failures.", 'tech@parcelhub.io', 'integration', 'Dev Team'],
            // expect: other, low (feature request)
            ['Feature request: dark mode', "Would love a dark mode option. Our night shift staff find the white screens very bright.", 'night.shift@24hlogistics.com', 'other', 'L1 Support'],

            // --- Ambiguous / tricky: expected needs_review ---

            // vague: no product area at all
            ['Not working', "It's not working again. Please help.", 'user123@gmail.com', 'other', 'L1 Support'],
            // vague and angry
            ['Terrible', "This is the third time this week. Honestly considering switching vendors. Fix it.", 'k.brennan@brennanconstruction.com', 'other', 'L1 Support'],
            // two unrelated issues in one ticket
            ['Login and invoice problem', "Two things: my colleague can't log in, and also the totals on invoice INV-3302 don't match the line items. Which one should I report first?", 'office@alpinetools.at', 'login_access', 'L1 Support'],
            // looks like password reset, is really a possible security incident
            ['Did someone change my password?', "I got a 'your password was changed' email but I didn't change it. Now I can't log in.", 'r.singh@medivault.com', 'login_access', 'Dev Team'],
            // slow, but is it normal? not clearly covered
            ['Reports a bit slow', "The Aged Receivables report takes about a minute to load. Is that normal?", 'ar@crestwoodlegal.com', 'performance', 'Dev Team'],
            // product capability question, no KB article
            ['Can we bill customers in USD?', "We're starting to sell to US clients. Can NovaERP create invoices in USD while our books stay in EUR?", 'sales@nordicoptics.se', 'invoicing', 'L1 Support'],
            // positive feedback, nothing to resolve
            ['Thank you!', "Just wanted to say the new dashboard is great, our team loves it. No issue, just thanks!", 'emma.l@greenleafcafe.com', 'other', 'L1 Support'],
            // non-English
            ['Problema con la exportación', "No puedo exportar el informe de ventas a PDF, el botón no hace nada. ¿Me pueden ayudar?", 'contabilidad@iberica-sa.es', 'reporting', 'L1 Support'],
        ];
    }
}
