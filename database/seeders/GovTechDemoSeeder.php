<?php

namespace Database\Seeders;

/**
 * Demo dataset: "CivicLink", a fictional SaaS platform used by local government agencies
 * for meetings and agendas, citizen email/SMS bulletins, their public website, online forms
 * and public records requests.
 * Run with: php artisan db:seed --class=GovTechDemoSeeder
 *
 * The comment above each ticket is the outcome we EXPECT. It isn't stored; it lets
 * you compare the AI's decisions against a known answer during a demo.
 */
class GovTechDemoSeeder extends DemoSeeder
{
    protected function teams(): array
    {
        return [
            'L1 Support' => 'First-line support for agency staff: how-to questions, account access, configuration help.',
            'Dev Team' => 'Engineers: bugs, outages, delivery failures on the platform side, data problems.',
            'Billing' => 'Agency contracts, licence counts, purchase orders, invoices and renewals for CivicLink itself.',
        ];
    }

    protected function categories(): array
    {
        return [
            'login_sso' => ['Login & SSO', 'Cannot log in, single sign-on (Azure AD / Okta) errors, locked accounts.', 'L1 Support'],
            'password_reset' => ['Password reset', 'Forgotten password or reset email problems.', 'L1 Support'],
            'agendas_meetings' => ['Agendas & meetings', 'Creating and publishing meeting agendas, agenda items, meeting portal.', 'L1 Support'],
            'minutes_video' => ['Minutes & video', 'Uploading minutes, meeting video recordings, livestream archive.', 'L1 Support'],
            'bulletins' => ['Email/SMS bulletins', 'Citizen bulletins not delivered, bounces, SMS failures, sending problems.', 'L1 Support'],
            'subscribers' => ['Subscriber lists', 'Importing or managing citizen subscriber lists and topics.', 'L1 Support'],
            'website_cms' => ['Website CMS', 'Editing, publishing or scheduling pages on the agency website.', 'L1 Support'],
            'online_forms' => ['Online forms', 'Citizen form submissions missing, form notifications, form builder.', 'L1 Support'],
            'records_requests' => ['Public records requests', 'Tracking, assigning and responding to public records (FOIA) requests.', 'L1 Support'],
            'permissions' => ['Users & roles', 'Staff roles, departments, access denied, missing features.', 'L1 Support'],
            'bug' => ['Bug / outage', 'Something is broken platform-side: errors, outages, data loss, wrong behaviour after an update.', 'Dev Team'],
            'account_billing' => ['Contract & billing', 'CivicLink contract, licences, purchase orders, invoices, renewals.', 'Billing'],
            'other' => ['Other', 'Feedback, feature requests, or anything that fits no other category.', 'L1 Support'],
        ];
    }

    protected function kbArticles(): array
    {
        return [
            [
                'slug' => 'sso-login-troubleshooting',
                'title' => 'Cannot log in to CivicLink (including SSO)',
                'content' => "If you cannot log in:\n"
                    ."1. Use your agency's login URL (https://youragency.civiclink.app), not the public website.\n"
                    ."2. If your agency uses single sign-on (Azure AD or Okta), click 'Sign in with your organisation' and use your normal work login. Do not enter a CivicLink password.\n"
                    ."3. SSO error 'User not assigned to application' means your IT department has not added you to the CivicLink app in Azure AD/Okta. Contact your IT helpdesk.\n"
                    ."4. After 5 failed password attempts your account is locked for 15 minutes. Your site administrator can unlock it under Admin > Users.\n"
                    ."5. Try a private/incognito window to rule out old cookies.",
            ],
            [
                'slug' => 'password-reset',
                'title' => 'How to reset your CivicLink password',
                'content' => "Password reset applies only to agencies that do NOT use single sign-on.\n"
                    ."1. On the login page click 'Forgot password?' and enter your work email.\n"
                    ."2. The reset email arrives within 2 minutes. Government mail filters often quarantine it; ask IT to allow noreply@civiclink.app.\n"
                    ."3. The link is valid for 60 minutes and works once.\n"
                    ."4. Passwords need at least 14 characters including a number and a symbol.\n"
                    ."If your agency uses SSO, reset your password through your IT department instead.",
            ],
            [
                'slug' => 'publish-meeting-agenda',
                'title' => 'Creating and publishing a meeting agenda',
                'content' => "To publish an agenda:\n"
                    ."1. Go to Meetings > [meeting] > Agenda and add items. Attach staff reports as PDF.\n"
                    ."2. Click 'Compile agenda' to generate the agenda packet PDF. Compiling fails if any attachment is a password-protected PDF; remove the password and re-upload.\n"
                    ."3. Click 'Publish'. The agenda appears on your public meeting portal within 5 minutes.\n"
                    ."4. To meet public notice deadlines, use 'Schedule publish' to set an exact date and time.\n"
                    ."5. Changes after publishing require 'Publish amendment', which keeps the original version visible for transparency.",
            ],
            [
                'slug' => 'upload-minutes-and-video',
                'title' => 'Uploading meeting minutes and video recordings',
                'content' => "After a meeting:\n"
                    ."1. Minutes: Meetings > [meeting] > Minutes > Upload (PDF or Word, max 50 MB). Click 'Publish minutes' once approved.\n"
                    ."2. Video: Meetings > [meeting] > Media > Upload video. Supported formats: MP4 (H.264), max 10 GB.\n"
                    ."3. Video processing takes about 1x the video length (a 2-hour meeting takes about 2 hours). The status shows 'Processing' until done.\n"
                    ."4. Uploads stuck at 99% are usually caused by the browser being closed. Keep the tab open until it shows 'Upload complete'.\n"
                    ."5. To link agenda items to video timestamps, open the video and use 'Index agenda items'.",
            ],
            [
                'slug' => 'bulletin-delivery-failures',
                'title' => 'Email or SMS bulletins not delivered',
                'content' => "If subscribers did not receive a bulletin:\n"
                    ."1. Open Bulletins > [bulletin] > Delivery report. It shows delivered, bounced and suppressed counts.\n"
                    ."2. 'Suppressed' addresses previously hard-bounced or unsubscribed and will not be emailed again. Subscribers can re-subscribe from your public signup page.\n"
                    ."3. Many recipients at one organisation not receiving mail usually means their mail filter blocks bulk email; ask their IT to allow bulletins@civiclink.app.\n"
                    ."4. SMS is only sent to subscribers who opted in to text messages. US carriers block SMS containing URL shorteners; use full links.\n"
                    ."5. Bulletins over 10,000 recipients are sent in batches and can take up to 30 minutes.",
            ],
            [
                'slug' => 'subscriber-list-import',
                'title' => 'Importing subscriber lists',
                'content' => "To import subscribers:\n"
                    ."1. Go to Subscribers > Import and download the CSV template.\n"
                    ."2. Required column: email. Optional: phone (E.164 format, e.g. +15551234567), first_name, last_name, topics.\n"
                    ."3. Separate multiple topics with a semicolon (Road closures;Council meetings). Topics must already exist.\n"
                    ."4. You must confirm the subscribers gave consent. Imported addresses that previously unsubscribed are skipped automatically.\n"
                    ."5. The import summary lists rejected rows and the reason; fix and re-upload only those rows.",
            ],
            [
                'slug' => 'cms-page-editing',
                'title' => 'Editing and publishing website pages',
                'content' => "To edit a page on your agency website:\n"
                    ."1. Go to Website > Pages, find the page and click Edit.\n"
                    ."2. Save creates a draft. Click 'Submit for approval' if your department requires approval, or 'Publish' if you have publisher rights.\n"
                    ."3. Published changes appear within 2 minutes. If you still see the old page, refresh with Ctrl+F5 (browser cache).\n"
                    ."4. Use 'Schedule' to publish or unpublish at a set time, e.g. for temporary notices.\n"
                    ."5. Images must have alt text before publishing; this is required for accessibility (WCAG 2.1 AA).",
            ],
            [
                'slug' => 'form-submissions-not-arriving',
                'title' => 'Online form submissions not arriving',
                'content' => "If you are not receiving form submissions:\n"
                    ."1. All submissions are stored even if emails fail. Check Forms > [form] > Submissions first.\n"
                    ."2. Check the notification recipients under Forms > [form] > Settings > Notifications. Departed staff are a common cause.\n"
                    ."3. Notification emails come from forms@civiclink.app; ask IT to allow this address.\n"
                    ."4. If submissions are missing from the list too, check the form is published and not past its 'Close date'.\n"
                    ."5. Forms with file uploads reject files over 25 MB; the citizen sees an error and the submission is not saved.",
            ],
            [
                'slug' => 'records-request-tracking',
                'title' => 'Tracking and responding to public records requests',
                'content' => "Managing public records requests:\n"
                    ."1. New requests appear under Records > Inbox. Assign each to a department and a staff member.\n"
                    ."2. The due date is calculated from your agency's statutory deadline (set by your admin under Records > Settings). Weekends and configured holidays are skipped.\n"
                    ."3. Use 'Request clarification' to pause the clock when a request is unclear; the requester is emailed automatically.\n"
                    ."4. Upload responsive documents under the request and use the Redact tool before releasing. Redactions are permanent once released.\n"
                    ."5. 'Close request' sends the requester a final email with a download link valid for 30 days.",
            ],
            [
                'slug' => 'user-role-permissions',
                'title' => 'Staff roles and "Access denied"',
                'content' => "CivicLink uses roles per department:\n"
                    ."1. Site administrators manage users under Admin > Users > [user] > Roles.\n"
                    ."2. Roles control modules (Meetings, Bulletins, Website, Forms, Records) and actions (edit, approve, publish).\n"
                    ."3. Users only see content for the departments they are assigned to.\n"
                    ."4. After a role change the user must log out and back in.\n"
                    ."CivicLink support cannot change roles; contact your agency's site administrator.",
            ],
        ];
    }

    protected function tickets(): array
    {
        return [
            // --- Clear KB matches: expected auto_resolved ---

            // expect: login_sso, kb sso-login-troubleshooting
            ['SSO error when logging in', "When I click 'Sign in with your organisation' I get 'User not assigned to application'. I'm a new hire in the Planning department.", 'jdavis@riverbendcity.gov'],
            // expect: password_reset, kb password-reset
            ['Password reset email never arrives', "I requested a password reset three times and nothing came through. Checked spam too.", 'clerk.assistant@pinevalleytownship.gov'],
            // expect: agendas_meetings, kb publish-meeting-agenda
            ['Agenda packet will not compile', "When I click Compile agenda for the March 12 council meeting, it fails. One of the staff reports came from the county attorney.", 'cityclerk@lakeshorecity.gov'],
            // expect: agendas_meetings, kb publish-meeting-agenda
            ['Publish agenda at a specific time?', "We need the agenda to go live exactly 72 hours before the meeting for open meetings law. Can I set that up in advance?", 'deputy.clerk@oakridgecounty.gov'],
            // expect: minutes_video, kb upload-minutes-and-video
            ['Meeting video stuck on Processing', "I uploaded last night's 3-hour council meeting video about an hour ago and it still says Processing. Is something wrong?", 'av.tech@maplefordcity.gov'],
            // expect: bulletins, kb bulletin-delivery-failures
            ['Residents not getting road closure alerts', "Several residents called saying they didn't get yesterday's road closure bulletin. The delivery report shows some addresses as suppressed.", 'comms@harlowcounty.gov'],
            // expect: bulletins, kb bulletin-delivery-failures
            ['SMS bulletin not received', "Our emergency SMS went out but a lot of people say they never got the text. The message contained a bit.ly link.", 'oem@stonebridgecounty.gov'],
            // expect: subscribers, kb subscriber-list-import
            ['Subscriber import rejecting phone numbers', "Importing our newsletter list, every phone number is rejected. They're in the format (555) 123-4567.", 'pio@willowcreek.gov'],
            // expect: website_cms, kb cms-page-editing
            ['Page changes not showing on website', "I published an update to the Parks hours page 10 minutes ago but the public site still shows the old hours.", 'parks.web@cedarfalls-city.gov'],
            // expect: website_cms, kb cms-page-editing
            ["Can't publish page, says alt text required", "I'm trying to publish a news item with a photo and it won't let me. Something about alt text?", 'news@brookhavencounty.gov'],
            // expect: online_forms, kb form-submissions-not-arriving
            ['Not receiving permit application emails', "We stopped getting emails for the building permit form after our permits coordinator retired last month.", 'building@eastonborough.gov'],
            // expect: records_requests, kb records-request-tracking
            ['How to pause a records request deadline', "A requester asked for 'all emails about the park', which is way too broad. Can I stop the deadline clock while I ask them to narrow it?", 'records@fairviewcity.gov'],
            // expect: permissions, kb user-role-permissions
            ["New staff member can't see Bulletins", "Our new communications assistant can log in but the Bulletins module is missing from her menu.", 'admin@glenwoodvillage.gov'],
            // expect: permissions, kb user-role-permissions
            ['Access denied on Public Works pages', "I can edit Parks pages but get Access denied on Public Works pages. My manager said I should have access to both.", 'web.editor@summitcounty-demo.gov'],

            // --- Real problems with no KB answer: expected needs_review ---

            // expect: bug, high, Dev Team (public meeting portal down before a meeting)
            ['URGENT: public meeting portal is down', "Our public meeting portal shows a blank white page. Council meets at 6pm tonight and residents can't see the agenda. This is a legal notice issue!", 'cityclerk@harborpointcity.gov'],
            // expect: bulletins/bug, high, Dev Team (platform-wide delivery failure)
            ['No bulletins delivered since this morning', "None of our bulletins since 8am have been delivered. Delivery report shows 0 sent, 0 bounced, everything just queued. Includes a boil-water notice.", 'utilities@millbrookcity.gov'],
            // expect: bug, high, Dev Team (data loss)
            ['Form submissions disappeared', "About two weeks of submissions for our pothole report form are gone from the Submissions list. They were there on Friday.", 'publicworks@ashlandcounty.gov'],
            // expect: bug, medium, Dev Team
            ['Video and agenda timestamps out of sync', "Since the last update, clicking an agenda item in the video player jumps to the wrong part of the meeting, about 4 minutes off.", 'clerk@lindenvillage.gov'],
            // expect: account_billing, Billing (PO paperwork needs a human)
            ['Need invoice with new PO number', "Our fiscal year started and finance issued a new purchase order number. We need the renewal invoice reissued with PO 2027-0412.", 'procurement@redfordcounty.gov'],
            // expect: account_billing, Billing (contract change)
            ['Add the Library to our contract', "The public library wants to start using bulletins. Can we add them to our existing contract and what would it cost?", 'it.director@westportcity.gov'],
            // expect: records_requests/bug, Dev Team (released redaction incident)
            ['Redaction did not hide text', "We released documents for a records request and the requester says they can copy the text under the black redaction boxes. This is serious.", 'legal@claytoncounty.gov'],
            // expect: other, low (feature request)
            ['Feature request: Spanish bulletins', "Can bulletins be sent automatically in Spanish to subscribers who prefer it? A lot of our residents would benefit.", 'equity@sanmarcos-demo.gov'],

            // --- Ambiguous / tricky: expected needs_review ---

            // vague: no module mentioned
            ['Help', "It's broken again, same as last week. Need this fixed today.", 'frontdesk@townofhollis.gov'],
            // written by a resident, not agency staff
            ['I want to stop getting emails', "I'm a resident and keep getting emails from the city about council meetings. How do I make it stop?", 'bob.henderson1958@gmail.com'],
            // two unrelated issues
            ['Agenda and login issue', "Two problems: our mayor can't log in, and the agenda for Tuesday is showing last month's items. Which do I report first?", 'clerk@townofgreenridge.gov'],
            // sounds like a password problem, might be a compromised account
            ['Someone sent a bulletin from my account', "A bulletin I didn't write went out to 12,000 subscribers from my account at 2am. I can't log in now.", 'comms.director@marlowcity.gov'],
            // policy/legal question, not a product how-to
            ['Do we have to publish closed session minutes?', "Is CivicLink able to tell us whether closed session minutes legally need to be posted?", 'clerk@ferndalevillage.gov'],
            // angry, unspecified
            ['Very disappointed', "Your platform made us look terrible at last night's meeting in front of the whole community. I expect a call from your manager.", 'citymanager@belmontcity.gov'],
            // positive feedback
            ['Thanks for the new meeting portal', "Residents have been complimenting the new meeting portal. Great job, no issue to report!", 'clerk@silverlakecity.gov'],
            // non-English
            ['Formulario no envía', "Los residentes dicen que el formulario de solicitud de reciclaje no se envía. Aparece un error al final.", 'servicios@villadelsol-demo.gov'],
        ];
    }
}
