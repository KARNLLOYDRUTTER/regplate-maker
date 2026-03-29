# Reddit Business Ideas Scraper

Scrapes Reddit for pain points and tool requests related to selling tools/services to media businesses.

## Setup

1. Create a Reddit app at https://www.reddit.com/prefs/apps/ (choose "script" type)
2. Copy `.env.example` to `.env` and fill in your credentials
3. Install dependencies:

```bash
pip install -r requirements.txt
```

## Usage

```bash
# Load your credentials
export REDDIT_CLIENT_ID=your_id
export REDDIT_CLIENT_SECRET=your_secret

# Default run: browse subreddits + search, top posts from past month
python scraper.py

# Last week's hot posts, save as JSON only
python scraper.py --sort hot --time week --format json

# Search-only mode (faster, skips browsing)
python scraper.py --search-only --time year

# Custom subreddits
python scraper.py --subreddits Entrepreneur SaaS marketing

# More posts per subreddit
python scraper.py --limit 100 --time year
```

## What it does

**Phase 1 — Subreddit browsing:** Scans 16 relevant subreddits (entrepreneur, SaaS, marketing, media, podcasting, etc.) and filters posts matching ~40 demand-signal keywords like "need a tool", "would pay for", "media workflow", etc.

**Phase 2 — Targeted search:** Runs 12 specific search queries across those subreddits to catch posts that browsing might miss.

**Output:** Deduplicates results, ranks by relevance score, and outputs:
- A terminal summary of top 25 posts + trending keyword analysis
- `results.json` — full data with post text and top comments
- `results.csv` — spreadsheet-friendly overview

## Subreddits scraped

Entrepreneur, SaaS, smallbusiness, media, digital_marketing, marketing, socialmedia, startups, indiehackers, SideProject, content_marketing, advertising, MediaBusiness, videography, podcasting, youtubers
