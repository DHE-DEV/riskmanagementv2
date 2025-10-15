# RSS/Atom Feed API Documentation

## Overview

The Risk Management System provides comprehensive RSS and Atom feeds for monitoring events and updates. These feeds allow you to stay informed about risk management events through your favorite feed reader, automation tools, or custom integrations.

**Key Features:**
- Multiple feed formats (RSS 2.0 and Atom 1.0)
- Filtered feeds by country, event type, and priority
- Automatic caching for optimal performance (1-hour cache duration)
- Maximum 100 most recent events per feed
- Standards-compliant XML feeds
- Real-time updates when events are created or modified

**Update Frequency:**
- Feeds are cached for 1 hour for performance
- New events appear in feeds within 1 hour of creation
- Feeds automatically refresh when cache expires

---

## Available Feed Endpoints

### Base URL
All feed URLs are relative to your application's base URL. Replace `{BASE_URL}` with your actual domain.

Example: `https://yourdomain.com/feed/events.xml`

### 1. All Events Feed

Get all active, non-archived risk management events.

**RSS Format:**
```
GET {BASE_URL}/feed/events.xml
```

**Atom Format:**
```
GET {BASE_URL}/feed/events.atom
```

**Description:** Returns all active events across all countries, types, and priorities. Events are ordered by start date (most recent first) and limited to 100 items.

**Use Cases:**
- General monitoring of all events
- Dashboard displays
- Comprehensive event tracking
- Archival and analysis systems

---

### 2. Critical Events Feed

Get only high-priority and critical events requiring immediate attention.

**RSS Format:**
```
GET {BASE_URL}/feed/critical.xml
```

**Description:** Returns events with priority levels of "high" or "critical". Ideal for alerting systems and priority notifications.

**Use Cases:**
- Emergency alerting systems
- Management dashboards
- Priority notification services
- Crisis response monitoring

---

### 3. Events by Country

Get events filtered by specific country using ISO country codes.

**RSS Format:**
```
GET {BASE_URL}/feed/countries/{ISO_CODE}.xml
```

**Parameters:**
- `{ISO_CODE}`: Two-letter (ISO 3166-1 alpha-2) or three-letter (ISO 3166-1 alpha-3) country code
  - Examples: `DE`, `DEU` (Germany), `US`, `USA` (United States), `FR`, `FRA` (France)

**Example URLs:**
```
GET {BASE_URL}/feed/countries/DE.xml      # Events in Germany
GET {BASE_URL}/feed/countries/US.xml      # Events in United States
GET {BASE_URL}/feed/countries/JP.xml      # Events in Japan
GET {BASE_URL}/feed/countries/FRA.xml     # Events in France (ISO3)
```

**Use Cases:**
- Regional monitoring
- Country-specific dashboards
- Localized alert systems
- Geographic filtering

---

### 4. Events by Type

Get events filtered by specific event type.

**RSS Format:**
```
GET {BASE_URL}/feed/types/{TYPE_CODE}.xml
```

**Parameters:**
- `{TYPE_CODE}`: Event type code (lowercase)
  - Common types: `earthquake`, `hurricane`, `flood`, `wildfire`, `volcano`, `drought`, `exercise`, `tsunami`, `storm`, `heatwave`

**Example URLs:**
```
GET {BASE_URL}/feed/types/earthquake.xml  # Earthquake events only
GET {BASE_URL}/feed/types/flood.xml       # Flood events only
GET {BASE_URL}/feed/types/hurricane.xml   # Hurricane events only
GET {BASE_URL}/feed/types/wildfire.xml    # Wildfire events only
```

**Use Cases:**
- Specialized monitoring (e.g., only earthquakes)
- Type-specific alerting
- Filtered notifications
- Specialized analysis systems

---

### 5. Events by Region

Get events filtered by specific region ID.

**RSS Format:**
```
GET {BASE_URL}/feed/regions/{REGION_ID}.xml
```

**Parameters:**
- `{REGION_ID}`: Numeric region identifier

**Example URLs:**
```
GET {BASE_URL}/feed/regions/1.xml         # Events in region 1
GET {BASE_URL}/feed/regions/5.xml         # Events in region 5
```

**Use Cases:**
- Sub-national monitoring
- State/province-level tracking
- Regional crisis management
- Local government dashboards

---

## Feed Structure and Content

### RSS 2.0 Format

RSS feeds follow the RSS 2.0 specification with Dublin Core extensions.

**Channel Elements:**
- `title`: Feed title
- `link`: Link to application homepage
- `description`: Feed description
- `language`: Content language (en-us)
- `lastBuildDate`: When feed was last built
- `pubDate`: Feed publication date
- `atom:link`: Self-referential link to feed URL

**Item Elements:**
- `title`: Event title
- `link`: Direct link to event details
- `guid`: Unique identifier (permalink)
- `description`: Event description with additional details
- `pubDate`: Event creation date
- `category`: Event categories (priority, type)
- `dc:creator`: Event creator name

**Description Format:**
```
Event description text

Start: YYYY-MM-DD HH:MM | End: YYYY-MM-DD HH:MM | Priority: high | Countries: Germany, France
```

### Atom 1.0 Format

Atom feeds follow the Atom 1.0 specification.

**Feed Elements:**
- `title`: Feed title
- `subtitle`: Feed description
- `link[rel=alternate]`: Link to application
- `link[rel=self]`: Self-referential link to feed
- `id`: Unique feed identifier
- `updated`: Last update timestamp

**Entry Elements:**
- `title`: Event title
- `link`: Direct link to event
- `id`: Unique entry identifier
- `updated`: Last update timestamp
- `published`: Publication timestamp
- `content`: Event description and details
- `category`: Event categories with terms and labels
- `author/name`: Event creator

---

## Examples

### cURL Examples

**1. Fetch All Events (RSS):**
```bash
curl -X GET "https://yourdomain.com/feed/events.xml" \
  -H "Accept: application/rss+xml"
```

**2. Fetch Critical Events:**
```bash
curl -X GET "https://yourdomain.com/feed/critical.xml" \
  -H "Accept: application/rss+xml"
```

**3. Fetch Events for Germany:**
```bash
curl -X GET "https://yourdomain.com/feed/countries/DE.xml" \
  -H "Accept: application/rss+xml"
```

**4. Fetch All Events (Atom):**
```bash
curl -X GET "https://yourdomain.com/feed/events.atom" \
  -H "Accept: application/atom+xml"
```

**5. Parse with jq (convert to JSON):**
```bash
curl -s "https://yourdomain.com/feed/events.xml" | \
  xmllint --xpath "//item/title/text()" - | \
  jq -R -s -c 'split("\n")[:-1]'
```

### Python Example

```python
import feedparser

# Parse the feed
feed = feedparser.parse('https://yourdomain.com/feed/events.xml')

# Print feed information
print(f"Feed Title: {feed.feed.title}")
print(f"Feed Description: {feed.feed.description}")
print(f"Number of entries: {len(feed.entries)}")

# Iterate through entries
for entry in feed.entries:
    print(f"\nTitle: {entry.title}")
    print(f"Link: {entry.link}")
    print(f"Published: {entry.published}")
    print(f"Description: {entry.description}")

    # Print categories
    if hasattr(entry, 'tags'):
        categories = [tag.term for tag in entry.tags]
        print(f"Categories: {', '.join(categories)}")
```

### JavaScript/Node.js Example

```javascript
const Parser = require('rss-parser');
const parser = new Parser();

(async () => {
  try {
    const feed = await parser.parseURL('https://yourdomain.com/feed/events.xml');

    console.log(`Feed Title: ${feed.title}`);
    console.log(`Feed Description: ${feed.description}`);

    feed.items.forEach(item => {
      console.log('\n---');
      console.log(`Title: ${item.title}`);
      console.log(`Link: ${item.link}`);
      console.log(`Published: ${item.pubDate}`);
      console.log(`Description: ${item.contentSnippet}`);

      if (item.categories) {
        console.log(`Categories: ${item.categories.join(', ')}`);
      }
    });
  } catch (error) {
    console.error('Error fetching feed:', error);
  }
})();
```

---

## Subscribing in Feed Readers

### Popular Feed Readers

#### 1. Feedly

**Steps:**
1. Log in to [Feedly](https://feedly.com)
2. Click the "+" button or "Add Content"
3. Paste feed URL: `https://yourdomain.com/feed/events.xml`
4. Click "Follow"
5. Choose a folder/category for organization

**Pro Tips:**
- Create separate folders for different feed types (All Events, Critical, By Country)
- Use Feedly's filtering features to highlight critical items
- Enable notifications for the critical events feed

#### 2. NewsBlur

**Steps:**
1. Log in to [NewsBlur](https://newsblur.com)
2. Click "Add Site" button
3. Enter feed URL: `https://yourdomain.com/feed/events.xml`
4. Click "Add" and choose folder

**Pro Tips:**
- Use NewsBlur's training feature to prioritize important events
- Create filters based on keywords in event descriptions
- Utilize the mobile app for on-the-go monitoring

#### 3. Inoreader

**Steps:**
1. Log in to [Inoreader](https://www.inoreader.com)
2. Click "Add New" and select "Subscription"
3. Paste feed URL and click "Subscribe"
4. Organize into folders as needed

**Pro Tips:**
- Use Inoreader's rules to automatically tag or filter events
- Set up push notifications for critical feeds
- Create bundles for different geographic regions

#### 4. The Old Reader

**Steps:**
1. Log in to [The Old Reader](https://theoldreader.com)
2. Click "Add a subscription"
3. Enter feed URL and click "Subscribe"

**Pro Tips:**
- Use folders to organize different feed types
- Share important events with team members
- Use keyboard shortcuts for efficient reading

#### 5. NetNewsWire (macOS/iOS)

**Steps:**
1. Open NetNewsWire
2. File > New Feed (or Cmd+N)
3. Paste feed URL: `https://yourdomain.com/feed/events.xml`
4. Click "Add"

**Pro Tips:**
- Sync across devices using iCloud
- Use smart feeds for advanced filtering
- Enable notifications for critical feeds

#### 6. Outlook (Desktop/Web)

**Steps:**
1. Open Outlook
2. Right-click "RSS Subscriptions" folder
3. Select "Add a New RSS Feed"
4. Paste feed URL and click "Add"

**Pro Tips:**
- Create rules to move critical events to specific folders
- Use Outlook's categories for organization
- Set up alerts for high-priority items

---

## Automation Integration

### 1. Zapier Integration

**Create a Zap for RSS Feed Monitoring:**

**Trigger:** RSS by Zapier - New Item in Feed
- Feed URL: `https://yourdomain.com/feed/critical.xml`

**Actions (Examples):**

**A. Send Email Notifications:**
- Action: Gmail - Send Email
- To: `alerts@yourcompany.com`
- Subject: `New Critical Event: {{title}}`
- Body: `{{description}}`

**B. Post to Slack:**
- Action: Slack - Send Channel Message
- Channel: `#emergency-alerts`
- Message:
  ```
  New Critical Event: {{title}}
  {{link}}
  {{description}}
  ```

**C. Create Task:**
- Action: Trello - Create Card
- Board: "Emergency Response"
- List: "New Events"
- Title: `{{title}}`
- Description: `{{description}}\n\nLink: {{link}}`

**D. Log to Spreadsheet:**
- Action: Google Sheets - Create Spreadsheet Row
- Spreadsheet: "Event Log"
- Values: `{{pubDate}} | {{title}} | {{link}} | {{categories}}`

**Multi-Step Zap Example (Critical Events):**
1. Trigger: New item in critical events feed
2. Filter: Only continue if categories contain "high"
3. Send Slack notification to #emergency-alerts
4. Send email to on-call team
5. Create Trello card in Emergency Response board
6. Log entry in Google Sheets

### 2. Make.com (formerly Integromat) Integration

**Scenario: Monitor Events and Create Tasks**

**Modules:**

1. **RSS > Watch RSS feed items**
   - URL: `https://yourdomain.com/feed/events.xml`
   - Maximum number of returned results: 10

2. **Filter > Only process if:**
   - Category contains "high" OR "critical"

3. **Slack > Create a message**
   - Channel: emergency-alerts
   - Text: `New Event: {{item.title}}`

4. **Asana > Create a task**
   - Project: Emergency Response
   - Name: `{{item.title}}`
   - Notes: `{{item.description}}`

### 3. IFTTT Integration

**Applet: RSS Feed to Notification**

**Trigger:** Feed - New feed item
- Feed URL: `https://yourdomain.com/feed/critical.xml`

**Actions:**
- **Notifications:** Send a notification
  - Message: `Critical Event: {{EntryTitle}}`

- **Email:** Send me an email
  - Subject: `New Critical Event Alert`
  - Body: `{{EntryTitle}}\n\n{{EntryContent}}\n\n{{EntryUrl}}`

### 4. n8n Integration (Self-Hosted)

**Workflow: RSS to Database**

```json
{
  "nodes": [
    {
      "name": "RSS Feed Reader",
      "type": "n8n-nodes-base.rssFeedRead",
      "parameters": {
        "url": "https://yourdomain.com/feed/events.xml"
      }
    },
    {
      "name": "Filter Critical",
      "type": "n8n-nodes-base.if",
      "parameters": {
        "conditions": {
          "string": [
            {
              "value1": "={{$json[\"categories\"]}}",
              "operation": "contains",
              "value2": "high"
            }
          ]
        }
      }
    },
    {
      "name": "Save to PostgreSQL",
      "type": "n8n-nodes-base.postgres",
      "parameters": {
        "operation": "insert",
        "table": "events",
        "columns": "title, description, link, pub_date"
      }
    }
  ]
}
```

### 5. Power Automate (Microsoft)

**Flow: RSS Feed to Teams**

1. **Trigger:** RSS - When a feed item is published
   - Feed URL: `https://yourdomain.com/feed/critical.xml`

2. **Action:** Microsoft Teams - Post message
   - Team: Emergency Response
   - Channel: Alerts
   - Message:
     ```
     **New Critical Event**

     {{FeedTitle}}

     {{FeedDescription}}

     [View Details]({{FeedPrimaryLink}})
     ```

---

## OPML Export for Multiple Feeds

### What is OPML?

OPML (Outline Processor Markup Language) is a standard format for exchanging lists of RSS feeds. Most feed readers support importing OPML files.

### Sample OPML File

Save this as `risk-management-feeds.opml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<opml version="2.0">
  <head>
    <title>Risk Management Feeds</title>
    <dateCreated>2025-10-15T10:00:00Z</dateCreated>
  </head>
  <body>
    <outline text="Risk Management" title="Risk Management">

      <!-- General Feeds -->
      <outline type="rss"
               text="All Events (RSS)"
               title="All Events (RSS)"
               xmlUrl="https://yourdomain.com/feed/events.xml"
               htmlUrl="https://yourdomain.com/" />

      <outline type="rss"
               text="All Events (Atom)"
               title="All Events (Atom)"
               xmlUrl="https://yourdomain.com/feed/events.atom"
               htmlUrl="https://yourdomain.com/" />

      <outline type="rss"
               text="Critical Events Only"
               title="Critical Events Only"
               xmlUrl="https://yourdomain.com/feed/critical.xml"
               htmlUrl="https://yourdomain.com/" />

      <!-- Country-Specific Feeds -->
      <outline text="By Country" title="By Country">
        <outline type="rss"
                 text="Germany (DE)"
                 title="Events in Germany"
                 xmlUrl="https://yourdomain.com/feed/countries/DE.xml"
                 htmlUrl="https://yourdomain.com/" />

        <outline type="rss"
                 text="United States (US)"
                 title="Events in United States"
                 xmlUrl="https://yourdomain.com/feed/countries/US.xml"
                 htmlUrl="https://yourdomain.com/" />

        <outline type="rss"
                 text="United Kingdom (GB)"
                 title="Events in United Kingdom"
                 xmlUrl="https://yourdomain.com/feed/countries/GB.xml"
                 htmlUrl="https://yourdomain.com/" />

        <outline type="rss"
                 text="France (FR)"
                 title="Events in France"
                 xmlUrl="https://yourdomain.com/feed/countries/FR.xml"
                 htmlUrl="https://yourdomain.com/" />

        <outline type="rss"
                 text="Japan (JP)"
                 title="Events in Japan"
                 xmlUrl="https://yourdomain.com/feed/countries/JP.xml"
                 htmlUrl="https://yourdomain.com/" />
      </outline>

      <!-- Event Type Feeds -->
      <outline text="By Event Type" title="By Event Type">
        <outline type="rss"
                 text="Earthquakes"
                 title="Earthquake Events"
                 xmlUrl="https://yourdomain.com/feed/types/earthquake.xml"
                 htmlUrl="https://yourdomain.com/" />

        <outline type="rss"
                 text="Floods"
                 title="Flood Events"
                 xmlUrl="https://yourdomain.com/feed/types/flood.xml"
                 htmlUrl="https://yourdomain.com/" />

        <outline type="rss"
                 text="Hurricanes"
                 title="Hurricane Events"
                 xmlUrl="https://yourdomain.com/feed/types/hurricane.xml"
                 htmlUrl="https://yourdomain.com/" />

        <outline type="rss"
                 text="Wildfires"
                 title="Wildfire Events"
                 xmlUrl="https://yourdomain.com/feed/types/wildfire.xml"
                 htmlUrl="https://yourdomain.com/" />

        <outline type="rss"
                 text="Volcanoes"
                 title="Volcanic Events"
                 xmlUrl="https://yourdomain.com/feed/types/volcano.xml"
                 htmlUrl="https://yourdomain.com/" />
      </outline>

    </outline>
  </body>
</opml>
```

### How to Import OPML

**Most Feed Readers:**
1. Look for "Import" or "Import OPML" in settings
2. Select the OPML file
3. Confirm import
4. Feeds will be organized in folders as specified

**Command Line (using wget):**
```bash
# Download OPML file
wget https://yourdomain.com/feeds.opml -O risk-feeds.opml

# Import in your reader (example with newsboat)
cat risk-feeds.opml >> ~/.newsboat/urls
```

---

## Feed Best Practices

### For Feed Consumers

#### 1. Polling Frequency

**Recommended:**
- General monitoring: Every 15-30 minutes
- Critical events: Every 5-10 minutes
- Low-priority feeds: Every 1-2 hours

**Considerations:**
- Feeds are cached for 1 hour server-side
- Polling more frequently than every 5 minutes provides no benefit
- Consider feed reader's rate limiting and server load

#### 2. Error Handling

```python
import feedparser
import time

def fetch_feed_with_retry(url, max_retries=3):
    for attempt in range(max_retries):
        try:
            feed = feedparser.parse(url)

            # Check for errors
            if feed.bozo:
                print(f"Feed parse warning: {feed.bozo_exception}")

            # Check HTTP status
            if hasattr(feed, 'status'):
                if feed.status == 200:
                    return feed
                elif feed.status == 404:
                    print("Feed not found")
                    return None
                elif feed.status >= 500:
                    print(f"Server error: {feed.status}")
                    if attempt < max_retries - 1:
                        time.sleep(2 ** attempt)  # Exponential backoff
                        continue

            return feed

        except Exception as e:
            print(f"Error fetching feed: {e}")
            if attempt < max_retries - 1:
                time.sleep(2 ** attempt)

    return None
```

#### 3. Duplicate Detection

```python
seen_guids = set()

def process_feed_items(feed):
    new_items = []

    for entry in feed.entries:
        guid = entry.get('id') or entry.get('link')

        if guid not in seen_guids:
            seen_guids.add(guid)
            new_items.append(entry)

    return new_items
```

#### 4. Content Filtering

```python
def filter_by_keywords(entries, keywords):
    filtered = []

    for entry in entries:
        title = entry.title.lower()
        description = entry.get('description', '').lower()
        content = f"{title} {description}"

        if any(keyword.lower() in content for keyword in keywords):
            filtered.append(entry)

    return filtered

# Example usage
keywords = ['flood', 'earthquake', 'high priority']
critical_events = filter_by_keywords(feed.entries, keywords)
```

#### 5. Conditional Processing

```python
def process_by_category(entry):
    categories = [tag.term for tag in entry.get('tags', [])]

    if 'Priority: High' in categories or 'Priority: Critical' in categories:
        send_urgent_notification(entry)
    elif 'earthquake' in categories:
        log_earthquake_event(entry)
    else:
        log_general_event(entry)
```

### For Feed Publishers

#### 1. Cache Management

- Feeds are automatically cached for 1 hour
- Clear cache when critical updates are made
- Consider implementing manual cache clear endpoints

#### 2. Content Guidelines

**DO:**
- Keep descriptions concise but informative
- Include relevant metadata (dates, locations, priority)
- Use consistent categorization
- Maintain valid XML structure

**DON'T:**
- Include HTML in RSS descriptions (it will be stripped)
- Exceed 100 items per feed (current limit)
- Use special characters without proper XML escaping

#### 3. Testing Feeds

```bash
# Validate RSS feed
curl -s "https://yourdomain.com/feed/events.xml" | xmllint --noout -

# Validate Atom feed
curl -s "https://yourdomain.com/feed/events.atom" | xmllint --noout -

# Check feed with W3C Validator
curl -s "https://validator.w3.org/feed/check.cgi?url=https://yourdomain.com/feed/events.xml"
```

---

## Troubleshooting

### Common Issues and Solutions

#### Issue 1: Feed Not Found (404)

**Symptoms:**
- HTTP 404 error when accessing feed URL
- "Feed not found" message in feed reader

**Solutions:**
1. Verify the URL is correct
2. Check that the feed route is registered:
   ```bash
   php artisan route:list | grep feed
   ```
3. Clear route cache:
   ```bash
   php artisan route:clear
   php artisan route:cache
   ```

#### Issue 2: Empty Feed

**Symptoms:**
- Feed loads but contains no items
- Valid XML but empty channel/feed

**Possible Causes:**
1. No active events in database
2. All events are archived
3. Filter conditions exclude all events

**Solutions:**
1. Check database for active events:
   ```bash
   php artisan tinker
   >>> \App\Models\CustomEvent::active()->notArchived()->count()
   ```
2. Verify event filters in feed configuration
3. Check event visibility rules

#### Issue 3: Parse Errors

**Symptoms:**
- Feed reader reports parse errors
- Invalid XML structure warnings

**Solutions:**
1. Validate XML structure:
   ```bash
   curl -s "URL" | xmllint --noout -
   ```
2. Check for special characters in event data
3. Verify XML escaping is working correctly

#### Issue 4: Outdated Content

**Symptoms:**
- Feed shows old events
- Recent events not appearing

**Solutions:**
1. Clear feed cache:
   ```bash
   php artisan cache:clear
   ```
2. Wait for cache to expire (1 hour)
3. Check event `updated_at` timestamps

#### Issue 5: Country Feed Not Working

**Symptoms:**
- 404 or error for country-specific feeds
- "Country not found" message

**Solutions:**
1. Verify ISO code is correct (use 2-letter or 3-letter codes)
2. Check country exists in database:
   ```bash
   php artisan tinker
   >>> \App\Models\Country::where('iso_code', 'DE')->first()
   ```
3. Ensure events are associated with countries

#### Issue 6: Event Type Feed Empty

**Symptoms:**
- Type-specific feed loads but is empty
- Valid XML but no items

**Solutions:**
1. Verify event type code:
   ```bash
   php artisan tinker
   >>> \App\Models\EventType::where('code', 'earthquake')->first()
   ```
2. Check that event type is active
3. Verify events are tagged with this type

### Debug Commands

**1. Test Feed Generation:**
```bash
# Test in tinker
php artisan tinker

# Get feed items
>>> $events = \App\Models\CustomEvent::getFeedItems();
>>> $events->count();

# Get critical items
>>> $critical = \App\Models\CustomEvent::getCriticalFeedItems();
>>> $critical->count();
```

**2. Check Feed Cache:**
```bash
php artisan tinker

# Check if feed is cached
>>> Cache::has('feed:all_events:rss');

# Get cached content
>>> Cache::get('feed:all_events:rss');

# Clear specific feed cache
>>> Cache::forget('feed:all_events:rss');
```

**3. Monitor Feed Access:**
```bash
# Check web server logs
tail -f storage/logs/laravel.log | grep feed

# Monitor in real-time
php artisan pail --filter=feed
```

### Performance Issues

**Symptoms:**
- Slow feed loading
- Timeout errors
- High server load

**Solutions:**

1. **Optimize Database Queries:**
   - Ensure proper indexing on `is_active`, `archived`, `country_id`
   - Use query optimization:
     ```sql
     CREATE INDEX idx_custom_events_active ON custom_events(is_active, archived);
     CREATE INDEX idx_custom_events_country ON custom_events(country_id);
     CREATE INDEX idx_custom_events_priority ON custom_events(priority);
     ```

2. **Extend Cache Duration:**
   - Modify `CACHE_DURATION` in FeedController for less frequent updates
   ```php
   private const CACHE_DURATION = 7200; // 2 hours
   ```

3. **Reduce Feed Size:**
   - Decrease `MAX_ITEMS` limit
   ```php
   private const MAX_ITEMS = 50; // Instead of 100
   ```

4. **Enable Response Compression:**
   - Add gzip compression in web server configuration
   - For nginx:
     ```nginx
     gzip on;
     gzip_types application/rss+xml application/atom+xml application/xml;
     ```

---

## Technical Details

### Feed Format Comparison

| Feature | RSS 2.0 | Atom 1.0 |
|---------|---------|----------|
| Standard | RSS 2.0 Specification | IETF RFC 4287 |
| Date Format | RFC 2822 | ISO 8601 (RFC 3339) |
| Content Type | `application/rss+xml` | `application/atom+xml` |
| Namespace | Optional | Required |
| ID Requirement | Optional (guid) | Required (id) |
| Link Handling | Single link per item | Multiple links with relations |
| Content Element | `description` | `content` with type attribute |
| Best For | Broad compatibility | Modern applications |

### Update Mechanism

**How Updates Work:**

1. **Event Creation:**
   - New event is created in database
   - Cache for relevant feeds is NOT automatically cleared
   - Event appears in feeds within 1 hour (cache duration)

2. **Event Modification:**
   - Event `updated_at` timestamp is changed
   - Cached feed still shows old data until expiration
   - New data appears after cache expires

3. **Manual Cache Clear:**
   - Administrator can clear cache manually
   - Forces immediate feed regeneration
   ```bash
   php artisan cache:clear
   ```

### Caching Strategy

**Cache Keys:**
- All events (RSS): `feed:all_events:rss`
- All events (Atom): `feed:all_events:atom`
- Critical events: `feed:critical_events:rss`
- Country feed: `feed:country:{ISO_CODE}:rss`
- Type feed: `feed:type:{TYPE_CODE}:rss`
- Region feed: `feed:region:{REGION_ID}:rss`

**Cache Driver:**
- Configured in `config/cache.php`
- Supports: file, database, redis, memcached
- Recommended: Redis for high-traffic sites

### Data Sources

**Event Fields Included in Feeds:**
- `id`: Unique event identifier
- `title`: Event title
- `description`: Event description (HTML stripped)
- `start_date`: Event start time
- `end_date`: Event end time
- `priority`: Priority level (info, low, medium, high)
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp
- `creator`: Event creator name
- `countries`: Associated countries
- `eventTypes`: Associated event types
- `eventCategory`: Event category

**Event Visibility Rules:**
- Only active events (`is_active = true`)
- Only non-archived events (`archived = false`)
- Ordered by start date (descending)
- Limited to 100 most recent events

### Security Considerations

1. **No Authentication Required:**
   - Feeds are publicly accessible
   - No API key or authentication needed
   - Consider implementing rate limiting for high-traffic scenarios

2. **XML Injection Prevention:**
   - All user-generated content is XML-escaped
   - Uses `htmlspecialchars()` with `ENT_XML1` flag
   - Prevents XSS and XML injection attacks

3. **SQL Injection Prevention:**
   - Uses Laravel's query builder with parameter binding
   - All database queries are sanitized
   - Country codes and type codes are validated

4. **Rate Limiting:**
   - Consider implementing rate limiting in production
   - Example nginx configuration:
     ```nginx
     limit_req_zone $binary_remote_addr zone=feed:10m rate=10r/m;

     location /feed/ {
         limit_req zone=feed burst=5;
     }
     ```

---

## Advanced Use Cases

### 1. Event Aggregation Dashboard

Combine multiple feeds to create a comprehensive dashboard:

```javascript
const feeds = [
  'https://yourdomain.com/feed/events.xml',
  'https://yourdomain.com/feed/critical.xml',
  'https://yourdomain.com/feed/countries/US.xml'
];

async function aggregateFeeds() {
  const allEvents = [];

  for (const feedUrl of feeds) {
    const feed = await parser.parseURL(feedUrl);
    allEvents.push(...feed.items);
  }

  // Remove duplicates by guid
  const uniqueEvents = Array.from(
    new Map(allEvents.map(item => [item.guid, item])).values()
  );

  // Sort by date
  uniqueEvents.sort((a, b) => new Date(b.pubDate) - new Date(a.pubDate));

  return uniqueEvents;
}
```

### 2. Automated Alerting System

Monitor critical events and send alerts:

```python
import feedparser
import smtplib
from email.mime.text import MIMEText
import time

FEED_URL = 'https://yourdomain.com/feed/critical.xml'
CHECK_INTERVAL = 300  # 5 minutes
seen_guids = set()

def send_alert(event):
    msg = MIMEText(f"""
    Critical Event Alert

    Title: {event.title}
    Description: {event.description}
    Link: {event.link}
    Published: {event.published}
    """)

    msg['Subject'] = f"ALERT: {event.title}"
    msg['From'] = 'alerts@yourdomain.com'
    msg['To'] = 'team@yourdomain.com'

    with smtplib.SMTP('localhost') as server:
        server.send_message(msg)

def monitor_feed():
    global seen_guids

    while True:
        try:
            feed = feedparser.parse(FEED_URL)

            for entry in feed.entries:
                guid = entry.id or entry.link

                if guid not in seen_guids:
                    seen_guids.add(guid)
                    print(f"New critical event: {entry.title}")
                    send_alert(entry)

            time.sleep(CHECK_INTERVAL)

        except Exception as e:
            print(f"Error monitoring feed: {e}")
            time.sleep(60)

if __name__ == '__main__':
    monitor_feed()
```

### 3. Multi-Country Monitoring

Monitor multiple countries simultaneously:

```python
COUNTRIES = ['DE', 'FR', 'GB', 'US', 'JP']

def monitor_countries():
    for country_code in COUNTRIES:
        feed_url = f'https://yourdomain.com/feed/countries/{country_code}.xml'
        feed = feedparser.parse(feed_url)

        print(f"\n{country_code}: {len(feed.entries)} events")

        for entry in feed.entries[:3]:  # Show top 3
            print(f"  - {entry.title}")
```

### 4. Historical Data Collection

Collect and archive feed data over time:

```python
import sqlite3
import feedparser
from datetime import datetime

def archive_feed(feed_url, db_path='events.db'):
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()

    # Create table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS events (
            guid TEXT PRIMARY KEY,
            title TEXT,
            description TEXT,
            link TEXT,
            published TIMESTAMP,
            categories TEXT,
            fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ''')

    feed = feedparser.parse(feed_url)

    for entry in feed.entries:
        guid = entry.id or entry.link
        categories = ', '.join([tag.term for tag in entry.get('tags', [])])

        cursor.execute('''
            INSERT OR IGNORE INTO events
            (guid, title, description, link, published, categories)
            VALUES (?, ?, ?, ?, ?, ?)
        ''', (
            guid,
            entry.title,
            entry.get('description', ''),
            entry.link,
            entry.published,
            categories
        ))

    conn.commit()
    conn.close()

# Run daily
import schedule

schedule.every().day.at("00:00").do(
    lambda: archive_feed('https://yourdomain.com/feed/events.xml')
)
```

### 5. Custom Feed Filtering Service

Create a custom filtering proxy:

```python
from flask import Flask, Response
import feedparser
from feedgen.feed import FeedGenerator

app = Flask(__name__)

@app.route('/filtered/<keyword>')
def filtered_feed(keyword):
    # Fetch original feed
    feed = feedparser.parse('https://yourdomain.com/feed/events.xml')

    # Create new feed
    fg = FeedGenerator()
    fg.title(f'Filtered Events: {keyword}')
    fg.link(href='https://yourdomain.com', rel='alternate')
    fg.description(f'Events filtered by keyword: {keyword}')

    # Filter and add entries
    for entry in feed.entries:
        content = f"{entry.title} {entry.get('description', '')}".lower()

        if keyword.lower() in content:
            fe = fg.add_entry()
            fe.id(entry.link)
            fe.title(entry.title)
            fe.link(href=entry.link)
            fe.description(entry.get('description', ''))
            fe.published(entry.published)

    return Response(fg.rss_str(), mimetype='application/rss+xml')

if __name__ == '__main__':
    app.run()
```

---

## API Changelog

### Version 1.0 (Current)
- Initial release
- RSS 2.0 and Atom 1.0 support
- All events feed
- Critical events feed
- Country-specific feeds
- Event type feeds
- Region-specific feeds
- 1-hour caching
- 100 items per feed limit

### Planned Features
- JSON Feed format support
- Webhook notifications
- Real-time updates via WebSockets
- Custom date range filtering
- Severity-based filtering
- Multi-country filtering in single feed
- Podcast/media enclosures for events with media

---

## Support and Contact

For issues, questions, or feature requests:

- **Documentation:** This file (FEED_API.md)
- **System Documentation:** See Benutzeranleitung.md
- **Technical Support:** Contact your system administrator
- **Bug Reports:** Use your internal issue tracking system

---

## Standards and Specifications

This feed implementation follows these standards:

- **RSS 2.0:** [RSS 2.0 Specification](https://www.rssboard.org/rss-specification)
- **Atom 1.0:** [IETF RFC 4287](https://tools.ietf.org/html/rfc4287)
- **Dublin Core:** [Dublin Core Metadata](https://www.dublincore.org/specifications/dublin-core/)
- **OPML 2.0:** [OPML 2.0 Specification](http://opml.org/spec2.opml)

---

*Last Updated: 2025-10-15*
*Version: 1.0*
