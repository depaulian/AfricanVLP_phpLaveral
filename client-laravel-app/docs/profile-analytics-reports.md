# Profile Analytics Reports

This document describes the profile analytics reporting system, including command usage, configuration options, and scheduled reports.

## Overview

The profile analytics reporting system provides comprehensive insights into user profile data, including completion scores, behavioral patterns, engagement metrics, and scoring analysis. Reports can be generated on-demand or automatically scheduled.

## Command Usage

### Generate Profile Analytics Report

```bash
php artisan profile:analytics-report [options]
```

#### Options

- `--type=TYPE` - Type of report to generate
  - `comprehensive` - Complete analytics including scoring, behavioral analysis, and engagement metrics
  - `summary` - High-level overview of key profile metrics
  - `behavioral` - Detailed analysis of user behavior patterns
  - `scoring` - Comprehensive profile scoring with detailed breakdowns

- `--period=PERIOD` - Time period for the report
  - `daily` - Last 24 hours
  - `weekly` - Last 7 days
  - `monthly` - Last 30 days (default)
  - `quarterly` - Last 90 days
  - `yearly` - Last 365 days

- `--format=FORMAT` - Output format
  - `json` - Machine-readable JSON format
  - `csv` - Comma-separated values for spreadsheet applications
  - `html` - Web-friendly HTML format with styling
  - `pdf` - Portable Document Format (requires PDF library)

- `--user=ID` - Generate report for specific user ID only
- `--email=EMAIL` - Email address to send the report to
- `--save` - Save report to storage
- `--output=PATH` - Custom output file path

#### Examples

```bash
# Generate a comprehensive weekly report in HTML format
php artisan profile:analytics-report --type=comprehensive --period=weekly --format=html --save

# Generate a summary report for a specific user
php artisan profile:analytics-report --type=summary --user=123 --format=json

# Generate and email a monthly behavioral report
php artisan profile:analytics-report --type=behavioral --period=monthly --format=csv --email=admin@example.com --save

# Generate a quarterly scoring report with custom output path
php artisan profile:analytics-report --type=scoring --period=quarterly --format=json --save --output=custom/quarterly_scores.json
```

### Cleanup Old Reports

```bash
php artisan profile:cleanup-reports [options]
```

#### Options

- `--days=DAYS` - Number of days to retain reports (overrides config)
- `--dry-run` - Show what would be deleted without actually deleting
- `--force` - Force cleanup without confirmation

#### Examples

```bash
# Clean up reports older than 30 days
php artisan profile:cleanup-reports --days=30

# Preview what would be deleted
php artisan profile:cleanup-reports --dry-run

# Force cleanup without confirmation
php artisan profile:cleanup-reports --force
```

## Scheduled Reports

The system automatically generates reports according to the following schedule:

- **Daily Summary** - Every day at 1:00 AM (JSON format)
- **Weekly Comprehensive** - Every Monday at 6:00 AM (HTML format)
- **Monthly Behavioral** - First day of each month at 7:00 AM (CSV format)
- **Quarterly Scoring** - First day of each quarter at 8:00 AM (JSON format)
- **Cleanup** - Every Sunday at 4:00 AM (removes reports older than retention period)

## Configuration

The system is configured through `config/profile_analytics.php`. Key configuration options include:

### Report Types

```php
'types' => [
    'comprehensive' => [
        'name' => 'Comprehensive Analytics',
        'includes' => ['analytics', 'scoring', 'behavioral'],
        'default_format' => 'html',
    ],
    // ... other types
],
```

### Storage Settings

```php
'storage' => [
    'disk' => 'local',
    'path' => 'reports/profile-analytics',
    'retention_days' => 90,
    'max_file_size' => 50 * 1024 * 1024, // 50MB
],
```

### Email Configuration

```php
'email' => [
    'enabled' => true,
    'from_address' => env('MAIL_FROM_ADDRESS'),
    'subject_prefix' => '[Analytics Report]',
    'max_attachment_size' => 25 * 1024 * 1024, // 25MB
],
```

### Performance Settings

```php
'performance' => [
    'batch_size' => 100,
    'memory_limit' => '512M',
    'timeout' => 300, // 5 minutes
    'enable_caching' => true,
],
```

## Environment Variables

You can customize the behavior using these environment variables:

```env
# Storage configuration
PROFILE_ANALYTICS_DISK=local
PROFILE_ANALYTICS_RETENTION_DAYS=90
PROFILE_ANALYTICS_MAX_FILE_SIZE=52428800

# Email configuration
PROFILE_ANALYTICS_EMAIL_ENABLED=true
PROFILE_ANALYTICS_FROM_EMAIL=analytics@example.com
PROFILE_ANALYTICS_SUBJECT_PREFIX="[Analytics Report]"

# Performance configuration
PROFILE_ANALYTICS_BATCH_SIZE=100
PROFILE_ANALYTICS_MEMORY_LIMIT=512M
PROFILE_ANALYTICS_TIMEOUT=300

# Security configuration
PROFILE_ANALYTICS_REQUIRE_AUTH=true
PROFILE_ANALYTICS_RATE_LIMIT_ENABLED=true
PROFILE_ANALYTICS_RATE_LIMIT_MAX=10

# Scheduled reports
PROFILE_ANALYTICS_SCHEDULED_ENABLED=true
PROFILE_ANALYTICS_DEFAULT_RECIPIENTS=admin@example.com

# Logging
PROFILE_ANALYTICS_LOGGING_ENABLED=true
PROFILE_ANALYTICS_LOG_LEVEL=info
```

## Report Formats

### JSON Format

Machine-readable format suitable for API consumption and data processing:

```json
{
  "metadata": {
    "report_type": "summary",
    "period": "weekly",
    "generated_at": "2024-01-15T10:30:00Z",
    "total_users": 150
  },
  "data": [
    {
      "user_id": 1,
      "user_name": "John Doe",
      "completion_score": 85.5,
      "engagement_level": 12
    }
  ],
  "summary": {
    "total_users_analyzed": 150,
    "average_completion": 72.3,
    "highly_engaged_users": 45
  }
}
```

### CSV Format

Spreadsheet-friendly format for data analysis:

```csv
user_id,user_name,email,completion_score,engagement_level,last_activity
1,"John Doe","john@example.com",85.5,12,"2024-01-15T09:15:00Z"
2,"Jane Smith","jane@example.com",92.1,8,"2024-01-14T16:22:00Z"
```

### HTML Format

Web-friendly format with styling for easy viewing:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Profile Analytics Report</title>
    <style>/* Styling */</style>
</head>
<body>
    <div class="header">
        <h1>Profile Analytics Report</h1>
        <p><strong>Type:</strong> Summary</p>
        <p><strong>Period:</strong> Weekly</p>
    </div>
    <!-- Report content -->
</body>
</html>
```

## Security Considerations

- Reports may contain sensitive user data - ensure proper access controls
- Email delivery should use secure SMTP connections
- Consider data anonymization for external sharing
- Implement rate limiting to prevent abuse
- Regular cleanup of old reports to manage storage

## Troubleshooting

### Common Issues

1. **Memory Limit Exceeded**
   - Increase `PROFILE_ANALYTICS_MEMORY_LIMIT`
   - Reduce `PROFILE_ANALYTICS_BATCH_SIZE`
   - Generate reports for smaller time periods

2. **Timeout Errors**
   - Increase `PROFILE_ANALYTICS_TIMEOUT`
   - Enable caching with `PROFILE_ANALYTICS_ENABLE_CACHING=true`
   - Consider running reports during off-peak hours

3. **Email Delivery Failures**
   - Check SMTP configuration
   - Verify attachment size limits
   - Ensure recipient email addresses are valid

4. **Storage Issues**
   - Check disk space availability
   - Verify storage permissions
   - Run cleanup command to free space

### Logging

The system logs all activities to help with troubleshooting:

```bash
# View recent logs
tail -f storage/logs/laravel.log | grep "Profile Analytics"

# Check for errors
grep "ERROR" storage/logs/laravel.log | grep "Profile Analytics"
```

## Performance Optimization

- Enable caching for frequently accessed data
- Use database indexes on activity log tables
- Consider running large reports asynchronously
- Implement data archiving for old activity logs
- Monitor memory usage and adjust batch sizes accordingly

## API Integration

Reports can be generated programmatically using the service classes:

```php
use App\Services\ProfileAnalyticsService;
use App\Services\ProfileScoringService;
use App\Services\BehavioralAnalyticsService;

// Generate analytics for a specific user
$analytics = app(ProfileAnalyticsService::class)->getUserProfileAnalytics($user);
$scoring = app(ProfileScoringService::class)->calculateComprehensiveScore($user);
$behavioral = app(BehavioralAnalyticsService::class)->analyzeUserBehavior($user);
```