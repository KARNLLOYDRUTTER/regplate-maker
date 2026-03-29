#!/usr/bin/env python3
"""
Reddit Scraper for Media Business Tool Ideas

Scrapes relevant subreddits for posts about pain points, tool requests,
and business ideas related to selling tools/services to media businesses.
"""

import argparse
import csv
import json
import os
import re
import sys
from datetime import datetime, timezone

try:
    import praw
except ImportError:
    sys.exit("praw not installed. Run: pip install -r requirements.txt")


# Subreddits to scrape — mix of business, SaaS, media, and entrepreneur communities
SUBREDDITS = [
    "Entrepreneur",
    "SaaS",
    "smallbusiness",
    "media",
    "digital_marketing",
    "marketing",
    "socialmedia",
    "startups",
    "indiehackers",
    "SideProject",
    "content_marketing",
    "advertising",
    "MediaBusiness",
    "videography",
    "podcasting",
    "youtubers",
]

# Keywords that signal a potential business/tool opportunity
KEYWORDS = [
    # Pain points
    "need a tool",
    "looking for a tool",
    "wish there was",
    "anyone know a tool",
    "is there a tool",
    "pain point",
    "struggle with",
    "frustrated with",
    "waste time on",
    "manually doing",
    "no good solution",
    "can't find",
    # Business/monetisation signals
    "would pay for",
    "willing to pay",
    "shut up and take my money",
    "someone should build",
    "business idea",
    "money making",
    "side hustle",
    "SaaS idea",
    "tool idea",
    "product idea",
    "startup idea",
    # Media-specific
    "media workflow",
    "content creation tool",
    "publishing tool",
    "media management",
    "content scheduling",
    "media analytics",
    "ad tech",
    "media buying",
    "content distribution",
    "media agency",
    "video editing tool",
    "podcast tool",
    "newsletter tool",
    "social media tool",
    "media automation",
]


def build_reddit_client():
    """Create a PRAW Reddit client from environment variables."""
    client_id = os.environ.get("REDDIT_CLIENT_ID")
    client_secret = os.environ.get("REDDIT_CLIENT_SECRET")
    user_agent = os.environ.get("REDDIT_USER_AGENT", "BusinessIdeaScraper/1.0")

    if not client_id or not client_secret:
        sys.exit(
            "Set REDDIT_CLIENT_ID and REDDIT_CLIENT_SECRET env vars.\n"
            "Create an app at https://www.reddit.com/prefs/apps/"
        )

    return praw.Reddit(
        client_id=client_id,
        client_secret=client_secret,
        user_agent=user_agent,
    )


def matches_keywords(text, keywords):
    """Return list of keywords found in text (case-insensitive)."""
    text_lower = text.lower()
    return [kw for kw in keywords if kw.lower() in text_lower]


def relevance_score(post_text, matched_keywords):
    """Simple relevance score: more keyword hits + longer discussion = higher."""
    keyword_score = len(matched_keywords) * 10
    length_score = min(len(post_text) / 200, 10)  # cap at 10
    return round(keyword_score + length_score, 1)


def scrape_subreddit(reddit, subreddit_name, limit, time_filter, sort):
    """Scrape a single subreddit for relevant posts."""
    results = []
    try:
        subreddit = reddit.subreddit(subreddit_name)
        if sort == "top":
            posts = subreddit.top(time_filter=time_filter, limit=limit)
        elif sort == "hot":
            posts = subreddit.hot(limit=limit)
        elif sort == "new":
            posts = subreddit.new(limit=limit)
        else:
            posts = subreddit.hot(limit=limit)

        for post in posts:
            full_text = f"{post.title} {post.selftext}"
            matched = matches_keywords(full_text, KEYWORDS)
            if not matched:
                continue

            score = relevance_score(full_text, matched)

            # Grab top comments for extra context
            post.comments.replace_more(limit=0)
            top_comments = []
            for comment in post.comments[:5]:
                comment_matched = matches_keywords(comment.body, KEYWORDS)
                if comment_matched:
                    top_comments.append({
                        "body": comment.body[:500],
                        "score": comment.score,
                        "keywords": comment_matched,
                    })

            created = datetime.fromtimestamp(post.created_utc, tz=timezone.utc)

            results.append({
                "subreddit": subreddit_name,
                "title": post.title,
                "selftext": post.selftext[:2000],
                "url": f"https://reddit.com{post.permalink}",
                "post_score": post.score,
                "num_comments": post.num_comments,
                "created_utc": created.isoformat(),
                "matched_keywords": matched,
                "relevance_score": score,
                "top_comments": top_comments,
            })

    except Exception as e:
        print(f"  [!] Error scraping r/{subreddit_name}: {e}", file=sys.stderr)

    return results


def scrape_search(reddit, query, subreddits, limit, time_filter, sort):
    """Use Reddit search API to find posts matching a query across subreddits."""
    results = []
    sub_str = "+".join(subreddits)
    try:
        subreddit = reddit.subreddit(sub_str)
        for post in subreddit.search(query, sort=sort, time_filter=time_filter, limit=limit):
            full_text = f"{post.title} {post.selftext}"
            matched = matches_keywords(full_text, KEYWORDS)
            # For search results, include even without keyword match since the query itself is relevant
            if not matched:
                matched = [f"search:{query}"]

            score = relevance_score(full_text, matched)
            created = datetime.fromtimestamp(post.created_utc, tz=timezone.utc)

            results.append({
                "subreddit": str(post.subreddit),
                "title": post.title,
                "selftext": post.selftext[:2000],
                "url": f"https://reddit.com{post.permalink}",
                "post_score": post.score,
                "num_comments": post.num_comments,
                "created_utc": created.isoformat(),
                "matched_keywords": matched,
                "relevance_score": score,
                "top_comments": [],
            })
    except Exception as e:
        print(f"  [!] Search error for '{query}': {e}", file=sys.stderr)

    return results


def deduplicate(results):
    """Remove duplicate posts by URL."""
    seen = set()
    unique = []
    for r in results:
        if r["url"] not in seen:
            seen.add(r["url"])
            unique.append(r)
    return unique


def save_json(results, path):
    with open(path, "w") as f:
        json.dump(results, f, indent=2, ensure_ascii=False)
    print(f"Saved {len(results)} results to {path}")


def save_csv(results, path):
    if not results:
        print("No results to save.")
        return
    fieldnames = [
        "subreddit", "title", "url", "post_score", "num_comments",
        "created_utc", "matched_keywords", "relevance_score",
    ]
    with open(path, "w", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction="ignore")
        writer.writeheader()
        for r in results:
            row = dict(r)
            row["matched_keywords"] = ", ".join(row["matched_keywords"])
            writer.writerow(row)
    print(f"Saved {len(results)} results to {path}")


def print_summary(results):
    """Print a human-readable summary of top findings."""
    print(f"\n{'='*70}")
    print(f"  REDDIT BUSINESS IDEAS SCRAPER — {len(results)} relevant posts found")
    print(f"{'='*70}\n")

    # Sort by relevance
    sorted_results = sorted(results, key=lambda x: x["relevance_score"], reverse=True)

    for i, r in enumerate(sorted_results[:25], 1):
        print(f"{i:2}. [{r['relevance_score']:4.1f}] r/{r['subreddit']}")
        print(f"    {r['title']}")
        print(f"    Score: {r['post_score']} | Comments: {r['num_comments']} | {r['created_utc'][:10]}")
        print(f"    Keywords: {', '.join(r['matched_keywords'][:5])}")
        print(f"    {r['url']}")
        if r.get("top_comments"):
            print(f"    💬 {len(r['top_comments'])} relevant comments")
        print()

    # Keyword frequency analysis
    keyword_counts = {}
    for r in results:
        for kw in r["matched_keywords"]:
            keyword_counts[kw] = keyword_counts.get(kw, 0) + 1

    print(f"\n{'='*70}")
    print("  TOP TRENDING KEYWORDS (demand signals)")
    print(f"{'='*70}")
    for kw, count in sorted(keyword_counts.items(), key=lambda x: x[1], reverse=True)[:15]:
        bar = "#" * min(count, 40)
        print(f"  {kw:30s} {count:3d} {bar}")
    print()


def main():
    parser = argparse.ArgumentParser(description="Scrape Reddit for media business tool ideas")
    parser.add_argument("--limit", type=int, default=50, help="Posts per subreddit (default: 50)")
    parser.add_argument("--time", default="month", choices=["hour", "day", "week", "month", "year", "all"],
                        help="Time filter for top/search (default: month)")
    parser.add_argument("--sort", default="top", choices=["hot", "new", "top"],
                        help="Sort method (default: top)")
    parser.add_argument("--output", default="results", help="Output filename without extension (default: results)")
    parser.add_argument("--format", default="both", choices=["json", "csv", "both"],
                        help="Output format (default: both)")
    parser.add_argument("--search-only", action="store_true",
                        help="Only use search queries, skip subreddit browsing")
    parser.add_argument("--subreddits", nargs="+", help="Override default subreddit list")
    args = parser.parse_args()

    reddit = build_reddit_client()
    all_results = []
    subreddits = args.subreddits or SUBREDDITS

    # Phase 1: Browse subreddits
    if not args.search_only:
        print(f"Phase 1: Browsing {len(subreddits)} subreddits ({args.sort}, {args.time})...\n")
        for sub in subreddits:
            print(f"  Scraping r/{sub}...")
            hits = scrape_subreddit(reddit, sub, args.limit, args.time, args.sort)
            print(f"    Found {len(hits)} relevant posts")
            all_results.extend(hits)

    # Phase 2: Targeted searches
    search_queries = [
        "media business tool needed",
        "content creation tool wish",
        "media workflow automation",
        "would pay for media tool",
        "social media management pain",
        "video editing tool business",
        "podcast tool frustration",
        "newsletter platform problem",
        "media agency software",
        "content distribution tool",
        "ad tech tool needed",
        "media buying automation",
    ]

    print(f"\nPhase 2: Running {len(search_queries)} targeted searches...\n")
    for query in search_queries:
        print(f"  Searching: '{query}'...")
        hits = scrape_search(reddit, query, subreddits, args.limit, args.time, args.sort)
        print(f"    Found {len(hits)} results")
        all_results.extend(hits)

    # Deduplicate and sort
    all_results = deduplicate(all_results)
    all_results.sort(key=lambda x: x["relevance_score"], reverse=True)

    # Output
    print_summary(all_results)

    output_dir = os.path.dirname(os.path.abspath(__file__))
    if args.format in ("json", "both"):
        save_json(all_results, os.path.join(output_dir, f"{args.output}.json"))
    if args.format in ("csv", "both"):
        save_csv(all_results, os.path.join(output_dir, f"{args.output}.csv"))


if __name__ == "__main__":
    main()
