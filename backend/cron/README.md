# Automated Notifications & Escalation System

This directory contains cron jobs for automated notifications and escalation management in the fault reporting system.

## Overview

The notification system automatically sends emails for job status changes and overdue job reminders. It includes:

- **Real-time notifications** when job statuses change
- **Quote response notifications** (accept/reject/re-request)
- **Overdue job reminders** for jobs inactive for more than 7 days
- **Comprehensive email templates** with professional styling

## Files

- `send-overdue-reminders.php` - Daily cron job for overdue job notifications

## Setup Cron Job

### Linux/Mac Setup
1. Edit your crontab: `crontab -e`
2. Add this line to run daily at 9 AM:
```
0 9 * * * /usr/bin/php /path/to/fault-reporter/backend/cron/send-overdue-reminders.php >> /path/to/fault-reporter/backend/cron/cron.log 2>&1
```

### Windows Scheduled Task
1. Open Task Scheduler
2. Create a new task
3. Set trigger to daily at 9 AM
4. Set action to run: `php.exe`
5. Add argument: `C:\path\to\fault-reporter\backend\cron\send-overdue-reminders.php`
6. Set working directory to cron folder

## Notification Types

### Job Status Change Notifications

Automatically sent when job status changes:

- **New Job**: Service providers get notified of new jobs
- **Assigned**: Technicians get notified when assigned to jobs
- **Started**: Clients get notified when work begins
- **Completed**: Clients get notified when work is done
- **Confirmed**: Providers get notified of work confirmation
- **Rejected**: Providers get notified if work is rejected
- **Incomplete**: Providers get notified of rework requests

### Quote Notifications

- **Quote Requested**: Providers get notified to submit quotes
- **Quote Accepted**: Providers get notified when quotes are accepted
- **Quote Rejected**: Providers get notified when quotes are rejected
- **Quote Revision**: Providers get notified of re-quote requests

### Overdue Reminders

- Sent daily for jobs inactive > 7 days
- Email goes to both client and provider
- Includes job details and status information

## Email Configuration

The system uses the existing email infrastructure. To verify emails are being sent:

1. Check the email logs at `/all-logs/mail.log`
2. Emails appear in the log with full content
3. For production, configure SMTP in your server

## Testing

### Test Overdue Reminders Manually
```bash
cd /path/to/fault-reporter/backend/cron
php send-overdue-reminders.php
```

### Test Specific Notifications
You can manually trigger notifications by:

1. Creating/changing job statuses in the UI
2. Using the quote response modal
3. The system will automatically send appropriate emails

## Notification Recipients

| Event Type | Recipients |
|------------|------------|
| New Job | Approved service providers for that client |
| Job Assigned | Assigned technician + provider contact |
| Job Started | Client user who reported the job |
| Job Completed | Client user who reported the job |
| Job Confirmed | Provider contact |
| Cannot Repair | Client user who reported the job |
| Job Rejected | Provider contact |
| Job Incomplete | Provider contact |
| Quote Requested | Provider contact |
| Quote Response | Provider contact |
| Overdue Job | Client reporter + provider contact |

## Configuration Options

The notification system is fully configurable through the code:

- **Overdue threshold**: 7 days (configurable in JOB_NOTIFICATIONS)
- **Email templates**: Customizable HTML templates
- **Recipient logic**: Easy to modify who gets notified for each event

## Troubleshooting

### Common Issues

1. **Emails not sending**: Check `/all-logs/mail.log` for errors
2. **Cron job not running**: Check system cron logs
3. **Incorrect recipients**: Verify database relationships
4. **Template errors**: Check email template HTML syntax

### Debug Mode

The system includes comprehensive logging:
- All emails logged to `/all-logs/mail.log`
- Cron job execution logged to `/all-logs/cron-overdue-reminders.log`
- Application errors logged to respective API log files

## Security Considerations

- All email content contains only public job information
- No sensitive user data is included in notifications
- Recipients are verified against database relationships
- Emails use the system's authenticated sending mechanism
