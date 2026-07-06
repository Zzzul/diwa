import { Scalar } from "@scalar/hono-api-reference";
import type { Hono } from "hono";

const ex = {
    healthz: { ok: true },
    ranking: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        rank: 1,
        name: "Linux Mint",
        slug: "http://localhost:3000/api/distributions/mint",
        based_on: ["Ubuntu"],
        hpd: 1848,
        yesterday: 1822,
        trend: "up",
        scraped_at: "2026-07-06T12:00:00.000Z",
        dataspan: "26",
    },
    news: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        date: "2026-07-06",
        is_new: true,
        type: "stable",
        headline: "Linux Mint 22.1 Released",
        headline_slug: "http://localhost:3000/api/news/12345",
        headline_url: "https://distrowatch.com/...",
        logo: "https://distrowatch.com/images/...",
        screenshot: "https://distrowatch.com/images/...",
        rating: 5,
        text: "plain text...",
        text_html: "<p>html...</p>",
        links: [
            {
                url: "https://example.com",
                text: "Release Notes",
                href: "/?newsid=12345",
            },
        ],
        distribution: "mint",
        release_type: "stable",
        month: "January",
        year: "2026",
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    newsFilter: {
        data: {
            distribution: [{ value: "mint", label: "Linux Mint" }],
            release: [{ value: "stable", label: "Stable" }],
            month: [{ value: "January", label: "January" }],
            year: [{ value: "2026", label: "2026" }],
        },
    },
    newsDetail: {
        newsid: "12345",
        date: "2026-07-06",
        headline: "Linux Mint 22.1",
        type: "stable",
        logo: "https://distrowatch.com/images/...",
        screenshot: null,
        rating: 5,
        text: "plain text",
        text_html: "<p>html</p>",
        distribution_slug: "mint",
        distribution_summary: {
            os_type: "Linux",
            based_on: "Ubuntu",
            origin: "USA",
            architecture: "x86_64",
            desktop: "Cinnamon",
            category: "Desktop",
            status: "Active",
        },
        related_news: [
            {
                news_id: "67890",
                headline: "Previous news",
                url: "https://distrowatch.com/?newsid=67890",
            },
        ],
        about: "Linux Mint is...",
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    distro: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        name: "Linux Mint",
        slug: "mint",
        logo: "https://distrowatch.com/images/...",
        screenshot: null,
        last_update: "2026-06-15",
        os_type: "Linux",
        based_on: [{ text: "Ubuntu", url: "https://distrowatch.com/ubuntu" }],
        origin: "USA",
        architecture: ["x86_64", "aarch64"],
        desktop: ["Cinnamon", "MATE", "Xfce"],
        category: ["Desktop", "Beginners", "Live Medium"],
        status: "Active",
        popularity: { rank: 2, hpd: 1848 },
        description: "Linux Mint is...",
        rating: 5,
        reviews_count: 42,
        home_page: "https://linuxmint.com",
        user_forums: "https://forums.linuxmint.com",
        documentation: "https://linuxmint.com/documentation.php",
        screenshots: "https://distrowatch.com/...",
        download_mirrors: "https://distrowatch.com/...",
    },
    distroItem: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        slug: "mint",
        name: "Linux Mint",
    },
    latestDist: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        date: "2026-07-06",
        slug: "mint",
        name: "Linux Mint",
        description: "Latest release description",
        download_url: "https://distrowatch.com/...",
        version: "22.1",
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    headline: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        story_id: 12345,
        title: "Ubuntu 24.10 Released",
        url: "https://distrowatch.com/?newsid=12345",
        position: 1,
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    pkg: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        date: "2026-07-06",
        name: "firefox",
        description: "Mozilla Firefox web browser",
        package_url: "https://distrowatch.com/...",
        version: "130.0",
        download_url: "https://distrowatch.com/...",
        position: 1,
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    review: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        date: "2026-07-06",
        title: "Review: Linux Mint 22",
        url: "https://distrowatch.com/...",
        position: 1,
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    newsletter: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        date: "2026-07-06",
        title: "DistroWatch Weekly, Issue 1180",
        url: "https://distrowatch.com/...",
        position: 1,
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    podcast: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        date: "2026-07-06",
        title: "Linux Out Loud",
        url: "https://distrowatch.com/...",
        episode: "86",
        episode_url: "https://...",
        mp3_url: "https://...",
        position: 1,
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    addition: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        date: "2026-07-06",
        name: "NewDistro",
        slug: "newdistro",
        position: 1,
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    waiting: {
        id: "019f3635-fa4d-7000-8a89-87d6d7e45224",
        date: "2026-07-06",
        name: "DistroName",
        url: "https://distrowatch.com/...",
        position: 1,
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    weekly: {
        issue: "20260629",
        issue_number: 1179,
        title: "DistroWatch Weekly, Issue 1179, 29 June 2026",
        date: "2026-06-29",
        sections: [
            {
                id: "news",
                title: "Miscellaneous News (by Jesse Smith)",
                content_html: "<p>...</p>",
                content_text: "...",
            },
        ],
        scraped_at: "2026-07-06T12:00:00.000Z",
    },
    searchFilter: {
        ostype: {
            label: "OS Type",
            options: [{ value: "Linux", label: "Linux" }],
        },
    },
    searchResult: {
        rank: 1,
        name: "Linux Mint",
        slug: "http://localhost:3000/api/distributions/mint",
        popularity: 2,
        description: "Ubuntu-based distribution...",
    },
};

function j(example: unknown) {
    return { "application/json": { schema: { type: "object", example } } };
}

const spec = {
    openapi: "3.1.0",
    info: {
        title: "Diwa API",
        version: "1.0.0",
        description: "API for DistroWatch data — rankings, distributions, news",
    },
    servers: [{ url: "http://localhost:3000", description: "local dev" }],
    paths: {
        "/": {
            get: {
                tags: ["System"],
                summary: "Root",
                responses: {
                    "200": {
                        description: "API info",
                        content: j({
                            name: "diwa",
                            version: "1.0.0",
                            docs: "/api/docs",
                        }),
                    },
                },
            },
        },
        "/api/healthz": {
            get: {
                tags: ["System"],
                summary: "Health check",
                responses: {
                    "200": { description: "OK", content: j(ex.healthz) },
                },
            },
        },
        "/api/rankings": {
            get: {
                tags: ["Rankings"],
                summary: "Rankings list",
                parameters: [
                    { name: "slug", in: "query", schema: { type: "string" } },
                    {
                        name: "dataspan",
                        in: "query",
                        schema: { type: "string", default: "26" },
                    },
                ],
                responses: {
                    "200": {
                        description: "Rankings",
                        content: j({ data: [ex.ranking], count: 1 }),
                    },
                },
            },
        },
        "/api/rankings/dataspans": {
            get: {
                tags: ["Rankings"],
                summary: "Dataspan options",
                responses: { "200": { description: "Dataspans" } },
            },
        },
        "/api/news": {
            get: {
                tags: ["News"],
                summary: "News list",
                parameters: [
                    { name: "type", in: "query", schema: { type: "string" } },
                    { name: "date", in: "query", schema: { type: "string" } },
                    {
                        name: "distribution",
                        in: "query",
                        schema: { type: "string" },
                    },
                    {
                        name: "release",
                        in: "query",
                        schema: { type: "string" },
                    },
                    { name: "month", in: "query", schema: { type: "string" } },
                    { name: "year", in: "query", schema: { type: "string" } },
                ],
                responses: {
                    "200": {
                        description: "News list",
                        content: j({ data: [ex.news], count: 1 }),
                    },
                },
            },
        },
        "/api/news/filters": {
            get: {
                tags: ["News"],
                summary: "News filter options",
                responses: {
                    "200": {
                        description: "Filter options",
                        content: j(ex.newsFilter),
                    },
                },
            },
        },
        "/api/news/{id}": {
            get: {
                tags: ["News"],
                summary: "News detail",
                description: "Numeric DistroWatch ID → scrape detail. UUID → DB lookup.",
                parameters: [
                    {
                        name: "id",
                        in: "path",
                        required: true,
                        schema: { type: "string" },
                    },
                ],
                responses: {
                    "200": {
                        description: "News detail",
                        content: j({ data: ex.newsDetail }),
                    },
                },
            },
        },
        "/api/distributions": {
            get: {
                tags: ["Distributions"],
                summary: "Distribution list",
                responses: {
                    "200": {
                        description: "All distributions",
                        content: j({ data: [ex.distroItem] }),
                    },
                },
            },
        },
        "/api/distributions/random": {
            get: {
                tags: ["Distributions"],
                summary: "Random distribution",
                responses: {
                    "200": {
                        description: "Random distribution",
                        content: j({ data: ex.distro }),
                    },
                },
            },
        },
        "/api/distributions/{slug}": {
            get: {
                tags: ["Distributions"],
                summary: "Distribution detail",
                parameters: [
                    {
                        name: "slug",
                        in: "path",
                        required: true,
                        schema: { type: "string" },
                    },
                ],
                responses: {
                    "200": {
                        description: "Distribution detail",
                        content: j({ data: ex.distro }),
                    },
                },
            },
        },
        "/api/weekly/{issue}": {
            get: {
                tags: ["Weekly"],
                summary: "DistroWatch Weekly issue",
                parameters: [
                    {
                        name: "issue",
                        in: "path",
                        required: true,
                        schema: { type: "string" },
                    },
                ],
                responses: {
                    "200": {
                        description: "Weekly issue",
                        content: j({ data: ex.weekly }),
                    },
                },
            },
        },
        "/api/search/filters": {
            get: {
                tags: ["Search"],
                summary: "Search filter options",
                responses: {
                    "200": {
                        description: "Search filters",
                        content: j({ data: ex.searchFilter }),
                    },
                },
            },
        },
        "/api/search": {
            get: {
                tags: ["Search"],
                summary: "Search distributions",
                parameters: [
                    { name: "ostype", in: "query", schema: { type: "string" } },
                    {
                        name: "category",
                        in: "query",
                        schema: { type: "string" },
                    },
                    { name: "origin", in: "query", schema: { type: "string" } },
                    {
                        name: "basedon",
                        in: "query",
                        schema: { type: "string" },
                    },
                    {
                        name: "desktop",
                        in: "query",
                        schema: { type: "string" },
                    },
                    {
                        name: "architecture",
                        in: "query",
                        schema: { type: "string" },
                    },
                    {
                        name: "package",
                        in: "query",
                        schema: { type: "string" },
                    },
                    {
                        name: "rolling",
                        in: "query",
                        schema: { type: "string" },
                    },
                    { name: "status", in: "query", schema: { type: "string" } },
                ],
                responses: {
                    "200": {
                        description: "Search results",
                        content: j({ data: [ex.searchResult] }),
                    },
                },
            },
        },
        "/api/latest/distributions": {
            get: {
                tags: ["Latest"],
                summary: "Latest distributions",
                parameters: [
                    {
                        name: "limit",
                        in: "query",
                        schema: { type: "integer", default: 50 },
                    },
                ],
                responses: {
                    "200": {
                        description: "Latest distributions",
                        content: j({ data: [ex.latestDist], count: 1 }),
                    },
                },
            },
        },
        "/api/latest/headlines": {
            get: {
                tags: ["Latest"],
                summary: "Latest headlines",
                responses: {
                    "200": {
                        description: "Headlines",
                        content: j({ data: [ex.headline], count: 1 }),
                    },
                },
            },
        },
        "/api/latest/packages": {
            get: {
                tags: ["Latest"],
                summary: "Latest packages",
                responses: {
                    "200": {
                        description: "Packages",
                        content: j({ data: [ex.pkg], count: 1 }),
                    },
                },
            },
        },
        "/api/latest/reviews": {
            get: {
                tags: ["Latest"],
                summary: "Latest reviews",
                responses: {
                    "200": {
                        description: "Reviews",
                        content: j({ data: [ex.review], count: 1 }),
                    },
                },
            },
        },
        "/api/latest/newsletters": {
            get: {
                tags: ["Latest"],
                summary: "Latest newsletters",
                responses: {
                    "200": {
                        description: "Newsletters",
                        content: j({ data: [ex.newsletter], count: 1 }),
                    },
                },
            },
        },
        "/api/latest/podcasts": {
            get: {
                tags: ["Latest"],
                summary: "Latest podcasts",
                responses: {
                    "200": {
                        description: "Podcasts",
                        content: j({ data: [ex.podcast], count: 1 }),
                    },
                },
            },
        },
        "/api/latest/additions": {
            get: {
                tags: ["Latest"],
                summary: "Latest additions",
                responses: {
                    "200": {
                        description: "Additions",
                        content: j({ data: [ex.addition], count: 1 }),
                    },
                },
            },
        },
        "/api/latest/waiting-list": {
            get: {
                tags: ["Latest"],
                summary: "New to waiting list",
                responses: {
                    "200": {
                        description: "Waiting list",
                        content: j({ data: [ex.waiting], count: 1 }),
                    },
                },
            },
        },
    },
};

export function setupOpenApi(app: Hono): void {
    app.get("/api/doc", (c) => c.json(spec));
    app.get(
        "/api/docs",
        Scalar({ url: "/api/doc", pageTitle: "Diwa API Docs" }),
    );
}
