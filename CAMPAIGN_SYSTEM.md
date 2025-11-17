# Campaign Management System

A complete campaign management system for sending email and SMS campaigns to customers with RFM segmentation support.

## Features

### ✉️ **Multi-Channel Support**
- **Email**: Send campaigns via Mailgun with HTML content support
- **SMS**: Send campaigns via Twilio with 160-character message support

### 🎯 **Advanced Customer Targeting**
- **All Customers**: Send to everyone
- **By RFM Segment**: Target specific customer segments (Champions, Loyal Customers, etc.)
- **Custom Filters**: Combine country, labels, channels, and date ranges
- **Individual Customer**: Send to a specific customer

### 🪄 **Template Variables**
Use dynamic placeholders in your campaign content:
- `{{first_name}}` - Customer's first name
- `{{last_name}}` - Customer's last name
- `{{email}}` - Customer's email address
- `{{segment}}` - Customer's RFM segment
- `{{monetary}}` - Customer's monetary value
- `{{frequency}}` - Customer's purchase frequency

### 📊 **Campaign Analytics**
- Total recipients and sent count
- Open rate tracking (email campaigns)
- Click rate tracking (email campaigns)
- Delivery rate monitoring
- Event logging (opens, clicks, bounces, unsubscribes)

### 🧪 **Testing**
- Send test messages before launching campaigns
- Preview message content with sample customer data
- Verify email/SMS configuration

### 🗓️ **Scheduling**
- Send campaigns immediately
- Schedule campaigns for later
- Draft campaigns for review

## Setup

### 1. Configure Email (Mailgun)

Add to your `.env` file:

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your-mailgun-api-key
MAILGUN_ENDPOINT=api.mailgun.net
```

### 2. Configure SMS (Twilio)

Add to your `.env` file:

```env
TWILIO_SID=your-account-sid
TWILIO_AUTH_TOKEN=your-auth-token
TWILIO_FROM=+1234567890
```

### 3. Set Up Webhooks

#### Mailgun Webhooks
Configure Mailgun to send webhooks to:
```
https://your-domain.com/webhooks/mailgun
```

Events tracked: opened, clicked, bounced, failed, unsubscribed, complained

#### Twilio Webhooks
Configure Twilio to send status callbacks to:
```
https://your-domain.com/webhooks/twilio
```

### 4. Queue Configuration

Campaigns use Laravel queues for sending. Make sure your queue worker is running:

```bash
php artisan queue:work
```

For production, use Supervisor or Laravel Horizon.

## Usage

### Creating a Campaign

1. Navigate to **Marketing > Campaigns** in the Filament admin panel
2. Click **Create Campaign**
3. Follow the wizard:
   - **Step 1: Basic Information**
     - Enter campaign name
     - Select channel (Email or SMS)
     - Write subject line (email only)
     - Compose message content

   - **Step 2: Recipients**
     - Choose filter type
     - Select segments or configure custom filters
     - View live recipient count

   - **Step 3: Schedule & Send**
     - Choose to send now or schedule for later
     - Send test message to verify
     - Review campaign summary

4. Click **Create** to save as draft or schedule

### Sending a Campaign

1. View a draft campaign
2. Review all details and statistics
3. Click **Send Campaign** button
4. Confirm to queue the campaign for sending

### Viewing Campaign Results

- Open the campaign from the list
- View real-time statistics:
  - Total sent vs total recipients
  - Open rate (email)
  - Click rate (email)
  - Delivery rate

## Database Structure

### Tables Created

- **campaigns**: Campaign definitions and configuration
- **campaign_sends**: Individual message sends to customers
- **campaign_events**: Tracking events (opens, clicks, bounces, etc.)

### Relationships

```
Campaign
  ├── belongs to User (creator)
  ├── has many CampaignSend
  └── has many CampaignEvent (through CampaignSend)

CampaignSend
  ├── belongs to Campaign
  ├── belongs to Customer
  └── has many CampaignEvent

CampaignEvent
  └── belongs to CampaignSend
```

## Rate Limiting

The system includes built-in rate limiting to respect provider limits:
- **Email**: 100 messages per minute
- **SMS**: 30 messages per minute

Rates can be adjusted in [SendCampaignMessageJob.php](app/Jobs/SendCampaignMessageJob.php:32).

## Testing

Run the campaign tests:

```bash
php artisan test --filter=CampaignTest
```

## Security Considerations

1. **CSRF Protection**: Webhook routes should be excluded from CSRF verification
2. **Webhook Validation**: Consider adding signature validation for Mailgun/Twilio webhooks
3. **Rate Limiting**: API rate limiting is handled automatically
4. **Queue Security**: Ensure queue workers run with appropriate permissions

## Troubleshooting

### Emails not sending
- Check Mailgun credentials in `.env`
- Verify Mailgun domain is verified
- Check queue worker is running: `php artisan queue:work`
- Review logs: `storage/logs/laravel.log`

### SMS not sending
- Check Twilio credentials in `.env`
- Verify Twilio phone number is valid
- Check queue worker is running
- Ensure phone numbers are in E.164 format (+1234567890)

### Open tracking not working
- Verify tracking pixel exists: `public/images/pixel.png`
- Check route is accessible: `/campaign/track/open/{send}`
- Verify webhooks are configured in Mailgun

## API Integration

The campaign system can be integrated programmatically:

```php
use App\Services\CampaignService;
use App\Models\Campaign;

$campaignService = app(CampaignService::class);

// Get recipients for a campaign
$recipients = $campaignService->getRecipients($campaign);

// Send test message
$success = $campaignService->sendTestMessage($campaign, 'test@example.com');

// Process campaign (queue for sending)
$campaignService->processCampaign($campaign);
```

## Future Enhancements

- A/B testing support
- Campaign templates
- Automated drip campaigns
- Advanced analytics dashboard
- Link click tracking
- Unsubscribe management
- Campaign duplication
- Export campaign results to CSV

## Support

For issues or questions, please refer to the main application documentation or contact the development team.
